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
const spawnOptions = ref([]); // From instellingen
const personages = ref([]); // From api

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

    // Use setTimeout or nextTick to ensure the DOM has updated after loading is set to false
    setTimeout(() => {
        initThree();
        if (glbUrl.value) {
            loadGLB();
        }
    }, 100);
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
        const [locRes, secRes, instRes, persRes] = await Promise.all([
            axios.get(`/api/locaties/${locatieId}`),
            axios.get(`/api/sectoren/${sectorId}`),
            axios.get('/api/instellingen'),
            axios.get('/api/personages')
        ]);
        locatieData.value = locRes.data;
        sectorData.value = secRes.data;
        personages.value = persRes.data;

        // Handle object-based response from Instelling pluck
        const optionsStr = instRes.data.spawn_options;
        if (optionsStr) {
            try {
                spawnOptions.value = JSON.parse(optionsStr);
            } catch(e) {
                console.error("Failed to parse spawn_options", e);
            }
        }

        // Load spawn points for this specific sector
        const allSpawnPoints = locatieData.value.spawn_points || {};
        spawnPoints.value = allSpawnPoints[sectorId] || allSpawnPoints[Number(sectorId)] || [];

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
        name: type === 'waypoint' ? (spawnOptions.value[0]?.id || '') : '',
        personage_id: type === 'personage' ? (personages.value[0]?.id || null) : null,
        aanwijzing_id: type === 'aanwijzing' ? (locatieData.value?.aanwijzingen?.[0]?.id || null) : null,
        x: pointerPosition.x,
        y: pointerPosition.y,
        z: pointerPosition.z,
        direction: 0,
        scale: 1.0
    };
    spawnPoints.value.push(newPoint);
    visualizeSpawnPoints();
};

const removeSpawnPoint = (id) => {
    spawnPoints.value = spawnPoints.value.filter(p => p.id !== id);
    visualizeSpawnPoints();
};

const visualizeSpawnPoints = () => {
    if (!scene) return;
    // Clear old markers
    spawnPointMarkers.forEach(m => {
        if (m.parent) m.parent.remove(m);
        else scene.remove(m);
    });
    spawnPointMarkers.length = 0;

    const VISUAL_OFFSET = 0.6; // Float markers above the ground point

    spawnPoints.value.forEach(p => {
        let color = 0x00ff00; // Personage
        if (p.type === 'aanwijzing') color = 0xffff00;
        if (p.type === 'waypoint') color = 0x00ffff;
        if (p.name === 'spinner' || p.name === 'landing') color = 0x0000ff;

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

        // Apply direction and scale
        const scaleValue = p.scale || 1.0;
        marker.scale.set(scaleValue, scaleValue, scaleValue);
        marker.rotation.y = THREE.MathUtils.degToRad(p.direction || 0);

        // Add a direction indicator (arrow) pointing forward (+Z)
        const arrowDir = new THREE.Vector3(0, 0, 1);
        const arrowHelper = new THREE.ArrowHelper(arrowDir, new THREE.Vector3(0, 0, 0), 0.5, 0xffffff);
        marker.add(arrowHelper);

        // Add a line pointing down to the actual ground coordinate
        const lineGeom = new THREE.CylinderGeometry(0.01 / scaleValue, 0.01 / scaleValue, VISUAL_OFFSET / scaleValue);
        const lineMat = new THREE.MeshBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.4 });
        const line = new THREE.Mesh(lineGeom, lineMat);
        line.position.y = -VISUAL_OFFSET / 2 / scaleValue;
        marker.add(line);

        // Add a ground ring target for better reference
        const ringGeom = new THREE.RingGeometry(0.15 / scaleValue, 0.18 / scaleValue, 32);
        ringGeom.rotateX(-Math.PI / 2);
        const ringMat = new THREE.MeshBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.5, side: THREE.DoubleSide });
        const ring = new THREE.Mesh(ringGeom, ringMat);
        ring.position.y = -VISUAL_OFFSET / scaleValue;
        marker.add(ring);

        scene.add(marker);
        spawnPointMarkers.push(marker);

        // Add Label
        const labelText = p.type === 'waypoint' ? p.name : (p.type === 'personage' ? getPersonageName(p.personage_id) : 'PROP');
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        canvas.width = 256;
        canvas.height = 64;
        context.fillStyle = 'rgba(0,0,0,0.6)';
        context.fillRect(0, 0, 256, 64);
        context.font = 'Bold 32px Arial';
        context.fillStyle = 'white';
        context.textAlign = 'center';
        context.fillText(labelText.toUpperCase(), 128, 44);

        const texture = new THREE.CanvasTexture(canvas);
        const spriteMaterial = new THREE.SpriteMaterial({ map: texture, transparent: true });
        const sprite = new THREE.Sprite(spriteMaterial);
        sprite.position.set(p.x, p.y + VISUAL_OFFSET + 0.4, p.z);
        sprite.scale.set(1.5, 0.375, 1);
        scene.add(sprite);
        spawnPointMarkers.push(sprite);
    });
};

