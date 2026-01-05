<script setup>
import { ref, onMounted, onUnmounted, computed, reactive, watch } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import axios from '../../axios';
import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader';
import { useToast } from '../../composables/useToast';
import { useDataRobustness } from '../../composables/useDataRobustness';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const { fetchData: fetchRobustData, resolveAssetUrl, slugify, getCharacterGlbUrl } = useDataRobustness();

const toast  = useToast();
const route  = useRoute();
const router = useRouter();
const sectorId = route.params.id;

// Data refs
const sectorData = ref(null);
const scenesInSector = ref([]);
const currentScene = ref(null);
const settings = ref({});
const gedragingen = ref([]);
const gameState = reactive({
    tags: []
});
const loading = ref(true);
const error = ref(null);

// Dialogue & Behavior System
const activeDialogue = ref(null);
const currentNodeId = ref('start');
const typewriterText = ref('');
const showDialogueOptions = ref(false);
const typewriterInterval = ref(null);
const dialogueNPCName = ref('');
let   dialogueResolve = null;

const spawnedNPCs = reactive({});
const npcModes = reactive({}); // Stores: 'HIDDEN', 'IDLE', 'SEQUENCE'
const isBehaviorActive = ref(false); // Global behavior lock
const lastTriggeredGateway = ref(null);
const lastExecutedBehaviorGateway = ref(null);



// Three.js refs
const canvasContainer = ref(null);
let renderer, scene, camera, clock;
let ambientLight, sun;
let currentGltf = null;
let animationFrameId = null;
const raycaster = new THREE.Raycaster();
const mouse = new THREE.Vector2();

// Movement / Character refs
let playableCharacter = null;
let vehicle = null;
const isWalking = ref(false);
const targetPosition = new THREE.Vector3();
const characterSpeed = 2.0;
const landingDone = ref(false);
let targetPointMesh = null;
const pendingGateway = ref(null);
const currentLoadingSceneId = ref(null);

const activeGateway = computed(() => getGatewayAtPosition());

const sceneTriggers = computed(() => currentScene.value?.gateways || []);
const behaviorLog = ref([]);

const activeBehaviors = computed(() => {
    if (!currentScene.value?.gateways) return [];
    const behaviorIds = [...new Set(currentScene.value.gateways.map(gw => gw.gedrag_id).filter(id => id))];
    return gedragingen.value.filter(g => behaviorIds.includes(g.id));
});



// Animation refs
let characterMixer = null;
const characterActions = {};
let activeAction = null;
const isCaution = ref(false);

// Scaling & Visibility refs
const characterScale   = ref(0.5);
const vehicleScale     = ref(0.5);
const show3DHelpers    = ref(false);
const sunIntensity     = ref(1.0);
const ambientIntensity = ref(0.8);

const VIEW_WIDTH = 1216;
const VIEW_HEIGHT = 832;
const ASPECT_RATIO = VIEW_WIDTH / VIEW_HEIGHT;

// slugify and getCharacterGlbUrl are now imported from useDataRobustness

const getImageUrl = (path) => {
    return resolveAssetUrl(path);
};

const getGlbUrl = (location, sector) => {
    if (!location || !sector) return '';
    const sectorSlug   = slugify(sector.naam);
    const locationSlug = slugify(location.naam);
    return resolveAssetUrl(`glb/${sectorSlug}--${locationSlug}.glb`);
};

// getCharacterGlbUrl is now imported from useDataRobustness

const getVehicleGlbUrl = (path) => {
    if (!path) return '';
    // Strip 'artwork/' if it's at the start, as 3D is in glb/
    let cleanPath = path.replace(/^artwork\//, '');
    return resolveAssetUrl(cleanPath);
};

// Responsive Logic
const containerWidth  = ref(VIEW_WIDTH);
const containerHeight = ref(VIEW_HEIGHT);

const updateDimensions = () => {
    const verticalOffset = 200;
    const sidebarWidth = window.innerWidth >= 1024 ? 384 + 32 : 0; // lg:w-96 (384px) + gap (32px)

    // Respect the max-w-[1300px] constraint
    const effectiveTotalWidth = Math.min(window.innerWidth, 1300);
    const maxWidth = effectiveTotalWidth - 64 - sidebarWidth;
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
    if (currentScene.value) {
        await loadSceneGLB(currentScene.value);
    }
});

onUnmounted(() => {
    window.removeEventListener('resize', updateDimensions);
    if (animationFrameId) cancelAnimationFrame(animationFrameId);
    if (renderer) renderer.dispose();
});

