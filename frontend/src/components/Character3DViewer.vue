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
    },
    type: {
        type: String,
        default: 'persoon'
    }
});

const canvasContainer = ref(null);
const loading = ref(true);
const error = ref(null);

let renderer, scene, camera, controls, animationFrameId, clock;
let currentModel = null;
let mixer = null;
const actions = {};
let activeAction = null;
const currentAnim = ref('idle');

const initThree = () => {
    if (!canvasContainer.value) return;

    // 1. Scene setup
    scene = new THREE.Scene();

    // 2. Camera setup
    const width = canvasContainer.value.clientWidth || 300;
    const height = canvasContainer.value.clientHeight || 400;
    camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
    camera.position.set(0, 1.2, 3);

    // 3. Renderer setup
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(window.devicePixelRatio);
    renderer.setSize(width, height);
    renderer.outputColorSpace = THREE.SRGBColorSpace;

    // Modern Tone Mapping (standard for glTF viewers)
    renderer.toneMapping = THREE.NeutralToneMapping;
    renderer.toneMappingExposure = 1.25;

    canvasContainer.value.appendChild(renderer.domElement);

    // 4. Environment Map (Vital for PBR materials like characters)
    const pmremGenerator = new THREE.PMREMGenerator(renderer);
    pmremGenerator.compileEquirectangularShader();

    const environment = new RoomEnvironment();
    const envMap = pmremGenerator.fromScene(environment).texture;
    scene.environment = envMap;
    environment.dispose(); // No longer needed once flattened

    // 4. Controls setup
    controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.autoRotate = true;
    controls.autoRotateSpeed = 2.0;
    controls.target.set(0, 1, 0);
    controls.update();

    // Log camera settings on change to help the user tune the view
    // controls.addEventListener('change', () => {
    //     console.log(`[CAM_DEBUG] Pos: ${camera.position.x.toFixed(2)}, ${camera.position.y.toFixed(2)}, ${camera.position.z.toFixed(2)} | Target: ${controls.target.x.toFixed(2)}, ${controls.target.y.toFixed(2)}, ${controls.target.z.toFixed(2)}`);
    // });

    // loadModel();

    // 5. Lighting setup
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.8);
    scene.add(ambientLight);

    const mainLight = new THREE.DirectionalLight(0xffffff, 2.5);
    mainLight.position.set(2, 4, 4);
    scene.add(mainLight);

    const fillLight = new THREE.DirectionalLight(0xffffff, 1.0);
    fillLight.position.set(-2, 2, -4);
    scene.add(fillLight);

    // 6. Global base light (Slightly reduced since we now have Scene Environment)
    const hemiLight = new THREE.HemisphereLight(0xffffff, 0x444444, 0.5);
    scene.add(hemiLight);

    clock = new THREE.Clock();

    loadModel();
    animate();
};

const playAnimation = (name) => {
    const nextAction = actions[name];
    if (!nextAction || nextAction === activeAction) return;

    if (activeAction) {
        nextAction.reset();
        nextAction.crossFadeFrom(activeAction, 0.5, true);
        nextAction.play();
    } else {
        nextAction.play();
    }
    activeAction = nextAction;
    currentAnim.value = name;
};

