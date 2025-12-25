<script setup>
import { ref, onMounted, onUnmounted, computed, reactive, watch } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import axios from '../../axios';
import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader';
import { useToast } from '../../composables/useToast';

const toast = useToast();

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
const isEditingSpawnPoints = ref(false);
const spawnPoints = ref([]);
const spawnPointMarkers = []; // Non-reactive array of Three.js objects

// Three.js refs
const canvasContainer = ref(null);
let renderer, scene, camera, clock;
let currentGltf = null;
let animationFrameId = null;
let testPointerMesh = null;
const raycaster = new THREE.Raycaster();
const mouse = new THREE.Vector2();

const VIEW_WIDTH = 1216;
const VIEW_HEIGHT = 832;
const ASPECT_RATIO = VIEW_WIDTH / VIEW_HEIGHT;

// Cube Controls
const pointerPosition = reactive({ x: 0, y: 0, z: 0 });

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

        // Load spawn points for this specific sector
        const allSpawnPoints = locatieData.value.spawn_points || {};
        spawnPoints.value = allSpawnPoints[sectorId] || [];

        if (scene) {
            visualizeSpawnPoints();
        }
    } catch (e) {
        console.error("Failed to fetch data", e);
        error.value = "Failed to load location or sector data.";
    } finally {
        loading.value = false;
    }
};

const saveSpawnPoints = async () => {
    try {
        const allSpawnPoints = locatieData.value.spawn_points || {};
        allSpawnPoints[sectorId] = spawnPoints.value;

        await axios.put(`/api/locaties/${locatieId}`, {
            spawn_points: allSpawnPoints
        });

        toast.success('SPAWN_POINTS_SAVED');
    } catch (e) {
        console.error(e);
        toast.error('FAILED_TO_SAVE_SPAWN_POINTS');
    }
};

const addSpawnPoint = (type) => {
    const newPoint = {
        id: Date.now(),
        type,
        x: pointerPosition.x,
        y: pointerPosition.y-0.,
        z: pointerPosition.z
    };
    spawnPoints.value.push(newPoint);
    visualizeSpawnPoints();
};

const removeSpawnPoint = (id) => {
    spawnPoints.value = spawnPoints.value.filter(p => p.id !== id);
    visualizeSpawnPoints();
};

const visualizeSpawnPoints = () => {
    // Clear old markers
    spawnPointMarkers.forEach(m => scene.remove(m));
    spawnPointMarkers.length = 0;

    const VISUAL_OFFSET = 0.6; // Float markers above the ground point

    spawnPoints.value.forEach(p => {
        let color = 0x00ff00; // Personage
        if (p.type === 'aanwijzing') color = 0xffff00;
        if (p.type === 'vehicle') color = 0x0000ff;

        const geometry = new THREE.SphereGeometry(0.15, 16, 16);
        const material = new THREE.MeshStandardMaterial({
            color,
            emissive: color,
            emissiveIntensity: 0.5,
            transparent: true,
            opacity: 0.8
        });
        const marker = new THREE.Mesh(geometry, material);
        // Position the sphere floating above the saved coordinate
        marker.position.set(p.x, p.y + VISUAL_OFFSET, p.z);
        marker.name = `spawnpoint-${p.id}`;

        // Add a line pointing down to the actual ground coordinate
        const lineGeom = new THREE.CylinderGeometry(0.01, 0.01, VISUAL_OFFSET);
        const lineMat = new THREE.MeshBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.4 });
        const line = new THREE.Mesh(lineGeom, lineMat);
        line.position.y = -VISUAL_OFFSET / 2;
        marker.add(line);

        // Add a ground ring target for better reference
        const ringGeom = new THREE.RingGeometry(0.15, 0.18, 32);
        ringGeom.rotateX(-Math.PI / 2);
        const ringMat = new THREE.MeshBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.5, side: THREE.DoubleSide });
        const ring = new THREE.Mesh(ringGeom, ringMat);
        ring.position.y = -VISUAL_OFFSET;
        marker.add(ring);

        scene.add(marker);
        spawnPointMarkers.push(marker);
    });
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

    const geometry = new THREE.ConeGeometry(0.3, 1, 16);
    geometry.rotateX(Math.PI); // Point peak down
    geometry.translate(0, 0.5, 0); // Move peak to local origin
    const material = new THREE.MeshStandardMaterial({ color: 0xff0000 });
    testPointerMesh = new THREE.Mesh(geometry, material);
    testPointerMesh.position.set(0, 0, 0);
    testPointerMesh.name = "test-cone";
    scene.add(testPointerMesh);

    pointerPosition.x = testPointerMesh.position.x;
    pointerPosition.y = testPointerMesh.position.y;
    pointerPosition.z = testPointerMesh.position.z;

    const gridHelper = new THREE.GridHelper(10, 10);
    scene.add(gridHelper);

    visualizeSpawnPoints();
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

            if (testPointerMesh) {
                testPointerMesh.position.set(center.x, box.max.y, center.z);
                pointerPosition.x = testPointerMesh.position.x;
                pointerPosition.y = testPointerMesh.position.y;
                pointerPosition.z = testPointerMesh.position.z;
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
    if (testPointerMesh) {
        testPointerMesh.position.set(pointerPosition.x, pointerPosition.y, pointerPosition.z);
    }
};