const fetchData = async () => {
    try {
        const [sectorRes, settingsRes, gedragRes] = await Promise.all([
            axios.get(`/api/sectoren/${sectorId}`),
            axios.get('/api/instellingen'),
            axios.get('/api/gedrag')
        ]);
        sectorData.value     = sectorRes.data;
        scenesInSector.value = sectorData.value.scenes || [];
        settings.value       = settingsRes.data;
        gedragingen.value    = gedragRes.data;

        // Priority 1: Specific Scene ID from query parameter (for direct emulation from 3D view)
        const querySceneId = route.query.sceneId;
        if (querySceneId) {
            const scRes = await axios.get(`/api/scenes/${querySceneId}`);
            const locRes = await axios.get(`/api/locaties/${scRes.data.locatie_id}`);
            currentScene.value = { ...scRes.data, location: locRes.data };
            console.log(`[EMULATE] Loading specific scene from query: ${querySceneId}`);
        }

        // Priority 2: Find start scene (scene with vehicle spawn point)
        if (!currentScene.value) {
            for (const s of scenesInSector.value) {
            const locRes = await axios.get(`/api/locaties/${s.locatie_id}`);
            const loc = locRes.data;
            const allSpawnPoints = loc.spawn_points || {};
            const spawnPoints = allSpawnPoints[sectorId] || allSpawnPoints[Number(sectorId)] || [];
            if (spawnPoints.some(p => p.type === 'vehicle')) {
                const scRes = await axios.get(`/api/scenes/${s.id}`);
                currentScene.value = { ...scRes.data, location: loc };
                break;
            }
            }
        }

        if (!currentScene.value && scenesInSector.value.length > 0) {
            // Fallback to first scene
            const s = scenesInSector.value[0];
            const locRes = await axios.get(`/api/locaties/${s.locatie_id}`);
            const scRes = await axios.get(`/api/scenes/${s.id}`);
            currentScene.value = { ...scRes.data, location: locRes.data };
        }


    } catch (e) {
        console.error("Failed to fetch data", e);
        error.value = t('map.load_error');
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
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    canvasContainer.value.appendChild(renderer.domElement);

    camera = new THREE.PerspectiveCamera(45, ASPECT_RATIO, 0.1, 1000);
    camera.position.set(5, 5, 5);
    camera.lookAt(0, 0, 0);

    clock = new THREE.Clock();

    ambientLight = new THREE.AmbientLight(0xffffff, ambientIntensity.value);
    scene.add(ambientLight);
    ambientLight.name = "ambient-light";

    sun = new THREE.DirectionalLight(0xffffff, sunIntensity.value);
    sun.position.set(5, 10, 5);
    sun.castShadow = true;
    sun.shadow.mapSize.width  = 1024;
    sun.shadow.mapSize.height = 1024;
    sun.shadow.camera.near    = 0.5;
    sun.shadow.camera.far     = 50;
    sun.shadow.camera.left    = -10;
    sun.shadow.camera.right   = 10;
    sun.shadow.camera.top     = 10;
    sun.shadow.camera.bottom  = -10;
    scene.add(sun);
    sun.name = "sun-light";

    // Target Marker
    const markerGeom = new THREE.RingGeometry(0.2, 0.25, 32);
    markerGeom.rotateX(-Math.PI / 2);
    targetPointMesh = new THREE.Mesh(markerGeom, new THREE.MeshBasicMaterial({ color: 0x00ffff, transparent: true, opacity: 0.8 }));
    targetPointMesh.userData.isHelper = true; // Tag as helper
    targetPointMesh.visible = false;
    scene.add(targetPointMesh);

    animate();
};

const loadSceneGLB = (sceneData, targetSpawnLabel = null) => {
    return new Promise((resolve) => {
        if (!sceneData || !sceneData.location) {
            resolve();
            return;
        }

        const url = getGlbUrl(sceneData.location, sectorData.value);
        if (!url) {
            resolve();
            return;
        }

        currentLoadingSceneId.value = sceneData.id;

        // 1. Cleanup before loading
        if (currentGltf) { scene.remove(currentGltf); currentGltf = null; }
        if (playableCharacter) { scene.remove(playableCharacter); playableCharacter = null; }
        if (vehicle) { scene.remove(vehicle); vehicle = null; }

        // Remove all NPCs
        Object.values(spawnedNPCs).forEach(npc => {
            if (npc.mesh) scene.remove(npc.mesh);
            if (npc.mixer) npc.mixer = null;
        });
        for (const key in spawnedNPCs) delete spawnedNPCs[key];
        for (const key in npcModes) delete npcModes[key];

        // Thorough cleanup: remove anything that isn't a core light
        for (let i = scene.children.length - 1; i >= 0; i--) {
            const child = scene.children[i];
            if (child.name !== 'ambient-light' && child.name !== 'sun-light' && child.name !== 'target-marker' && child !== targetPointMesh && child !== currentGltf) {
                if (child.isMesh || child.isGroup) {
                    scene.remove(child);
                }
            }
        }



        const loader = new GLTFLoader();
        loader.load(url, (gltf) => {
            currentGltf = gltf.scene;
            scene.add(currentGltf);

            let fspyCamera = null;
            currentGltf.traverse((child) => {
                if (child.name.includes('fspy') && child.isCamera) fspyCamera = child;
                if (child.isMesh) {
                    const isFloor = child.name.toLowerCase() === 'floor' || child.name.toLowerCase() === 'plane';

                    if (isFloor) {
                        child.material = new THREE.ShadowMaterial({
                            opacity: show3DHelpers.value ? 0.3 : 0.4
                        });
                        child.receiveShadow = true;
                        child.name = 'floor';
                    } else {
                        // Occlusion Mask: Write to depth buffer
                        child.material = new THREE.MeshBasicMaterial({
                            color: 0xff00ff,
                            transparent: show3DHelpers.value,
                            opacity: show3DHelpers.value ? 0.3 : 0,
                            colorWrite: show3DHelpers.value,
                        });
                        // Depth rendering is crucial for masking
                        child.material.depthWrite = true;
                        child.material.depthTest = true;
                        child.userData.isHelper = true; // Tag as helper so we don't walk on it
                    }
                }
            });

            if (fspyCamera) {
                camera = fspyCamera;
                camera.aspect = ASPECT_RATIO;
                camera.updateProjectionMatrix();
            }

            // 2. Identify Spawn Points
            const currentSectorId = route.params.id;
            const allSpawnPoints = sceneData.location.spawn_points || {};
            const spawnPoints = allSpawnPoints[currentSectorId] || allSpawnPoints[Number(currentSectorId)] || [];

            // Look for specific named spawn point from gateway
            const specificSpawn = targetSpawnLabel ? spawnPoints.find(p => p.name === targetSpawnLabel || p.personage_id === targetSpawnLabel) : null;

            // Vehicle: search for waypoint named 'spinner' (user's fixed preference) or old 'vehicle' type
            const vehicleSpawn = spawnPoints.find(p => p.type === 'waypoint' && p.name === 'spinner') ||
                                spawnPoints.find(p => p.type === 'vehicle');

            // Spawn logic:
            // 1. If we came through a gateway with a specific label, use that.
            // 2. If we are landing the vehicle for the first time, look for 'landing' waypoint.
            // 3. Fallback to 'personage' type.
            const charSpawnPoint = specificSpawn ||
                                   (!landingDone.value && spawnPoints.find(p => p.type === 'waypoint' && p.name === 'landing')) ||
                                   spawnPoints.find(p => p.type === 'personage');

            // 3. Sequentially spawn vehicle then character then props
            const vehiclePromise = vehicleSpawn
                ? spawnVehicle(vehicleSpawn, !landingDone.value)
                : Promise.resolve();

            vehiclePromise.then(() => {
                if (currentLoadingSceneId.value !== sceneData.id) return resolve();

                const charPos = charSpawnPoint ||
                                (vehicleSpawn ? { x: vehicleSpawn.x + 2, y: vehicleSpawn.y, z: vehicleSpawn.z } : { x: 0, y: 0, z: 0 });

                spawnCharacter(charPos, sceneData.id).then(() => {
                    if (currentLoadingSceneId.value !== sceneData.id) return resolve();
                    const spList = sceneData.scene_personages || sceneData.scenePersonages || [];
                    spawnNPCs(spList, sceneData.id).then(() => {
                        if (currentLoadingSceneId.value !== sceneData.id) return resolve();
                        spawnProps(spawnPoints, sceneData.id).then(resolve);
                    });
                });
            });


        }, undefined, () => resolve());
    });
};

const spawnVehicle = (spawnPoint, animate = true) => {
    return new Promise((resolve) => {
        const vehicleUrl = getVehicleGlbUrl(settings.value.spinner2049);
        if (!vehicleUrl) {
            resolve();
            return;
        }

        const loader = new GLTFLoader();
        loader.load(vehicleUrl, (gltf) => {
            if (currentLoadingSceneId.value !== currentScene.value.id) {
                resolve();
                return;
            }
            vehicle = gltf.scene;

            const targetY = spawnPoint.y;
            const scale = spawnPoint.scale || vehicleScale.value;
            vehicle.scale.set(scale, scale, scale);
            vehicle.rotation.y = THREE.MathUtils.degToRad(spawnPoint.direction || 0);
            scene.add(vehicle);

            if (animate) {
                const startY = 30; // Start a bit higher
                const duration = 2500; // 2.5 seconds for a graceful descent
                const startTime = performance.now();
                vehicle.position.set(spawnPoint.x, startY, spawnPoint.z);

                const animateLanding = (currentTime) => {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);

                    // Quadratic ease-out: progress * (2 - progress)
                    const ease = progress * (2 - progress);

                    vehicle.position.y = startY - (startY - targetY) * ease;

                    if (progress < 1) {
                        requestAnimationFrame(animateLanding);
                    } else {
                        vehicle.position.y = targetY;
                        landingDone.value = true;
                        resolve();
                    }
                };
                requestAnimationFrame(animateLanding);
            } else {
                vehicle.position.set(spawnPoint.x, targetY, spawnPoint.z);
                resolve();
            }
        });
    });
};

