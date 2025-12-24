<script setup>
import { ref, onMounted, onUnmounted, computed, reactive, watch } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import axios from '../../axios';
import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader';

const route = useRoute();
const router = useRouter();
const locatieId = route.params.id;
const sectorId = route.params.sectorId;

const locatieData = ref(null);
const sectorData = ref(null);
const loading = ref(true);
const error = ref(null);
const floorDetected = ref(false);
const lightsDetectedCount = ref(0);

// Three.js refs
const canvasContainer = ref(null);
let renderer, scene, camera, clock;
let currentGltf = null;
let animationFrameId = null;
let testCubeMesh = null;

const VIEW_WIDTH = 1216;
const VIEW_HEIGHT = 832;
const ASPECT_RATIO = VIEW_WIDTH / VIEW_HEIGHT;

// Cube Controls
const cubePosition = reactive({ x: 0, y: 0.5, z: 0 });

const slugify = (str) => {
    if (!str) return '';
    return str
        .toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_]+/g, '-')
        .replace(/^-+|-+$/g, '');
};

const getImageUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    const cleanPath = path.startsWith('/') ? path : `/${path}`;
    return `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/storage${cleanPath}`;
};

const glbUrl = computed(() => {
    if (!locatieData.value || !sectorData.value) return '';
    const sectorSlug = slugify(sectorData.value.naam);
    const locationSlug = slugify(locatieData.value.naam);
    return `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/storage/glb/${sectorSlug}--${locationSlug}.glb`;
});

const backgroundImageUrl = computed(() => {
    if (!locatieData.value || !locatieData.value.artwork || locatieData.value.artwork.length === 0) {
        return '';
    }
    return getImageUrl(locatieData.value.artwork[0].bestandspad);
});

// Responsive Logic
const containerWidth = ref(VIEW_WIDTH);
const containerHeight = ref(VIEW_HEIGHT);

const updateDimensions = () => {
    const verticalOffset = 380;
    const maxWidth = window.innerWidth - 64;
    const maxHeight = window.innerHeight - verticalOffset;

    let newWidth = maxWidth;
    let newHeight = newWidth / ASPECT_RATIO;

    if (newHeight > maxHeight) {
        newHeight = maxHeight;
        newWidth = newHeight * ASPECT_RATIO;
    }

    if (newWidth > VIEW_WIDTH) {
        newWidth = VIEW_WIDTH;
        newHeight = VIEW_HEIGHT;
    }

    containerWidth.value = Math.floor(newWidth);
    containerHeight.value = Math.floor(newHeight);

    if (renderer && camera) {
        camera.aspect = ASPECT_RATIO;
        camera.updateProjectionMatrix();
        renderer.setSize(containerWidth.value, containerHeight.value);
    }
};

onMounted(async () => {
    await fetchData();
    window.addEventListener('resize', updateDimensions);
    updateDimensions();
    initThree();
    if (glbUrl.value) {
        loadGLB();
    }
});

onUnmounted(() => {
    window.removeEventListener('resize', updateDimensions);
    if (animationFrameId) cancelAnimationFrame(animationFrameId);
    if (renderer) renderer.dispose();
    if (scene) {
        scene.traverse((object) => {
            if (object.isMesh) {
                object.geometry.dispose();
                if (object.material.isMaterial) {
                    cleanMaterial(object.material);
                } else {
                    for (const material of object.material) cleanMaterial(material);
                }
            }
        });
    }
});

const cleanMaterial = (material) => {
    material.dispose();
    for (const key of Object.keys(material)) {
        if (material[key] && material[key].isTexture) {
            material[key].dispose();
        }
    }
};

const fetchData = async () => {
    try {
        const [locRes, secRes] = await Promise.all([
            axios.get(`/api/locaties/${locatieId}`),
            axios.get(`/api/sectoren/${sectorId}`)
        ]);
        locatieData.value = locRes.data;
        sectorData.value = secRes.data;
    } catch (e) {
        console.error("Failed to fetch data", e);
        error.value = "Failed to load location or sector data.";
    } finally {
        loading.value = false;
    }
};

const initThree = () => {
    if (!canvasContainer.value) return;

    scene = new THREE.Scene();
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(containerWidth.value, containerHeight.value);
    renderer.setPixelRatio(window.devicePixelRatio);
    canvasContainer.value.appendChild(renderer.domElement);

    camera = new THREE.PerspectiveCamera(45, ASPECT_RATIO, 0.1, 1000);
    camera.position.set(5, 5, 5);
    camera.lookAt(0, 0, 0);

    clock = new THREE.Clock();

    const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
    scene.add(ambientLight);

    const defaultSun = new THREE.DirectionalLight(0xffffff, 1);
    defaultSun.position.set(5, 10, 5);
    defaultSun.name = "default-sun";
    scene.add(defaultSun);

    const geometry = new THREE.BoxGeometry(1, 1, 1);
    const material = new THREE.MeshStandardMaterial({ color: 0xff0000 });
    testCubeMesh = new THREE.Mesh(geometry, material);
    testCubeMesh.position.set(0, 0.5, 0);
    testCubeMesh.name = "test-cube";
    scene.add(testCubeMesh);

    cubePosition.x = testCubeMesh.position.x;
    cubePosition.y = testCubeMesh.position.y;
    cubePosition.z = testCubeMesh.position.z;

    const gridHelper = new THREE.GridHelper(10, 10);
    scene.add(gridHelper);

    animate();
};