const getPersonageName = (id) => {
    const p = personages.value.find(pers => pers.id === id);
    return p ? p.naam : 'UNKNOWN';
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
    if (!scene) {
        console.error("Cannot load GLB: Scene is not initialized.");
        return;
    }
    const loader = new GLTFLoader();
    // Remove crossOrigin as it can break loading if server headers aren't perfect

    loader.load(glbUrl.value, (gltf) => {
        if (!scene) return;
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

watch(spawnPoints, () => {
    visualizeSpawnPoints();
}, { deep: true });

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
                            <button @click="addSpawnPoint('waypoint')" class="btn btn--small btn--primary">+ WAYPOINT</button>
                        </div>
                    </div>

                    <div v-if="spawnPoints.length === 0" class="text-center py-4 text-noir-muted italic">
                        GEEN_SPAWNPOINTS
                    </div>
                    <div v-else class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div v-for="point in spawnPoints" :key="point.id" class="bg-noir-panel p-4 rounded border border-noir-dark shadow-lg">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center gap-2">
                                    <span :class="{
                                        'text-noir-success': point.type === 'personage',
                                        'text-noir-warning': point.type === 'aanwijzing',
                                        'text-noir-accent':  point.type === 'waypoint'
                                    }">⬤</span>
                                    <span class="uppercase font-bold text-xs tracking-tighter">{{ point.type }}</span>
                                </div>
                                <button @click="removeSpawnPoint(point.id)" class="text-noir-danger hover:text-white transition-colors">✕</button>
                            </div>

                            <div class="space-y-3">
                                <!-- Waypoint Name Selector -->
                                <div v-if="point.type === 'waypoint'">
                                    <label class="block text-[10px] text-noir-muted uppercase mb-1">Identificatie</label>
                                    <select v-model="point.name" class="w-full bg-noir-darker border border-noir-dark text-white p-1 rounded text-xs">
                                        <option v-for="opt in spawnOptions" :key="opt.id" :value="opt.id">{{ opt.label }} ({{ opt.id }})</option>
                                    </select>
                                </div>

                                <!-- Personage Selector -->
                                <div v-if="point.type === 'personage'">
                                    <label class="block text-[10px] text-noir-muted uppercase mb-1">Selecteer Personage</label>
                                    <select v-model="point.personage_id" class="w-full bg-noir-darker border border-noir-dark text-white p-1 rounded text-xs">
                                        <option v-for="pers in personages" :key="pers.id" :value="pers.id">{{ pers.naam }}</option>
                                    </select>
                                </div>

                                <!-- Aanwijzing Selector -->
                                <div v-if="point.type === 'aanwijzing'">
                                    <label class="block text-[10px] text-noir-muted uppercase mb-1">Selecteer Prop</label>
                                    <select v-model="point.aanwijzing_id" class="w-full bg-noir-darker border border-noir-dark text-white p-1 rounded text-xs">
                                        <option v-for="aan in locatieData.aanwijzingen" :key="aan.id" :value="aan.id">{{ aan.titel }}</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] text-noir-muted uppercase mb-1">Richting</label>
                                        <select v-model="point.direction" class="w-full bg-noir-darker border border-noir-dark text-white p-1 rounded text-xs">
                                            <option :value="0">0° (N)</option>
                                            <option :value="45">45° (NE)</option>
                                            <option :value="90">90° (E)</option>
                                            <option :value="135">135° (SE)</option>
                                            <option :value="180">180° (S)</option>
                                            <option :value="225">225° (SW)</option>
                                            <option :value="270">270° (W)</option>
                                            <option :value="315">315° (NW)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-noir-muted uppercase mb-1">Schaal</label>
                                        <select v-model="point.scale" class="w-full bg-noir-darker border border-noir-dark text-white p-1 rounded text-xs">
                                            <option v-for="s in [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 2.0]" :key="s" :value="s">{{ s.toFixed(1) }}x</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="flex gap-2 text-[9px] font-mono text-noir-muted bg-black/30 p-1 rounded">
                                    <span>X:{{ point.x.toFixed(1) }}</span>
                                    <span>Y:{{ point.y.toFixed(1) }}</span>
                                    <span>Z:{{ point.z.toFixed(1) }}</span>
                                </div>
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