watch(pointerPosition, () => {
    updateCubePos();
});

const onScroll = (e) => {
    e.preventDefault();
    if (e.shiftKey) {
        const delta = e.deltaX > 0 ? 0.5 : -0.5;
        pointerPosition.x += delta;
    } else {
        const delta = e.deltaY > 0 ? 0.5 : -0.5;
        pointerPosition.z += delta;
    }
};
</script>

<template>
    <div class="min-h-screen bg-noir-darker p-8">
        <div class="max-w-[1300px] mx-auto">
            <div class="flex items-center text-sm text-noir-muted mb-4">
                <RouterLink to="/locaties" class="hover:text-white">&lt; LOCATIES</RouterLink>
                <span class="mx-2">/</span>
                <RouterLink :to="`/locaties/${locatieId}`" class="hover:text-white">&lt;TERUG_NAAR_LOCATIE</RouterLink>
                <span class="mx-2">/</span>
                <span class="text-white">3D_CONTROLE_VIEUW</span>

                <div class="ml-auto flex gap-4">
                    <button
                        @click="isEditingSpawnPoints = !isEditingSpawnPoints"
                        class="btn btn--small"
                        :class="isEditingSpawnPoints ? 'btn--warning' : 'btn--secondary'"
                    >
                        {{ isEditingSpawnPoints ? 'KLAAR' : 'WIJZIG_SPAWNPOINTS' }}
                    </button>
                    <button v-if="isEditingSpawnPoints" @click="saveSpawnPoints" class="btn btn--small btn--success">
                        BEWAAR_WIJZIGINGEN
                    </button>
                </div>
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

                <!-- Spawnpoint Editor Panel -->
                <div v-if="isEditingSpawnPoints" class="mt-6 w-full panel bg-noir-darker/50 border-noir-warning/30">
                    <div class="flex justify-between items-center mb-4 border-b border-noir-dark pb-2">
                        <h3 class="text-noir-warning font-bold uppercase tracking-widest text-sm">Spawnpoint_Editor</h3>
                        <div class="flex gap-2">
                            <button @click="addSpawnPoint('personage')" class="btn btn--small btn--success">+ PERSONAGE</button>
                            <button @click="addSpawnPoint('aanwijzing')" class="btn btn--small btn--warning">+ PROP</button>
                            <button @click="addSpawnPoint('vehicle')" class="btn btn--small btn--primary">+ VEHICLE</button>
                        </div>
                    </div>

                    <div v-if="spawnPoints.length === 0" class="text-center py-4 text-noir-muted italic">
                        NO_SPAWNPOINTS_DEFINED
                    </div>
                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        <div v-for="point in spawnPoints" :key="point.id" class="flex items-center justify-between bg-noir-panel p-2 rounded border border-noir-dark text-[10px]">
                            <div class="flex items-center gap-2">
                                <span :class="{
                                    'text-noir-success': point.type === 'personage',
                                    'text-noir-warning': point.type === 'aanwijzing',
                                    'text-noir-accent': point.type === 'vehicle'
                                }">⬤</span>
                                <span class="uppercase font-bold">{{ point.type }}</span>
                                <span class="text-noir-muted">({{ point.x.toFixed(1) }}, {{ point.y.toFixed(1) }}, {{ point.z.toFixed(1) }})</span>
                            </div>
                            <div class="flex gap-1">
                                <button @click="removeSpawnPoint(point.id)" class="text-noir-danger hover:text-white">✕</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6 w-full text-xs">
                     <div class="bg-noir-panel p-4 border border-noir-dark rounded">
                        <h3 class="text-white font-bold mb-2 uppercase border-b border-noir-dark pb-1 text-[10px]">INFO</h3>
                        <p class="text-noir-text">LOC: {{ locatieData.naam }}</p>
                        <p class="text-noir-text">SEC: {{ sectorData.naam }}</p>
                    </div>
                    <div class="bg-noir-panel p-4 border border-noir-dark rounded">
                        <h3 class="text-white font-bold mb-2 uppercase border-b border-noir-dark pb-1 text-[10px]">RODE KEGEL</h3>
                        <ul class="text-noir-text space-y-1">
                            <li>• Scroll: Z-DIEPTE</li>
                            <li>• Shift + Scroll: X-AS</li>
                        </ul>
                    </div>
                    <div class="bg-noir-panel p-4 border border-noir-dark rounded">
                        <h3 class="text-white font-bold mb-2 uppercase border-b border-noir-dark pb-1 text-[10px]">DEBUG_POINTER</h3>
                        <div class="flex gap-2">
                            <span>X: {{ pointerPosition.x.toFixed(2) }}</span>
                            <span>Y: {{ pointerPosition.y.toFixed(2) }}</span>
                            <span>Z: {{ pointerPosition.z.toFixed(2) }}</span>
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