const spawnCharacter = (spawnPoint, sceneId) => {
    return new Promise((resolve) => {
        const charUrl = getCharacterGlbUrl(settings.value.playable);
        if (!charUrl) {
            resolve();
            return;
        }

        const loader = new GLTFLoader();
        loader.load(charUrl, (gltf) => {
            if (currentLoadingSceneId.value !== sceneId) {
                resolve();
                return;
            }

            if (playableCharacter) {
                scene.remove(playableCharacter);
                if (characterMixer) characterMixer = null;
            }

            playableCharacter = gltf.scene;

            // Dynamic Scaling Logic: Measure model height
            const bbox = new THREE.Box3().setFromObject(playableCharacter);
            const size = new THREE.Vector3();
            bbox.getSize(size);
            const rawHeight = size.y;

            let scale = spawnPoint.scale || characterScale.value;

            // If the model is huge (e.g. > 20 units), it's likely the animated version needing 0.01 multiplier
            if (rawHeight > 20) {
                console.log(`Dynamic scaling: Huge model detected (height: ${rawHeight.toFixed(2)}). Applying 0.01 correction.`);
                scale = scale * 0.01;
            } else {
                console.log(`Dynamic scaling: Standard model detected (height: ${rawHeight.toFixed(2)}). Using base scale: ${scale}.`);
            }

            playableCharacter.scale.set(scale, scale, scale);
            playableCharacter.position.set(spawnPoint.x, spawnPoint.y, spawnPoint.z);
            playableCharacter.rotation.y = THREE.MathUtils.degToRad(spawnPoint.direction || 0);
            scene.add(playableCharacter);

            // Setup Animations
            if (gltf.animations && gltf.animations.length > 0) {
                characterMixer = new THREE.AnimationMixer(playableCharacter);

                gltf.animations.forEach(clip => {
                    const name = clip.name.toLowerCase();
                    let actionKey = null;

                    if (name.startsWith('walk')) actionKey = 'walk';
                    else if (name.startsWith('idle')) actionKey = 'idle';
                    else if (name.startsWith('caut')) actionKey = 'caution';

                    if (actionKey) {
                        characterActions[actionKey] = characterMixer.clipAction(clip);
                    }
                });

                // Default to Idle
                if (characterActions['idle']) {
                    playAnimation('idle');
                }
            }

            // Strip lights and set shadow casting
            playableCharacter.userData.isCharacter = true;
            playableCharacter.traverse(child => {
                child.userData.isCharacter = true;
                if (child.isLight) {
                    child.visible = false;
                }
                if (child.isMesh) {
                    child.castShadow = true;
                }
            });


            resolve();
        });
    });
};

const playAnimation = (name, duration = 0.5) => {
    const nextAction = characterActions[name];
    if (!nextAction || nextAction === activeAction) return;

    if (activeAction) {
        nextAction.reset();
        nextAction.setEffectiveTimeScale(1);
        nextAction.setEffectiveWeight(1);
        nextAction.crossFadeFrom(activeAction, duration, true);
        nextAction.play();
    } else {
        nextAction.play();
    }
    activeAction = nextAction;
};

// Animation Watchers
watch([isWalking, isCaution], ([walking, caution]) => {
    if (!characterMixer) return;

    if (walking) {
        playAnimation('walk');
    } else if (caution) {
        playAnimation('caution');
    } else {
        playAnimation('idle');
    }
});

const props = []; // Track spawned props for cleanup

const spawnProps = async (spawnPoints, sceneId) => {
    // Cleanup old props
    props.forEach(p => scene.remove(p));
    props.length = 0;

    const propSpawnPoints = spawnPoints.filter(p => p.type === 'aanwijzing');
    if (propSpawnPoints.length === 0) return;

    const loader = new GLTFLoader();

    for (const p of propSpawnPoints) {
        if (currentLoadingSceneId.value !== sceneId) break;

        // Find the aanwijzing data to get its GLB or visual representation
        try {
            // Ideally we have the aanwijzingen loaded or can fetch by ID
            // For now, let's assume we might need to fetch it or it's provided
            // If we don't have a specific GLB for each prop yet, we might skip or use a placeholder
            // But let's check if currentScene.location.aanwijzingen is available
            const aanwijzing = currentScene.value.location.aanwijzingen?.find(a => a.id === p.aanwijzing_id);
            if (aanwijzing && aanwijzing.glb_pad) {
                const url = getImageUrl(aanwijzing.glb_pad);
                const gltf = await new Promise((res, rej) => loader.load(url, res, undefined, rej));
                const propMesh = gltf.scene;
                const scale = p.scale || 1.0;
                propMesh.scale.set(scale, scale, scale);
                propMesh.position.set(p.x, p.y, p.z);
                propMesh.rotation.y = THREE.MathUtils.degToRad(p.direction || 0);
                scene.add(propMesh);
                props.push(propMesh);
            }
        } catch (e) {
            console.warn("Failed to spawn prop", p.id, e);
        }
    }
};

