<script setup>
    import { ref, onMounted, onUnmounted, computed, reactive, watch } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import axios from '../../axios';
import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader';

const route = useRoute();
const router = useRouter();
const sceneId = route.params.id;

const sceneData = ref(null);
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
    if (!sceneData.value || !sceneData.value.sector || !sceneData.value.locatie) return '';
    const sectorSlug = slugify(sceneData.value.sector.naam);
    const locationSlug = slugify(sceneData.value.locatie.naam);
    return `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/storage/glb/${sectorSlug}--${locationSlug}.glb`;
});

const backgroundImageUrl = computed(() => {
    if (!sceneData.value || !sceneData.value.locatie || !sceneData.value.locatie.artwork || sceneData.value.locatie.artwork.length === 0) {
        return '';
    }
    return getImageUrl(sceneData.value.locatie.artwork[0].bestandspad);
});

// Responsive Logic
const containerWidth = ref(VIEW_WIDTH);
const containerHeight = ref(VIEW_HEIGHT);
const ASPECT_RATIO = VIEW_WIDTH / VIEW_HEIGHT;

const updateDimensions = () => {
    // Calculate available space
    // Header ~ 100px, Instructions ~ 250px, Padding ~ 64px
    const verticalOffset = 380;
    const maxWidth = window.innerWidth - 64; // Horizontal padding
    const maxHeight = window.innerHeight - verticalOffset;

    let newWidth = maxWidth;
    let newHeight = newWidth / ASPECT_RATIO;

    if (newHeight > maxHeight) {
        newHeight = maxHeight;
        newWidth = newHeight * ASPECT_RATIO;
    }

    // Clamp to original max size if desired? Or allow scaling up?
    // Let's cap at original max resolution for sharpness, but allow simpler scaling
    if (newWidth > VIEW_WIDTH) {
        newWidth = VIEW_WIDTH;
        newHeight = VIEW_HEIGHT;
    }

    containerWidth.value = Math.floor(newWidth);
    containerHeight.value = Math.floor(newHeight);

    if (renderer && camera) {
        camera.aspect = ASPECT_RATIO; // Keep aspect locked to source aspect?
        camera.updateProjectionMatrix();
        renderer.setSize(containerWidth.value, containerHeight.value);
    }
};