const loadModel = () => {
    if (!props.glbUrl) return;

    loading.ref = true;
    error.value = null;

    // Clean up existing model
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
    // Match the scene tester's cross-origin setting
    loader.setCrossOrigin('anonymous');

    loader.load(props.glbUrl, (gltf) => {
        currentModel = gltf.scene;
        scene.add(currentModel);

        // Compute bounding box to center and scale correctly
        const box = new THREE.Box3().setFromObject(currentModel);
        const size = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());

        // Ground the character
        currentModel.position.y = -box.min.y;

        // Center horizontally
        currentModel.position.x = -center.x;
        currentModel.position.z = -center.z;

        // Dynamic Framing
        let targetY, cameraDistance;
        const fov = camera.fov * (Math.PI / 180);

        if (props.type === 'voertuig') {
            // Frame the entire vehicle (1/1)
            targetY = center.y;
            // Calculate distance to frame the whole height
            cameraDistance = (size.y / 2) / Math.tan(fov / 2);
            // Vehicles are often wider than they are tall, so we add a generous buffer
            cameraDistance = Math.max(cameraDistance, (size.x / 2) / Math.tan(fov / 2)) * 1.5;
        } else {
            // Dynamic Closeup Framing: Frame the top 1/4 of the subject (Head/Bust area)
            const frameHeight = size.y / 4;
            targetY = size.y * 0.85; // Roughly head level
            
            // Calculate distance to frame the vertical size (frameHeight)
            cameraDistance = (frameHeight / 2) / Math.tan(fov / 2);
            cameraDistance *= 1.5; // 1.5x buffer for horizontal fit
        }

        camera.position.set(0, targetY, cameraDistance);
        controls.target.set(0, targetY, 0);
        controls.update();

        // Setup Animations
        if (gltf.animations && gltf.animations.length > 0) {
            mixer = new THREE.AnimationMixer(currentModel);
            
            gltf.animations.forEach(clip => {
                const name = clip.name.toLowerCase();
                if (name.startsWith('walk')) actions['walk'] = mixer.clipAction(clip);
                else if (name.startsWith('idle')) actions['idle'] = mixer.clipAction(clip);
                else if (name.startsWith('caut')) actions['caution'] = mixer.clipAction(clip);
            });

            if (actions['idle']) playAnimation('idle');
            else if (Object.keys(actions).length > 0) playAnimation(Object.keys(actions)[0]);
        }

        // Strip lights from character GLB
        currentModel.traverse(child => {
            if (child.isLight) {
                child.visible = false;
            }
        });

        loading.value = false;
        // console.log('Character GLB met succes geladen.');
    }, (progress) => {
        // Handle progress if needed
    }, (err) => {
        console.error('Failed to load character GLB:', err);
        error.value = 'SCAN_ERROR: TARGET_ARGHH_ONVINDBAAR';
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
        renderer.forceContextLoss();
    }
});

watch(() => props.glbUrl, () => {
    loadModel();
});
</script>

<template>
    <div class="w-full h-full bg-black/40 rounded border border-noir-dark overflow-hidden relative group">
        <!-- 3D Canvas Container -->
        <div ref="canvasContainer" class="w-full h-full pointer-events-auto cursor-grab active:cursor-grabbing"></div>

        <!-- Loading Overlay -->
        <div v-if="loading" class="absolute inset-0 flex flex-col items-center justify-center bg-noir-darker/80 z-20">
            <div class="w-8 h-8 border-2 border-noir-accent border-t-transparent rounded-full animate-spin mb-4"></div>
            <span class="text-[10px] text-noir-accent font-mono animate-pulse uppercase tracking-widest">Inlining_Neural_Scan...</span>
        </div>

        <!-- Error Overlay -->
        <div v-if="error" class="absolute inset-0 flex flex-col items-center justify-center bg-noir-danger/10 z-20 p-4 text-center">
            <div class="text-2xl mb-2">⚠️</div>
            <span class="text-[10px] text-noir-danger font-mono uppercase tracking-widest">{{ error }}</span>
        </div>

        <!-- UI Decorators -->
        <div class="absolute top-2 left-2 text-[10px] text-noir-muted uppercase font-mono bg-black/70 px-2 py-0.5 rounded border border-white/5 pointer-events-none z-10 transition-opacity group-hover:opacity-100">
            [{{ props.type === 'voertuig' ? 'VEHICLE_DRIVE_v1.0' : 'CHARACTER_CORE_v1.0' }}]
        </div>

        <div class="absolute bottom-2 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity text-[10px] text-noir-muted uppercase font-mono bg-black/70 px-3 py-1 rounded border border-white/5 pointer-events-none z-10 whitespace-nowrap">
            DRAG_IS_ROTATE // SCROLL_IS_ZOOM
        </div>

        <!-- Animation Toggles -->
        <div v-if="Object.keys(actions).length > 0" class="absolute top-2 right-2 flex flex-col gap-1 z-30">
            <button v-for="(action, name) in actions" 
                    :key="name"
                    @click="playAnimation(name)"
                    class="px-2 py-0.5 text-[8px] font-mono border transition-colors uppercase"
                    :class="currentAnim === name ? 'bg-noir-accent text-black border-noir-accent' : 'bg-black/50 text-noir-muted border-white/10 hover:border-white/30'">
                {{ name }}
            </button>
        </div>

        <!-- Scanning Line Effect -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden opacity-20 group-hover:opacity-40 transition-opacity">
            <div class="w-full h-[1px] bg-noir-accent absolute scan-line"></div>
        </div>
    </div>
</template>

<style scoped>
.scan-line {
    animation: scan 4s linear infinite;
    box-shadow: 0 0 10px var(--color-noir-accent);
}

@keyframes scan {
    0% { top: -10%; }
    100% { top: 110%; }
}

canvas {
    display: block;
    outline: none;
}
</style>
