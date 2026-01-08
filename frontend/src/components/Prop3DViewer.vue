<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls';
import { RoomEnvironment } from 'three/examples/jsm/environments/RoomEnvironment';

const props = defineProps({
    glbUrl: {
        type: String,
        required: true
    }
});

const canvasContainer = ref(null);
const loading = ref(true);
const error = ref(null);

let renderer, scene, camera, controls, animationFrameId, clock;
let currentModel = null;
let mixer = null;

const initThree = () => {
    if (!canvasContainer.value) return;

    scene = new THREE.Scene();

    const width = canvasContainer.value.clientWidth || 300;
    const height = canvasContainer.value.clientHeight || 400;
    camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
    camera.position.set(2, 2, 2);

    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(window.devicePixelRatio);
    renderer.setSize(width, height);
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.NeutralToneMapping;
    renderer.toneMappingExposure = 1.0;

    canvasContainer.value.appendChild(renderer.domElement);

    const pmremGenerator = new THREE.PMREMGenerator(renderer);
    pmremGenerator.compileEquirectangularShader();

    const environment = new RoomEnvironment();
    const envMap = pmremGenerator.fromScene(environment).texture;
    scene.environment = envMap;
    environment.dispose();

    controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.autoRotate = true;
    controls.autoRotateSpeed = 4.0;
    controls.update();

    const ambientLight = new THREE.AmbientLight(0xffffff, 0.8);
    scene.add(ambientLight);

    const mainLight = new THREE.DirectionalLight(0xffffff, 2.0);
    mainLight.position.set(2, 4, 4);
    scene.add(mainLight);

    clock = new THREE.Clock();

    loadModel();
    animate();
};

const loadModel = () => {
    if (!props.glbUrl) return;

    loading.value = true;
    error.value = null;

    if (currentModel) {
        scene.remove(currentModel);
        currentModel.traverse((child) => {
            if (child.isMesh) {
                child.geometry.dispose();
                if (Array.isArray(child.material)) {
                    child.material.forEach(m => m.dispose());
                } else {
                    child.material.dispose();
                }
            }
        });
    }

    const loader = new GLTFLoader();
    loader.setCrossOrigin('anonymous');

    loader.load(props.glbUrl, (gltf) => {
        currentModel = gltf.scene;
        scene.add(currentModel);

        const box = new THREE.Box3().setFromObject(currentModel);
        const size = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());

        const maxDim = Math.max(size.x, size.y, size.z);
        const fov = camera.fov * (Math.PI / 180);
        let cameraDistance = Math.abs(maxDim / Math.sin(fov / 2));
        cameraDistance *= 1.25;

        camera.position.set(cameraDistance, cameraDistance, cameraDistance);
        controls.target.copy(center);
        controls.update();

        if (gltf.animations && gltf.animations.length > 0) {
            mixer = new THREE.AnimationMixer(currentModel);
            const action = mixer.clipAction(gltf.animations[0]);
            action.play();
        }

        loading.value = false;
    }, undefined, (err) => {
        console.error('Failed to load prop GLB:', err);
        error.value = 'LOAD_ERROR';
        loading.value = false;
    });
};

const animate = () => {
    animationFrameId = requestAnimationFrame(animate);
    const delta = clock ? clock.getDelta() : 0.016;

    if (mixer) mixer.update(delta);
    if (controls) controls.update();
    if (renderer && scene && camera) renderer.render(scene, camera);
};

const handleResize = () => {
    if (!canvasContainer.value || !renderer || !camera) return;
    const width = canvasContainer.value.clientWidth;
    const height = canvasContainer.value.clientHeight;

    renderer.setSize(width, height);
    camera.aspect = width / height;
    camera.updateProjectionMatrix();
};

onMounted(async () => {
    await nextTick();
    initThree();
    window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
    if (animationFrameId) cancelAnimationFrame(animationFrameId);
    if (renderer) {
        renderer.dispose();
    }
});

watch(() => props.glbUrl, () => {
    loadModel();
});
</script>

<template>
    <div class="w-full h-full bg-black/60 rounded border border-noir-dark overflow-hidden relative group aspect-square">
        <div ref="canvasContainer" class="w-full h-full pointer-events-auto cursor-grab active:cursor-grabbing"></div>

        <div v-if="loading" class="absolute inset-0 flex flex-col items-center justify-center bg-noir-darker/80 z-20">
            <div class="w-8 h-8 border-2 border-noir-accent border-t-transparent rounded-full animate-spin mb-4"></div>
            <span class="text-[10px] text-noir-accent font-mono animate-pulse uppercase tracking-widest">Scanning_Object...</span>
        </div>

        <div v-if="error" class="absolute inset-0 flex flex-col items-center justify-center bg-noir-danger/10 z-20 p-4 text-center">
            <span class="text-[10px] text-noir-danger font-mono uppercase tracking-widest">{{ error }}</span>
        </div>

        <div class="absolute top-2 left-2 text-[10px] text-noir-muted uppercase font-mono bg-black/70 px-2 py-0.5 rounded border border-white/5 pointer-events-none z-10 transition-opacity">
            [OBJECT_VIEW_v1.0]
        </div>
        
        <div class="absolute bottom-2 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity text-[10px] text-noir-muted uppercase font-mono bg-black/70 px-3 py-1 rounded border border-white/5 pointer-events-none z-10 whitespace-nowrap">
            DRAG_TO_ROTATE
        </div>
    </div>
</template>

<style scoped>
canvas {
    display: block;
    outline: none;
}
</style>