const spawnNPCs = async (scenePersonages, sceneId) => {
    // Cleanup old NPCs (redundant but safe)
    if (sceneId === currentLoadingSceneId.value) {
        // ... cleanup already happened in loadSceneGLB
    }

    if (!scenePersonages || scenePersonages.length === 0) return;

    const currentSectorId = route.params.id;
    const allSpawnPoints = currentScene.value.location?.spawn_points || {};
    const spawnPoints = allSpawnPoints[currentSectorId] || allSpawnPoints[Number(currentSectorId)] || [];

    for (const sp of scenePersonages) {
        try {
            if (currentLoadingSceneId.value !== sceneId) break;
            const p = sp.personage;
            if (!p) continue;

            // Find assigned spawn point
            const spawnPos = spawnPoints.find(point => point.name === sp.spawn_point_name || point.id === sp.spawn_point_name)
                          || spawnPoints.find(point => point.type === 'personage')
                          || { x: 0, y: 0, z: 0 };

            const charUrl = getCharacterGlbUrl(p.naam);
            if (!charUrl) continue;

            const loader = new GLTFLoader();
            const gltf = await new Promise((res, rej) => loader.load(charUrl, res, undefined, rej));

            if (currentLoadingSceneId.value !== sceneId) break;

            const mesh = gltf.scene;


            // Apply scaling
            const bbox = new THREE.Box3().setFromObject(mesh);
            const size = new THREE.Vector3();
            bbox.getSize(size);
            let scale = spawnPos.scale || characterScale.value;
            if (size.y > 20) scale *= 0.01;
            mesh.scale.set(scale, scale, scale);
            mesh.position.set(spawnPos.x, spawnPos.y, spawnPos.z);
            mesh.rotation.y = THREE.MathUtils.degToRad(spawnPos.direction || 0);

            scene.add(mesh);

            let mixer = null;
            let actions = {};
            if (gltf.animations?.length > 0) {
                mixer = new THREE.AnimationMixer(mesh);
                gltf.animations.forEach(clip => {
                    const name = clip.name.toLowerCase();
                    if (name.startsWith('walk')) actions.walk = mixer.clipAction(clip);
                    if (name.startsWith('idle')) actions.idle = mixer.clipAction(clip);
                });
                if (actions.idle) actions.idle.play();
            }

            spawnedNPCs[p.id] = {
                id: p.id,
                name: p.naam,
                mesh: mesh,
                mixer: mixer,
                actions: actions,
                gedrag_id: sp.gedrag_id || sp.gedragId || null,
                targetPos: new THREE.Vector3(spawnPos.x, spawnPos.y, spawnPos.z),
                isWalking: false
            };
            npcModes[p.id] = 'IDLE';


            mesh.traverse(child => {
                child.userData.isCharacter = true;
                if (child.isLight) child.visible = false;
                if (child.isMesh) child.castShadow = true;
            });


        } catch (e) {
            console.error("Failed to spawn NPC", sp.personage?.naam, e);
        }
    }
};

// Watchers for real-time scaling

watch(characterScale, (newScale) => {
    if (playableCharacter) {
        playableCharacter.scale.set(newScale, newScale, newScale);
    }
});

watch(vehicleScale, (newScale) => {
    if (vehicle) {
        vehicle.scale.set(newScale, newScale, newScale);
    }
});

const onMapClick = (e) => {
    if (activeDialogue.value) return;
    if (!playableCharacter || !landingDone.value) return;


    const rect = renderer.domElement.getBoundingClientRect();
    const mouseX = ((e.clientX - rect.left) / rect.width) * 2 - 1;
    const mouseY = -((e.clientY - rect.top) / rect.height) * 2 + 1;

    // 1. Detect Gateway Click (Screen Space)
    const screenX = (mouseX + 1) / 2 * 100;
    const screenY = -(mouseY - 1) / 2 * 100;

    const clickedGateway = currentScene.value?.gateways?.find(gw =>
        screenX >= gw.x && screenX <= gw.x + gw.width &&
        screenY >= gw.y && screenY <= gw.y + gw.height
    );

    // 2. Navigation Target Detection
    let moveTarget = null;

    // A. Priority: Floor Raycasting
    raycaster.setFromCamera(new THREE.Vector2(mouseX, mouseY), camera);
    const intersects = raycaster.intersectObjects(scene.children, true);

    // Filter out characters AND helpers
    const validIntersects = intersects.filter(i =>
        !i.object.userData.isCharacter &&
        !i.object.userData.isHelper
    );

    const floorIntersect = validIntersects.find(i =>
        i.object.name.toLowerCase().includes('floor') ||
        i.object.name.toLowerCase().includes('plane') ||
        i.object.isMesh
    );

    if (floorIntersect) {

        moveTarget = floorIntersect.point.clone();
    } else if (clickedGateway) {
        // B. Fallback: Waypoints (ONLY if a gateway was clicked but no floor detected)
        const currentSectorId = route.params.id;
        const allSpawnPoints = currentScene.value.location?.spawn_points || {};
        const spawnPoints = allSpawnPoints[currentSectorId] || allSpawnPoints[Number(currentSectorId)] || [];
        const waypoints = spawnPoints.filter(p => p.type === 'waypoint');

        if (waypoints.length > 0) {
            let nearest = null;
            let minDist = Infinity;
            waypoints.forEach(wp => {
                const vec = new THREE.Vector3(wp.x, wp.y, wp.z);
                vec.project(camera);
                const wpX = (vec.x + 1) / 2 * 100;
                const wpY = -(vec.y - 1) / 2 * 100;
                const d = Math.sqrt(Math.pow(screenX - wpX, 2) + Math.pow(screenY - wpY, 2));
                if (d < minDist) {
                    minDist = d;
                    nearest = wp;
                }
            });
            if (nearest) {
                moveTarget = new THREE.Vector3(nearest.x, nearest.y, nearest.z);
            }
        }
    }


    // 3. Apply Navigation & Gateway logic
    if (moveTarget) {
        targetPosition.copy(moveTarget);
        isWalking.value = true;
        pendingGateway.value = clickedGateway || null;

        if (targetPointMesh) {
            targetPointMesh.position.copy(targetPosition);
            targetPointMesh.position.y += 0.01;
            targetPointMesh.visible = true;
        }
    } else {
        // Explicitly clear if clicking "outside" everything
        pendingGateway.value = null;
    }
};



const startDialogue = (dialoogId) => {

    return new Promise(async (resolve) => {
        try {
            dialogueResolve = resolve;
            const res = await axios.get(`/api/dialogen/${dialoogId}`);
            activeDialogue.value = res.data;
            dialogueNPCName.value = activeDialogue.value.personage?.naam || 'NPC';

            // Default to 'root', or fallback to first key
            const nodeKeys = Object.keys(activeDialogue.value.tree?.nodes || {});
            currentNodeId.value = nodeKeys.includes('root') ? 'root' : nodeKeys[0];

            console.log(`[DIALOGUE] Starting dialogue ${dialoogId} at node: ${currentNodeId.value}`);

            if (currentNodeId.value) {
                playNode(currentNodeId.value);
            } else {
                console.warn("[DIALOGUE] No nodes found in dialogue tree.");
                closeDialogue();
            }
        } catch (e) {
            console.error("Failed to load dialogue", e);
            resolve();
        }
    });
};