const loadGLB = () => {
    const loader = new GLTFLoader();
    loader.setCrossOrigin('anonymous');

    loader.load(glbUrl.value, (gltf) => {
        currentGltf = gltf.scene;
        scene.add(currentGltf);

        let fspyCamera = null;
        let sunLight = null;
        let floorMesh = null;

        gltf.scene.traverse((child) => {
            if (child.name === 'camerafspy' || child.name.includes('fspy')) {
                if (child.isCamera) fspyCamera = child;
            }
            if (child.name.toLowerCase() === 'sun') {
                sunLight = child;
            }
            if ((child.name === 'floor' || child.name === 'plane') && child.isMesh) {
                floorMesh = child;
                floorDetected.value = true;
            }

            if (child.name === 'point-1' || child.name === 'point-2') {
                lightsDetectedCount.value++;
                if (!child.isLight) {
                    const pointLight = new THREE.PointLight(0xffffff, 10, 50);
                    const worldPos = new THREE.Vector3();
                    child.getWorldPosition(worldPos);
                    pointLight.position.copy(worldPos);
                    scene.add(pointLight);

                    const helper = new THREE.Mesh(
                        new THREE.SphereGeometry(0.1),
                        new THREE.MeshBasicMaterial({ color: 0xffff00 })
                    );
                    helper.position.copy(worldPos);
                    scene.add(helper);
                }
            }
        });

        if (floorMesh) {
            floorMesh.material = new THREE.MeshBasicMaterial({
                color: 0x00ffff,
                wireframe: false,
                transparent: true,
                opacity: 0.5
            });

            const box = new THREE.Box3().setFromObject(floorMesh);
            const center = new THREE.Vector3();
            box.getCenter(center);

            if (testCubeMesh) {
                testCubeMesh.position.set(center.x, box.max.y + 0.5, center.z);
                cubePosition.x = testCubeMesh.position.x;
                cubePosition.y = testCubeMesh.position.y;
                cubePosition.z = testCubeMesh.position.z;
            }
        }

        if (fspyCamera) {
            camera = fspyCamera;
            camera.aspect = ASPECT_RATIO;
            camera.updateProjectionMatrix();
        }

        if (sunLight) {
            const defSun = scene.getObjectByName('default-sun');
            if (defSun) scene.remove(defSun);
        }

    }, null, (err) => {
        console.error("Error loading GLB:", err);
        error.value = `GLB not found or failed to load. Path: ${glbUrl.value}`;
    });
};

const animate = () => {
    animationFrameId = requestAnimationFrame(animate);
    renderer.render(scene, camera);
};

const updateCubePos = () => {
    if (testCubeMesh) {
        testCubeMesh.position.set(cubePosition.x, cubePosition.y, cubePosition.z);
    }
};

watch(cubePosition, () => {
    updateCubePos();
});

const onScroll = (e) => {
    e.preventDefault();
    if (e.shiftKey) {
        const delta = e.deltaX > 0 ? 0.5 : -0.5;
        cubePosition.x += delta;
    } else {
        const delta = e.deltaY > 0 ? 0.5 : -0.5;
        cubePosition.z += delta;
    }
};
</script>

<template>
    <div class="min-h-screen bg-noir-darker p-8">
        <div class="max-w-[1300px] mx-auto">
            <div class="flex items-center text-sm text-noir-muted mb-4">
                <RouterLink to="/locaties" class="hover:text-white">&lt; LOCATIES</RouterLink>
                <span class="mx-2">/</span>
                <RouterLink :to="`/locaties/${locatieId}`" class="hover:text-white">&lt; BACK_TO_LOCATIE</RouterLink>
                <span class="mx-2">/</span>
                <span class="text-white">3D_CONTROLE_VIEUW</span>
            </div>

            <div v-if="loading" class="flex items-center justify-center py-40">
                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-noir-accent"></div>
            </div>
            <div v-else-if="error" class="bg-noir-panel border border-noir-danger p-6 rounded text-noir-danger text-center">
                {{ error }}
                <div class="mt-4 text-xs text-noir-muted">Target Path: {{ glbUrl }}</div>
            </div>
            <div v-else class="flex flex-col items-center">
                <div class="relative border-4 border-noir-dark shadow-[0_0_50px_rgba(0,0,0,0.5)] bg-black overflow-hidden"
                     :style="{ width: containerWidth + 'px', height: containerHeight + 'px' }"
                     @wheel="onScroll">
                    <img v-if="backgroundImageUrl" :src="backgroundImageUrl" class="absolute inset-0 w-full h-full object-cover pointer-events-none" alt="Background" />
                    <div ref="canvasContainer" class="absolute inset-0 pointer-events-auto z-10"></div>
                </div>

                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6 w-full text-xs">
                     <div class="bg-noir-panel p-4 border border-noir-dark rounded">
                        <h3 class="text-white font-bold mb-2 uppercase border-b border-noir-dark pb-1 text-[10px]">INFO</h3>
                        <p class="text-noir-text">LOC: {{ locatieData.naam }}</p>
                        <p class="text-noir-text">SEC: {{ sectorData.naam }}</p>
                    </div>
                    <div class="bg-noir-panel p-4 border border-noir-dark rounded">
                        <h3 class="text-white font-bold mb-2 uppercase border-b border-noir-dark pb-1 text-[10px]">RODE KUBUS</h3>
                        <ul class="text-noir-text space-y-1">
                            <li>• Scroll: Z-DIEPTE</li>
                            <li>• Shift + Scroll: X-AS</li>
                        </ul>
                    </div>
                   <div class="bg-noir-panel p-4 border border-noir-dark rounded">
                        <h3 class="text-white font-bold mb-2 uppercase border-b border-noir-dark pb-1 text-[10px]">DEBUG_CUBE</h3>
                        <div class="flex gap-2">
                            <span>X: {{ cubePosition.x.toFixed(2) }}</span>
                            <span>Y: {{ cubePosition.y.toFixed(2) }}</span>
                            <span>Z: {{ cubePosition.z.toFixed(2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
:deep(canvas) {
    background: transparent !important;
}
</style>