onMounted(async () => {
    await fetchSceneData();
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

const fetchSceneData = async () => {
    try {
        const response = await axios.get(`/api/scenes/${sceneId}`);
        sceneData.value = response.data;
    } catch (e) {
        console.error("Failed to fetch scene data", e);
        error.value = "Failed to load scene data.";
    } finally {
        loading.value = false;
    }
};

const initThree = () => {
    if (!canvasContainer.value) return;

    // SCENE
    scene = new THREE.Scene();

    // RENDERER
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(containerWidth.value, containerHeight.value);
    renderer.setPixelRatio(window.devicePixelRatio);
    canvasContainer.value.appendChild(renderer.domElement);

    // Initial default camera (will be overridden by GLB camera if found)
    camera = new THREE.PerspectiveCamera(45, ASPECT_RATIO, 0.1, 1000);
    camera.position.set(5, 5, 5);
    camera.lookAt(0, 0, 0);

    // CLOCK
    clock = new THREE.Clock();

    // DEFAULT LIGHTS
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
    scene.add(ambientLight);

    const defaultSun = new THREE.DirectionalLight(0xffffff, 1);
    defaultSun.position.set(5, 10, 5);
    defaultSun.name = "default-sun";
    scene.add(defaultSun);

    // HELPER CUBE
    const geometry = new THREE.BoxGeometry(1, 1, 1);
    const material = new THREE.MeshStandardMaterial({ color: 0xff0000 });
    testCubeMesh = new THREE.Mesh(geometry, material);
    testCubeMesh.position.set(0, 0.5, 0);
    testCubeMesh.name = "test-cube";
    scene.add(testCubeMesh);

    // Sync reactive state
    cubePosition.x = testCubeMesh.position.x;
    cubePosition.y = testCubeMesh.position.y;
    cubePosition.z = testCubeMesh.position.z;

    // GRID HELPER
    const gridHelper = new THREE.GridHelper(10, 10);
    scene.add(gridHelper);

    animate();
};

const loadGLB = () => {
    const loader = new GLTFLoader();
    // Allow cross-origin requests
    loader.setCrossOrigin('anonymous');

    loader.load(glbUrl.value, (gltf) => {
        currentGltf = gltf.scene;
        scene.add(currentGltf);

        console.log("GLB Loaded successfully:", glbUrl.value);

        // Find specific nodes
        let fspyCamera = null;
        let sunLight = null;
        let floorMesh = null;

        gltf.scene.traverse((child) => {
            console.log("Node name:", child.name);
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
            
            // Check for specific light nodes point-1 and point-2
            if (child.name === 'point-1' || child.name === 'point-2') {
                console.log(`Found node ${child.name} for lighting`);
                lightsDetectedCount.value++;
                
                // If it's not already a light, we should create one at this position
                if (!child.isLight) {
                    const pointLight = new THREE.PointLight(0xffffff, 10, 50);
                    // Use world position
                    const worldPos = new THREE.Vector3();
                    child.getWorldPosition(worldPos);
                    pointLight.position.copy(worldPos);
                    scene.add(pointLight);
                    console.log(`Created PointLight at position of ${child.name}:`, worldPos);
                    
                    // Add a small helper to see where the light is
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
            console.log("Found floor mesh, applying helper material");
            floorMesh.material = new THREE.MeshBasicMaterial({
                color: 0x00ffff,
                wireframe: false,
                transparent: true,
                opacity: 0.5
            });

            // Position cube on top of floor mesh
            const box = new THREE.Box3().setFromObject(floorMesh);
            const center = new THREE.Vector3();
            box.getCenter(center);

            if (testCubeMesh) {
                testCubeMesh.position.set(center.x, box.max.y + 0.5, center.z);
                // Sync UI
                cubePosition.x = testCubeMesh.position.x;
                cubePosition.y = testCubeMesh.position.y;
                cubePosition.z = testCubeMesh.position.z;
                console.log("Moved test-cube to floor position:", testCubeMesh.position);
            }
        }

        if (fspyCamera) {
            console.log("Found fSpy camera, switching...");
            camera = fspyCamera;
            camera.aspect = ASPECT_RATIO;
            camera.updateProjectionMatrix();
        } else {
            console.log("No fSpy camera found, using default camera");
        }

        if (sunLight) {
            console.log("Found sun light in GLB");
            const defSun = scene.getObjectByName('default-sun');
            if (defSun) scene.remove(defSun);
        }

    }, (progress) => {
        if (progress.total > 0) {
            console.log(`Loading GLB: ${(progress.loaded / progress.total * 100)}%`);
        } else {
            console.log(`Loading GLB: ${progress.loaded} bytes`);
        }
    }, (err) => {
        console.error("Error loading GLB:", err);
        console.error("Target URL:", glbUrl.value);
        error.value = `GLB not found or failed to load. Check console for details. Path: ${glbUrl.value}`;
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

// Sync controls with mesh
watch(cubePosition, () => {
    updateCubePos();
});

const onScroll = (e) => {
    e.preventDefault();

    if (e.shiftKey) {
        // Shift + Scroll = X-axis
        // User confirmed X should behave exactly like Z (Up/Down)
        const delta = e.deltaX > 0 ? 0.5 : -0.5;
        cubePosition.x += delta;
    } else {
        // Normal Scroll = Z-axis
        const delta = e.deltaY > 0 ? 0.5 : -0.5;
        cubePosition.z += delta;
    }
};
</script>
<template>
    <div class="min-h-screen bg-noir-darker p-8">
        <div class="max-w-[1300px] mx-auto">
            <div class="flex items-center text-sm text-noir-muted">
                <span>
                    <RouterLink to="/scenes" class="hover:text-white">&lt; SCENES</RouterLink>
                    &nbsp;
                </span>
                <span>
                    <RouterLink :to="`/scenes/${sceneId}`" class="hover:text-white">&lt; SCENE DETAILS</RouterLink>
                    <span class="mx-2">/</span>
                    <span class="text-white">3D TESTER</span>
                    &nbsp;
                </span>

            </div>

            <span v-if="sceneData" class="text-noir-muted text-sm mt-1">
                {{ sceneData.sector?.naam }} // {{ sceneData.locatie?.naam }} // {{ sceneData.titel }}
            </span>

            <div v-if="loading" class="flex items-center justify-center py-40">
                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-noir-accent"></div>
            </div>
            <div v-else-if="error" class="bg-noir-panel border border-noir-danger p-6 rounded text-noir-danger text-center">
                {{ error }}
                <div class="mt-4 text-xs text-noir-muted">Expected path: {{ glbUrl }}</div>
            </div>
            <div v-else class="flex flex-col items-center">
                <!-- Tester Viewport Container -->
                <div class="relative border-4 border-noir-dark shadow-[0_0_50px_rgba(0,0,0,0.5)] bg-black overflow-hidden" :style="{ width: containerWidth + 'px', height: containerHeight + 'px' }" @wheel="onScroll">
                    <!-- Background Image (Layer 0) -->
                    <img v-if="backgroundImageUrl" :src="backgroundImageUrl" class="absolute inset-0 w-full h-full object-cover pointer-events-none opacity-100" alt="Background" />
                    <!-- Three.js Canvas (Layer 1) -->
                    <div ref="canvasContainer" class="absolute inset-0 pointer-events-auto z-10"></div>
                    <!-- Overlay Info -->
                    <div class="absolute bottom-4 left-4 z-20 bg-black/60 p-3 rounded border border-white/10 text-xs font-mono text-noir-accent pointer-events-none">
                        RESOLUTION: {{ containerWidth }}x{{ containerHeight }}<br />
                        GLB: {{ glbUrl.split('/').pop() }}
                    </div>
                </div>
                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
                    <div class="bg-noir-panel p-4 border border-noir-dark rounded">
                        <h3 class="text-white font-bold mb-2 uppercase text-sm border-b border-noir-dark pb-1">INSTRUCTIES</h3>
                        <ul class="text-noir-text text-xs space-y-2">
                            <li>• Camera is <strong>GELOCKED</strong> om fSpy perspectief te checken.</li>
                            <li>• Scroll = <strong>Z-DEPTH</strong> (Diepte).</li>
                            <li>• Shift + Scroll = <strong>X-AXIS</strong> (Horizontaal).</li>
                            <li>• Gebruik controls rechts om te finetunen.</li>
                        </ul>
                    </div>
                    <div class="bg-noir-panel p-4 border border-noir-dark rounded">
                        <h3 class="text-white font-bold mb-2 uppercase text-sm border-b border-noir-dark pb-1">NODES_STATUS</h3>
                        <ul class="text-noir-text text-xs space-y-2">
                            <li :class="sceneData ? 'text-noir-success' : 'text-noir-danger'">• Scene Data: Loaded</li>
                            <li :class="backgroundImageUrl ? 'text-noir-success' : 'text-noir-danger'">• Background Image: {{ backgroundImageUrl ? 'Found' : 'Missing' }}</li>
                            <li :class="floorDetected ? 'text-noir-success' : 'text-noir-danger'">• Floor Mesh: {{ floorDetected ? 'Found' : 'Missing' }}</li>
                            <li :class="lightsDetectedCount > 0 ? 'text-noir-success' : 'text-noir-muted'">• Lights: {{ lightsDetectedCount > 0 ? `Found ${lightsDetectedCount}` : 'None' }}</li>
                        </ul>
                    </div>
                    <div class="bg-noir-panel p-4 border border-noir-dark rounded">
                        <h3 class="text-white font-bold mb-2 uppercase text-sm border-b border-noir-dark pb-1">DEBUG_ACTIONS (CUBE)</h3>
                        <div class="space-y-2 mt-2">
                            <div class="flex items-center gap-2">
                                <span class="text-noir-muted w-4 text-xs font-bold">X</span>
                                <input type="number" step="0.5" v-model.number="cubePosition.x" class="bg-noir-darker text-white border border-noir-dark rounded px-2 py-1 text-xs w-full focus:outline-none focus:border-noir-accent">
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-noir-muted w-4 text-xs font-bold">Y</span>
                                <input type="number" step="0.5" v-model.number="cubePosition.y" class="bg-noir-darker text-white border border-noir-dark rounded px-2 py-1 text-xs w-full focus:outline-none focus:border-noir-accent">
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-noir-muted w-4 text-xs font-bold">Z</span>
                                <input type="number" step="0.5" v-model.number="cubePosition.z" class="bg-noir-darker text-white border border-noir-dark rounded px-2 py-1 text-xs w-full focus:outline-none focus:border-noir-accent">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
/* Ensure the canvas doesn't have a background if we want the image to show through */
:deep(canvas) {
    background: transparent !important;
}
</style>