const playNode = (nodeId) => {
    if (nodeId === '[END]' || nodeId === 'END') {
        closeDialogue();
        return;
    }

    const node = activeDialogue.value.tree.nodes[nodeId];
    if (!node) {
        console.warn(`[DIALOGUE] Node ${nodeId} not found.`);
        return;
    }

    currentNodeId.value = nodeId;
    showDialogueOptions.value = false;

    typewriterText.value = '';
    if (typewriterInterval.value) clearInterval(typewriterInterval.value);
    let charIndex = 0;
    const fullText = node.text;
    typewriterInterval.value = setInterval(() => {
        typewriterText.value += fullText[charIndex];
        charIndex++;
        if (charIndex >= fullText.length) {
            clearInterval(typewriterInterval.value);
            showDialogueOptions.value = true;
        }
    }, 30);
};

const selectOption = async (option, actorId = null) => {
    if (option.actions?.length > 0) {
        for (const action of option.actions) {
            await runAction(action, actorId);
        }
    }
    if (option.actions?.some(a => a.type === 'END TALK')) {
        closeDialogue();
        return;
    }
    if (option.next) playNode(option.next);
    else closeDialogue();
};

const closeDialogue = () => {
    activeDialogue.value = null;
    currentNodeId.value = 'start';
    typewriterText.value = '';
    showDialogueOptions.value = false;
    if (typewriterInterval.value) clearInterval(typewriterInterval.value);
    if (dialogueResolve) {
        dialogueResolve();
        dialogueResolve = null;
    }
};

const isSwapping = ref(false);

const triggerGateway = async (gateway, isForced = false) => {
    if (isSwapping.value) return;

    const type = gateway.type || (gateway.target_scene_id ? 'gateway' : 'trigger');

    // 1. Behavioral Logic (SYSTEM_TRIGGER or Gateway with behavior)
    if (gateway.gedrag_id && (!isBehaviorActive.value || isForced)) {
        if (isForced || lastExecutedBehaviorGateway.value !== gateway) {

             const gedrag = gedragingen.value.find(g => g.id === gateway.gedrag_id);

             // Find matching spawned NPCs
             const spRelation = currentScene.value?.scene_personages || currentScene.value?.scenePersonages || [];
             const targetGedragId = String(gateway.gedrag_id);

             const sceneP = spRelation.filter(sp => String(sp.gedrag_id || sp.gedragId) === targetGedragId);
             const targetIds = sceneP.map(sp => String(sp.personage_id || sp.personageId));

             let actors = Object.values(spawnedNPCs).filter(n =>
                targetIds.includes(String(n.id)) ||
                String(n.gedrag_id) === targetGedragId
             );

             // FUZZY MATCH FALLBACK: If no actors found by ID, try matching NPC name into behavior name
             if (actors.length === 0 && gedrag) {
                 const behaviorName = gedrag.naam.toLowerCase();
                 actors = Object.values(spawnedNPCs).filter(n =>
                     behaviorName.includes(n.name.toLowerCase())
                 );
                 if (actors.length > 0) {
                     console.log(`[TRIGGER] Fuzzy-matched actors by name:`, actors.map(a => a.name));
                 }
             }



             if (gedrag && actors.length > 0) {
                 console.log(`[TRIGGER] Executing ${gedrag.naam} for actors:`, actors.map(a => a.name));
                 isBehaviorActive.value = true;
                 lastExecutedBehaviorGateway.value = gateway;

                 const logMsg = `${isForced ? 'MANUAL' : 'TRG'}: ${gateway.label || 'SYSTEM'} -> ${gedrag.naam}`;

                 behaviorLog.value.unshift({ time: new Date().toLocaleTimeString(), msg: logMsg });
                 if (behaviorLog.value.length > 5) behaviorLog.value.pop();

                 for (const actor of actors) {
                     npcModes[actor.id] = 'SEQUENCE';
                     actor.currentGedrag = gedrag.naam;
                     actor.activeGedragId = gedrag.id;
                 }

                 for (const [index, action] of (gedrag.acties || []).entries()) {
                     for (const actor of actors) {
                         actor.currentActionIndex = index;
                     }
                     const actionLog = `ACT [${index + 1}/${gedrag.acties.length}]: ${action.type}`;
                     behaviorLog.value.unshift({ time: new Date().toLocaleTimeString(), msg: actionLog });
                     if (behaviorLog.value.length > 8) behaviorLog.value.pop();

                     await Promise.all(actors.map(actor => runAction(action, actor.id)));
                 }

                 for (const actor of actors) {
                     npcModes[actor.id] = 'IDLE';
                     actor.currentGedrag = null;
                     actor.activeGedragId = null;
                     actor.currentActionIndex = -1;
                 }

                 isBehaviorActive.value = false;
             } else {
                 console.warn(`[TRIGGER] Failed to start behavior ${gateway.gedrag_id}:`, {
                     gedragFound: !!gedrag,
                     actorsFound: actors.length,
                     availableNPCs: Object.values(spawnedNPCs).map(n => ({ id: n.id, name: n.name, gedragId: n.gedrag_id }))
                 });
             }
        }
    }


    // 2. Scene Traversal Logic (Gateways)
    if (type === 'gateway' && gateway.target_scene_id) {
        if (!isForced && lastTriggeredGateway.value === gateway) return;

        isSwapping.value = true;
        lastTriggeredGateway.value = gateway;
        await swapScene(gateway);
        isSwapping.value = false;
        return;
    }

    lastTriggeredGateway.value = gateway;
};





const runAction = async (action, actorId = null) => {
    const type = action.type;
    const params = action.params || {};
    const value = action.value;
    const targetActor = actorId ? spawnedNPCs[actorId] : null;

    console.log(`Running action ${type} for ${actorId || 'player'}`);

    switch (type) {
        case 'SET GAME TAG':
            const tag = value || params.tag;
            if (tag && !gameState.tags.includes(tag)) {
                gameState.tags.push(tag);
                toast.success(`${t('map.tag_acquired')}: ${tag}`);
            }
            break;
        case 'REMOVE GAME TAG':
            const rtag = value || params.tag;
            gameState.tags = gameState.tags.filter(t => t !== rtag);
            break;
        case 'WAIT':
        case 'WAIT x SECONDS':
        case 'idle':
            const duration = parseFloat(params.duration || value || 2);
            await new Promise(res => setTimeout(res, duration * 1000));
            break;
        case 'WALK TO SPAWNPOINT':
        case 'walk_to':
        case 'WALK_TO':
            const spName = params.spawn_point || value;
            if (spName) {
                const currentSectorId = route.params.id;
                const locSpawnPoints = currentScene.value.location?.spawn_points || {};
                const spawnPoints = locSpawnPoints[currentSectorId] || locSpawnPoints[Number(currentSectorId)] || [];
                const sp = spawnPoints.find(p => p.name === spName || p.id === spName);
                if (sp) {
                    if (targetActor) {
                        targetActor.targetPos.set(sp.x, sp.y, sp.z);
                        targetActor.isWalking = true;
                        await new Promise(resolve => {
                            const check = setInterval(() => {
                                if (!targetActor.isWalking) { clearInterval(check); resolve(); }
                            }, 100);
                        });
                    } else if (actorId === null && playableCharacter) {
                        targetPosition.set(sp.x, sp.y, sp.z);
                        isWalking.value = true;
                        await new Promise(resolve => {
                            const check = setInterval(() => {
                                if (!isWalking.value) { clearInterval(check); resolve(); }
                            }, 100);
                        });
                    }
                }
            }
            break;
        case 'START DIALOG':
        case 'START_DIALOGUE':
        case 'talk':
            const dialId = params.dialoog_id || value;
            if (dialId) await startDialogue(dialId);
            break;
        case 'PLAY_ANIMATION':
        case 'ANIMATION':
            const animName = params.animation || value;
            if (targetActor && targetActor.actions[animName]) {
                const nextAction = targetActor.actions[animName];
                nextAction.reset().play();
                // Wait for animation? For now just play
                await new Promise(res => setTimeout(res, 500));
            }
            break;
        case 'LOOK_AT_TARGET':
        case 'LOOK_AT':
        case 'look_at':
            const lookTargetName = params.target || value || 'player';
            let lookAtPos = null;

            if (lookTargetName === 'player' && playableCharacter) {
                lookAtPos = playableCharacter.position.clone();
            } else {
                // Find NPC by name or ID
                const otherNpc = Object.values(spawnedNPCs).find(n =>
                    n.name.toLowerCase() === lookTargetName.toLowerCase() ||
                    String(n.id) === String(lookTargetName)
                );
                if (otherNpc && otherNpc.mesh) {
                    lookAtPos = otherNpc.mesh.position.clone();
                }
            }

            if (targetActor && targetActor.mesh && lookAtPos) {
                // Only rotate on Y axis
                const normalizedLookAt = new THREE.Vector3(lookAtPos.x, targetActor.mesh.position.y, lookAtPos.z);
                targetActor.mesh.lookAt(normalizedLookAt);
                // Sub-wait to ensure rotation is "seen" before next action
                await new Promise(res => setTimeout(res, 300));
            }
            break;


        case 'GOTO SCENE':
            const scId = value || params.scene_id;
            if (scId) await swapScene({ target_scene_id: parseInt(scId) });
            break;
    }
};



const getGatewayAtPosition = () => {
    if (!currentScene.value || !currentScene.value.gateways || !playableCharacter) return null;

    // Project character position to screen space
    const vector = playableCharacter.position.clone();
    vector.project(camera);

    const x = (vector.x + 1) / 2 * 100;
    const y = -(vector.y - 1) / 2 * 100;

    for (const gw of currentScene.value.gateways) {
        if (x >= gw.x && x <= gw.x + gw.width && y >= gw.y && y <= gw.y + gw.height) {
            return gw;
        }
    }
    return null;
};

const swapScene = async (gateway) => {
    loading.value = true;
    try {
        const nextSceneRes = await axios.get(`/api/scenes/${gateway.target_scene_id}`);
        const nextScene = nextSceneRes.data;
        const locRes = await axios.get(`/api/locaties/${nextScene.locatie_id}`);

        currentScene.value = { ...nextScene, location: locRes.data };

        // Reset states
        isWalking.value = false;
        pendingGateway.value = null;
        lastTriggeredGateway.value = null;
        if (targetPointMesh) targetPointMesh.visible = false;

        // When swapping, landing is already done
        landingDone.value = true;

        await loadSceneGLB(currentScene.value, gateway.target_spawn_point);

        // Anti-loop: check if we spawned inside a gateway in the new scene
        const entryGw = getGatewayAtPosition();
        if (entryGw) {
            console.log("Anti-loop: spawned inside gateway", entryGw);
            lastTriggeredGateway.value = entryGw;
        }
    } catch (e) {
        console.error("Failed to swap scene", e);
    } finally {
        loading.value = false;
    }
};

const checkGateways = () => {
    if (!currentScene.value || !currentScene.value.gateways || !playableCharacter || isSwapping.value) return;

    const found = getGatewayAtPosition();

    if (found) {
        triggerGateway(found);
    } else {
        // Only reset if we were previously in a gateway or if it's not the forced pending one
        if (!pendingGateway.value) {
            lastTriggeredGateway.value = null;
            lastExecutedBehaviorGateway.value = null;
        }
    }
};





const isActionActive = (gateway, actionIndex) => {
    return Object.values(spawnedNPCs).some(n =>
        String(n.activeGedragId) === String(gateway.gedrag_id) &&
        n.currentActionIndex === actionIndex
    );
};

const animate = () => {

    animationFrameId = requestAnimationFrame(animate);
    const delta = clock ? clock.getDelta() : 0.016;

    if (characterMixer) {
        characterMixer.update(delta);
    }

    // Update NPC mixers
    Object.values(spawnedNPCs).forEach(npc => {
        if (npc.mixer) npc.mixer.update(delta);

        // Handle NPC movement interpolation
        if (npc.isWalking && npc.mesh) {
            const dist = npc.mesh.position.distanceTo(npc.targetPos);
            if (dist > 0.1) {
                const lookPos = new THREE.Vector3(npc.targetPos.x, npc.mesh.position.y, npc.targetPos.z);
                npc.mesh.lookAt(lookPos);
                const dir = new THREE.Vector3().subVectors(npc.targetPos, npc.mesh.position).normalize();
                npc.mesh.position.add(dir.multiplyScalar(characterSpeed * delta));
                if (npc.actions.walk && !npc.actions.walk.isRunning()) {
                    if (npc.actions.idle) npc.actions.idle.stop();
                    npc.actions.walk.play();
                }
            } else {
                npc.isWalking = false;
                if (npc.actions.walk) npc.actions.walk.stop();
                if (npc.actions.idle) npc.actions.idle.play();
            }
        }
    });

    if (isWalking.value && playableCharacter) {

        const distance = playableCharacter.position.distanceTo(targetPosition);
        if (distance > 0.1) {
            const lookPos = new THREE.Vector3(targetPosition.x, playableCharacter.position.y, targetPosition.z);
            playableCharacter.lookAt(lookPos);
            const direction = new THREE.Vector3().subVectors(targetPosition, playableCharacter.position).normalize();
            playableCharacter.position.add(direction.multiplyScalar(characterSpeed * delta));

            // Continuous check while walking
            checkGateways();
        } else {
            isWalking.value = false;
            if (targetPointMesh) targetPointMesh.visible = false;

            if (pendingGateway.value) {
                triggerGateway(pendingGateway.value, true); // Force trigger on explicit arrival
                pendingGateway.value = null;
            } else {
                checkGateways();
            }
        }
    } else {
        // Check even when standing if just standing in a gateway
        checkGateways();
    }


    renderer.render(scene, camera);
};

watch(show3DHelpers, (newVal) => {
    if (!currentGltf) return;
    currentGltf.traverse((child) => {
        if (child.isMesh) {
            const isFloor = child.name === 'floor';
            if (isFloor) {
                if (child.material.isShadowMaterial) {
                    child.material.opacity = newVal ? 0.3 : 0.4;
                } else {
                    child.material.opacity = newVal ? 0.3 : 0.001;
                }
                child.material.transparent = true;
                child.material.colorWrite = true;
            } else {
                child.material.opacity = newVal ? 0.3 : 0;
                child.material.transparent = newVal; // Use transparent for helpers, opaque for mask
                child.material.colorWrite = newVal;
                // If helpers are off, it's a pure mask
                if (!newVal) {
                    child.material.colorWrite = false;
                }
            }
        }
    });
});

watch(ambientIntensity, (val) => {
    if (ambientLight) ambientLight.intensity = val;
});

watch(sunIntensity, (val) => {
    if (sun) sun.intensity = val;
});

const backgroundImageUrl = computed(() => {
    if (!currentScene.value) return '';

    // Check for scene-specific artwork first
    if (currentScene.value.artwork && currentScene.value.artwork.length > 0) {
        return getImageUrl(currentScene.value.artwork[0].bestandspad);
    }

    // Fallback to location artwork
    if (currentScene.value.location?.artwork && currentScene.value.location.artwork.length > 0) {
        return getImageUrl(currentScene.value.location.artwork[0].bestandspad);
    }

    return '';
});

</script>

<template>
    <div class="min-h-screen bg-noir-darker p-8">
        <div class="max-w-[1300px] mx-auto">
            <div v-if="error" class="bg-noir-panel border border-noir-danger p-6 rounded text-noir-danger text-center">
                {{ error }}
            </div>


            <div class="flex flex-col lg:flex-row gap-8 items-start relative">
                <!-- Loading Overlay -->
                <div v-if="loading" class="absolute inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm pointer-events-auto">
                    <div class="flex flex-col items-center gap-4">
                        <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-noir-accent font-mono text-[8px] flex items-center justify-center">{{ t('map.loading') }}</div>
                        <div class="text-noir-accent font-mono text-[10px] animate-pulse uppercase tracking-[0.2em]">{{ t('map.data_transfer') }}</div>
                    </div>
                </div>

                <!-- Left: 3D Scene Area -->
                <div class="flex-1">
                    <div class="relative border-4 border-noir-dark shadow-[0_0_50px_rgba(0,0,0,0.5)] bg-black overflow-hidden mx-auto"
                         :style="{ width: containerWidth + 'px', height: containerHeight + 'px' }"
                         @click="onMapClick">
                        <img v-if="backgroundImageUrl" :src="backgroundImageUrl" class="absolute inset-0 w-full h-full object-cover pointer-events-none" alt="Background" />
                        <div ref="canvasContainer" class="absolute inset-0 pointer-events-auto z-10"></div>

                        <div v-if="!landingDone" class="absolute top-4 left-4 bg-black/60 text-noir-accent px-3 py-1 rounded text-xs font-mono border border-noir-accent/30 animate-pulse">
                            {{ t('map.system_init_landing') }}
                        </div>

                        <div v-if="activeDialogue" class="absolute inset-x-0 bottom-4 z-40 flex flex-col items-center pointer-events-none px-12 pb-8">
                            <div class="max-w-3xl w-full flex flex-col items-center gap-6">
                                <!-- NPC Dialogue Box -->
                                <transition name="fade">
                                    <div v-if="typewriterText" class="w-full bg-noir-panel/85 backdrop-blur-md border border-noir-accent/30 p-6 rounded-lg pointer-events-auto shadow-[0_4px_24px_rgba(0,0,0,0.8)]">
                                        <div class="text-[10px] font-mono text-noir-accent mb-2 uppercase tracking-[0.2em]">
                                            {{ dialogueNPCName || t('map.contact') }}
                                        </div>
                                        <div class="text-white text-xl leading-relaxed font-light italic">
                                            {{ typewriterText }}<span class="animate-pulse opacity-50">_</span>
                                        </div>
                                    </div>
                                </transition>

                                <!-- Player Options -->
                                <transition name="slide-up">
                                    <div v-if="showDialogueOptions" class="flex flex-col items-stretch gap-2 w-full max-w-xl pointer-events-auto">
                                        <button
                                            v-for="(option, idx) in activeDialogue.tree.nodes[currentNodeId].options"
                                            :key="idx"
                                            @click="selectOption(option)"
                                            class="group w-full bg-black/60 hover:bg-noir-accent/20 border border-noir-accent/20 hover:border-noir-accent/60 px-6 py-3 rounded text-left transition-all duration-300 flex justify-between items-center"
                                        >
                                            <div class="flex items-center gap-3">
                                                <span class="text-xs font-mono text-noir-accent/40 group-hover:text-noir-accent">[{{ String(idx + 1).padStart(2, '0') }}]</span>
                                                <span class="text-white group-hover:text-white text-sm font-medium uppercase tracking-wider">{{ option.text }}</span>
                                            </div>
                                            <span class="text-[8px] text-noir-accent opacity-0 group-hover:opacity-100 transition-opacity font-mono text-right">{{ t('map.select_response') }} >></span>
                                        </button>
                                    </div>
                                </transition>

                            </div>
                        </div>

                    </div>
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">

                         <div class="bg-noir-panel p-4 border border-noir-dark rounded">
                            <h3 class="text-white font-bold mb-2 uppercase border-b border-noir-dark pb-1 text-[10px]">{{ t('map.scene_info') }}</h3>
                            <p class="text-noir-text">{{ t('map.sec') }} {{ sectorData?.naam }}</p>
                            <p class="text-noir-text">{{ t('map.scn') }} {{ currentScene?.titel }}</p>
                            <div v-if="activeBehaviors.length" class="mt-2 pt-2 border-t border-noir-dark/50">
                                <p class="text-noir-warning font-bold text-[8px] uppercase">{{ t('map.active_behaviors') }}</p>
                                <p v-for="b in activeBehaviors" :key="b.id" class="text-noir-warning/80 text-[9px]">• {{ b.naam }}</p>
                            </div>
                        </div>

                        <div class="bg-noir-panel p-4 border border-noir-dark rounded">
                            <h3 class="text-white font-bold mb-2 uppercase border-b border-noir-dark pb-1 text-[10px]">{{ t('map.character') }}</h3>
                            <p class="text-noir-text">{{ t('map.playable') }} {{ settings.playable }}</p>
                            <p class="text-noir-text">{{ t('map.mode') }} {{ isWalking ? t('map.walking') : t('map.idle') }}</p>
                        </div>
                    </div>
                </div>


                <!-- Right: Info Sidebar -->
                <div class="w-full lg:w-96 shrink-0 space-y-6">
                    <!-- NPC Status -->
                    <div class="bg-noir-panel p-4 border border-noir-dark rounded text-xs space-y-4">
                        <div class="space-y-2">
                             <div v-for="npc in Object.values(spawnedNPCs)" :key="npc.id" class="flex items-center justify-between p-2 bg-black/20 rounded border border-noir-dark/50">
                                 <div class="flex items-center gap-2">
                                     <div class="w-2 h-2 rounded-full" :class="(npcModes[npc.id] || 'IDLE') === 'IDLE' ? 'bg-noir-accent' : 'bg-noir-warning animate-pulse'"></div>
                                     <div class="flex flex-col">
                                         <span class="text-[10px] text-white font-mono uppercase leading-tight">{{ npc.name }}</span>
                                         <span v-if="npc.currentGedrag" class="text-[7px] text-noir-warning animate-pulse uppercase">{{ npc.currentGedrag }}</span>
                                         <span v-else class="text-[7px] text-noir-muted uppercase">{{ t('map.active_unit') }}</span>
                                     </div>
                                 </div>

                                 <span class="px-2 py-0.5 rounded text-[8px] font-bold" :class="{
                                     'bg-noir-accent text-black': (npcModes[npc.id] || 'IDLE') === 'IDLE',
                                     'bg-noir-warning text-black animate-pulse': (npcModes[npc.id] || 'IDLE') === 'SEQUENCE'
                                 }">
                                     {{ npcModes[npc.id] || 'IDLE' }}
                                 </span>
                             </div>
                        </div>

                        <!-- Analysis Panel -->
                        <div class="pt-4 border-t border-noir-dark/50 space-y-4">
                            <h4 class="text-white font-bold uppercase text-[9px] mb-2 tracking-widest text-noir-muted">{{ t('map.scene_analysis') }}</h4>

                            <div class="space-y-2 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                                <div v-for="gw in sceneTriggers" :key="gw.label"
                                     @click="gw.gedrag_id ? triggerGateway(gw, true) : null"
                                     class="p-2 bg-black/40 rounded border transition-all duration-300 group"
                                     :class="[
                                         activeGateway === gw ? 'border-noir-accent bg-noir-accent/10' : 'border-noir-dark/30',
                                         gw.gedrag_id ? 'border-l-2 border-l-noir-warning cursor-pointer hover:bg-black/60 hover:border-noir-warning' : 'border-l-2 border-l-noir-accent opacity-60'
                                     ]">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[9px] text-white font-mono uppercase">{{ gw.label || 'AREA' }}</span>
                                            <div v-if="gw.gedrag_id" class="px-1.5 py-0.5 rounded-full bg-noir-warning/20 border border-noir-warning/30 text-[6px] text-noir-warning font-bold">{{ t('map.trigger') }}</div>
                                        </div>
                                        <span v-if="activeGateway === gw" class="text-[7px] text-noir-accent animate-pulse font-bold">{{ t('map.active') }}</span>
                                        <span v-else-if="gw.gedrag_id" class="text-[6px] text-noir-muted group-hover:text-noir-warning opacity-0 group-hover:opacity-100 transition-opacity uppercase font-mono">{{ t('map.manual_start') }}</span>
                                    </div>

                                    <div v-if="gw.gedrag_id" class="text-[7px] text-noir-warning mt-1 font-mono uppercase flex flex-col gap-1">
                                        <div class="flex items-center justify-between border-b border-noir-warning/20 pb-1">
                                            <span>{{ t('map.linked_behavior') }} {{ gedragingen.find(g => g.id === gw.gedrag_id)?.naam || t('map.unknown') }}</span>
                                            <span class="opacity-50">({{ gedragingen.find(g => g.id === gw.gedrag_id)?.acties?.length || 0 }} ACTS)</span>
                                        </div>
                                        <div class="pl-2 pt-1 space-y-0.5 opacity-60 group-hover:opacity-90">
                                            <div v-for="(act, ai) in (gedragingen.find(g => g.id === gw.gedrag_id)?.acties || [])" :key="ai"
                                                 class="flex gap-2 transition-all duration-300"
                                                 :class="{ 'text-noir-warning font-bold scale-105 translate-x-1 opacity-100': isActionActive(gw, ai) }">
                                                <span class="opacity-40" :class="{ 'opacity-100': isActionActive(gw, ai) }">[{{ ai + 1 }}]</span>
                                                <span :class="isActionActive(gw, ai) ? 'text-noir-warning' : 'text-white/80'">{{ act.type }}</span>
                                            </div>
                                        </div>

                                    </div>

                                </div>
                            </div>


                            <div v-if="behaviorLog.length > 0" class="pt-4 border-t border-noir-dark/30">
                                <h4 class="text-[9px] text-noir-muted uppercase mb-2">{{ t('map.behavior_log') }}</h4>
                                <div class="space-y-1">
                                    <div v-for="(log, i) in behaviorLog" :key="i" class="text-[8px] font-mono text-noir-warning/70 border-l border-noir-warning/20 pl-2">
                                        [{{ log.time }}] {{ log.msg }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-noir-dark/50 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] text-noir-muted uppercase">{{ t('map.helpers') }}</span>

                                <button @click="show3DHelpers = !show3DHelpers"
                                        class="w-8 h-4 rounded-full relative transition-colors duration-200"
                                        :class="show3DHelpers ? 'bg-noir-accent' : 'bg-noir-dark'">
                                    <div class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full transition-transform duration-200"
                                         :class="show3DHelpers ? 'translate-x-4' : ''"></div>
                                </button>
                            </div>

                            <div v-if="characterActions['caution']" class="flex items-center justify-between">
                                <span class="text-[10px] text-noir-muted uppercase">{{ t('map.caution') }}</span>
                                <button @click="isCaution = !isCaution"
                                        class="w-8 h-4 rounded-full relative transition-colors duration-200"
                                        :class="isCaution ? 'bg-noir-danger' : 'bg-noir-dark'">
                                    <div class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full transition-transform duration-200"
                                         :class="isCaution ? 'translate-x-4' : ''"></div>
                                </button>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-[10px] text-noir-muted uppercase">{{ t('map.ambient') }}</span>
                                <input type="range" v-model.number="ambientIntensity" min="0" max="2" step="0.1" class="w-24 accent-noir-accent" />
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-[10px] text-noir-muted uppercase">{{ t('map.sun') }}</span>
                                <input type="range" v-model.number="sunIntensity" min="0" max="2" step="0.1" class="w-24 accent-noir-accent" />
                            </div>
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

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.5s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.slide-up-enter-active, .slide-up-leave-active {
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-up-enter-from {
    opacity: 0;
    transform: translateY(20px);
}
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.bg-noir-panel {
    background-color: #0a0a0a;
}

</style>
