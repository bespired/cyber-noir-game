<script setup>
import { ref, onMounted, onUnmounted, computed, reactive, watch } from 'vue';
import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader';
// import { useToast } from '../../composables/useToast';
import { useDataRobustness } from '../../composables/useDataRobustness';
// import { useI18n } from 'vue-i18n';
import { sectorData as importedSectorData } from '../../sectordata.js';
import { settings as importedSettings } from '../../settings.js';
import { dialogs as importedDialogs } from '../../dialogs.js';

// const { t } = useI18n();

const { isEngine, fetchData: fetchRobustData, resolveAssetUrl, slugify, getCharacterGlbUrl } = useDataRobustness();

const props = defineProps({
    id: [Number, String], // Scene ID
    sector_id: [Number, String],
    titel: String,
    type: String,
    data: Object,
    location: Object,
    scene_personages: Array,
    gateways: Array,
    spawn_points: Object,
    // Transient props
    targetSpawnPoint: String,
    isLanded: Boolean,
    debug: Boolean
});

const emit = defineEmits(['scene-complete']);

// const toast = useToast();

// Use prop if available
const sectorId = computed(() => props.sector_id);
const sceneId = computed(() => props.id);

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
let dialogueResolve = null;

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
const landingDone = ref(!!props.isLanded);
let targetPointMesh = null;
const pendingGateway = ref(null);
const currentLoadingSceneId = ref(null);

const activeGateway = computed(() => getGatewayAtPosition());

const sceneTriggers = computed(() => currentScene.value ?.gateways || []);
const behaviorLog = ref([]);

const activeBehaviors = computed(() => {
    if (!currentScene.value ?.gateways) return [];
    const behaviorIds = [...new Set(currentScene.value.gateways.map(gw => gw.gedrag_id).filter(id => id))];
    return gedragingen.value.filter(g => behaviorIds.includes(g.id));
});



// Animation refs
let characterMixer = null;
const characterActions = {};
let activeAction = null;
const isCaution = ref(false);

// Scaling & Visibility refs
const characterScale = ref(0.5);
const vehicleScale = ref(0.5);
const show3DHelpers = ref(false);
const sunIntensity = ref(1.0);
const ambientIntensity = ref(0.8);
const currentSpawnPoints = ref([]);

watch(() => props.debug, (val) => {
    show3DHelpers.value = !!val;
}, { immediate: true });

const VIEW_WIDTH = 1216;
const VIEW_HEIGHT = 832;
const ASPECT_RATIO = VIEW_WIDTH / VIEW_HEIGHT;

// slugify and getCharacterGlbUrl are now imported from useDataRobustness

const getImageUrl = (path) => {
    return resolveAssetUrl(path);
};

const getGlbUrl = (location, sector) => {
    if (!location) return '';

    // Favor pre-calculated threefile from backend
    if (location.threefile) {
        return resolveAssetUrl(`glb/${location.threefile}`);
    }

    if (!sector) return '';
    const sectorSlug = slugify(sector.naam);
    const locationSlug = slugify(location.naam);
    return resolveAssetUrl(`glb/${sectorSlug}--${locationSlug}.glb`);
};

// getCharacterGlbUrl is now imported from useDataRobustness

const getVehicleGlbUrl = (path) => {
    if (!path) return '';
    // Strip 'artwork/' if it's at the start
    let cleanPath = path.replace(/^artwork\//, '');

    // Ensure it's in the glb folder if it's just a filename
    if (!cleanPath.includes('/')) {
        cleanPath = `glb/${cleanPath}`;
    }

    const url = resolveAssetUrl(cleanPath);
    console.log(`[DEBUG] Resolved Vehicle URL: ${url} (original: ${path})`);
    return url;
};

// Responsive Logic
const containerWidth = ref(VIEW_WIDTH);
const containerHeight = ref(VIEW_HEIGHT);
const containerStyle = computed(() => {
    if (isEngine.value) {
        return {
            width: '100vw',
            height: '100vh',
            '--noir-border-width': '0px',
            border: 'none',
            boxShadow: 'none',
            margin: '0',
            padding: '0'
        };
    }
    return {
        width: containerWidth.value + 'px',
        height: containerHeight.value + 'px'
    };
});

const updateDimensions = () => {
    // In engine mode, we fill the screen
    if (isEngine.value) {
        const w = window.innerWidth;
        const h = window.innerHeight;
        containerWidth.value = w;
        containerHeight.value = h;

        if (renderer && camera) {
            renderer.setSize(w, h);

            // Perspective matching for object-fit: cover using setViewOffset
            // This preserves the internal camera matrix (important for fSpy/shifted shots)
            const scale = Math.max(w / VIEW_WIDTH, h / VIEW_HEIGHT);
            const fullW = VIEW_WIDTH * scale;
            const fullH = VIEW_HEIGHT * scale;
            const offsetX = (fullW - w) / 2;
            const offsetY = (fullH - h) / 2;

            if (camera.setViewOffset) {
                camera.setViewOffset(fullW, fullH, offsetX, offsetY, w, h);
            } else {
                camera.aspect = w / h;
            }
            camera.updateProjectionMatrix();
        }
        return;
    }

    const verticalOffset = 200;
    const sidebarWidth = window.innerWidth >= 1024 ? 384 + 32 : 0;

    const maxWidth = window.innerWidth - 64 - sidebarWidth;
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
    initThree();
    updateDimensions(); // Call after initThree to apply zoom/size
    if (currentScene.value) {
        await loadSceneGLB(currentScene.value, props.targetSpawnPoint);
    }
});

onUnmounted(() => {
    window.removeEventListener('resize', updateDimensions);
    if (animationFrameId) cancelAnimationFrame(animationFrameId);
    if (renderer) renderer.dispose();
});

const fetchData = async () => {
    try {
        if (isEngine.value && importedSectorData && importedSectorData.length > 0) {
            // Find current sector and scene in local sectorData
            const sId = parseInt(sectorId.value);
            sectorData.value = importedSectorData.find(s => parseInt(s.id) === sId);

            if (sectorData.value) {
                scenesInSector.value = sectorData.value.scenes || [];

                // If we have scene props, use them
                if (props.id && props.location) {
                    currentScene.value = { ...props };
                } else if (scenesInSector.value.length > 0) {
                    // Try to find the scene we're looking for (e.g. from props.id)
                    const targetSceneId = parseInt(props.id);
                    currentScene.value = scenesInSector.value.find(s => parseInt(s.id) === targetSceneId) || scenesInSector.value[0];
                }
            }

            // In engine mode, we use the imported settings
            settings.value = importedSettings || {};

            // Nested Gedrag: Extract from the current scene
            if (currentScene.value && currentScene.value.gedrag) {
                gedragingen.value = currentScene.value.gedrag;
            } else {
                // Fallback if not nested (although it should be)
                gedragingen.value = await fetchRobustData('gedrag.json', 'gedrag');
            }

            // Find start scene logic if currentScene not already set
            if (!currentScene.value) {
               for (const s of scenesInSector.value) {
                   const loc = s.location;
                   const allSpawnPoints = loc?.spawn_points || {};
                   const spawnPoints = allSpawnPoints[sId] || allSpawnPoints[Number(sId)] || [];
                   if (spawnPoints.some(p => p.type === 'vehicle')) {
                       currentScene.value = s;
                       break;
                   }
               }
            }
        } else {
            // Emulator / API mode
            const sectorRes = await fetchRobustData(`sectors/${sectorId.value}.json`, `sectoren/${sectorId.value}`);
            const settingsRes = await fetchRobustData('settings.json', 'instellingen');
            const gedragRes = await fetchRobustData('gedrag.json', 'gedrag');

            sectorData.value = sectorRes;
            scenesInSector.value = sectorData.value.scenes || [];
            settings.value = settingsRes;
            gedragingen.value = gedragRes;

            if (props.id) {
               const sceneData = await fetchRobustData(`scenes/${props.id}.json`, `scenes/${props.id}`);
               const locationData = await fetchRobustData(`locations/${sceneData.locatie_id}.json`, `locaties/${sceneData.locatie_id}`);
               currentScene.value = { ...sceneData, location: locationData };
            } else {
                // Original logic for finding start scene
                for (const s of scenesInSector.value) {
                    const locationData = await fetchRobustData(`locations/${s.locatie_id}.json`, `locaties/${s.locatie_id}`);
                    const allSpawnPoints = locationData.spawn_points || {};
                    const spawnPoints = allSpawnPoints[sectorId.value] || allSpawnPoints[Number(sectorId.value)] || [];
                    if (spawnPoints.some(p => p.type === 'vehicle')) {
                        const sceneData = await fetchRobustData(`scenes/${s.id}.json`, `scenes/${s.id}`);
                        currentScene.value = { ...sceneData, location: locationData };
                        break;
                    }
                }
            }
        }

        if (!currentScene.value && scenesInSector.value.length > 0) {
            const s = scenesInSector.value[0];
            if (isEngine.value) {
                currentScene.value = { ...s, gateways: s.gateways || [], location: s.location };
            } else {
                const locRes = await axios.get(`/api/locaties/${s.locatie_id}`);
                const scRes = await axios.get(`/api/scenes/${s.id}`);
                currentScene.value = { ...scRes.data, gateways: scRes.data.gateways || [], location: locRes.data };
            }
        }

    } catch (e) {
        console.error("Failed to fetch data", e);
        error.value = 'load_error';
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
    sun.shadow.mapSize.width = 1024;
    sun.shadow.mapSize.height = 1024;
    sun.shadow.camera.near = 0.5;
    sun.shadow.camera.far = 50;
    sun.shadow.camera.left = -10;
    sun.shadow.camera.right = 10;
    sun.shadow.camera.top = 10;
    sun.shadow.camera.bottom = -10;
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
        if (currentGltf) { scene.remove(currentGltf);
            currentGltf = null; }
        if (playableCharacter) { scene.remove(playableCharacter);
            playableCharacter = null; }
        if (vehicle) { scene.remove(vehicle);
            vehicle = null; }

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
                // Update dimensions immediately to apply correct zoom/offset to the new camera
                updateDimensions();
            }

            // 2. Identify Spawn Points
            const playerSectorId = sectorId.value;
            const allSpawnPoints = sceneData.location.spawn_points || {};
            const spawnPoints = allSpawnPoints[playerSectorId] || allSpawnPoints[Number(playerSectorId)] || [];
            currentSpawnPoints.value = spawnPoints;

            console.log(`[DEBUG] Scene ${sceneData.id} - Target Spawn: ${targetSpawnLabel}`);
            console.log(`[DEBUG] Available points:`, spawnPoints.map(p => p.name || p.type));

            // Spawning Priority:
            // 1. Specific requested label (case-insensitive)
            // 2. 'landing' waypoint (if landing for first time)
            // 3. 'personage' type
            // 4. Any 'waypoint'
            // 5. First available point
            // 0,0,0 if all else fails

            const findByName = (label) => spawnPoints.find(p => p.name && p.name.toLowerCase() === label.toLowerCase());

            const specificSpawn = targetSpawnLabel ? (findByName(targetSpawnLabel) || spawnPoints.find(p => String(p.personage_id) === String(targetSpawnLabel))) : null;
            const landingSpawn = findByName('landing');
            const personageSpawn = spawnPoints.find(p => p.type === 'personage');
            const anyWaypoint = spawnPoints.find(p => p.type === 'waypoint');

            const charSpawnPoint = specificSpawn || 
                              (!landingDone.value ? landingSpawn : null) || 
                              personageSpawn || 
                              anyWaypoint || 
                              spawnPoints[0];

            if (specificSpawn) console.log(`[DEBUG] Chose specific spawn: ${specificSpawn.name}`);
            else if (!landingDone.value && landingSpawn) console.log(`[DEBUG] Chose landing spawn`);
            else if (charSpawnPoint) console.log(`[DEBUG] Chose fallback spawn: ${charSpawnPoint.name || charSpawnPoint.type}`);
            else console.log(`[DEBUG] No spawn point found! Fallback to 0,0,0`);

            // Vehicle: search for waypoint named 'spinner'
            const vehicleSpawn = findByName('spinner') || spawnPoints.find(p => p.type === 'vehicle');


            // 3. Sequentially spawn vehicle then character then props
            const vehiclePromise = vehicleSpawn ?
                spawnVehicle(vehicleSpawn, !landingDone.value) :
                Promise.resolve();

            if (!vehicleSpawn) {
                landingDone.value = true;
            }

            vehiclePromise.then(() => {
                if (currentLoadingSceneId.value !== sceneData.id) return resolve();

                const charPos = charSpawnPoint ||
                    (vehicleSpawn ? { x: vehicleSpawn.x + 2, y: vehicleSpawn.y, z: vehicleSpawn.z } : { x: 0, y: 0, z: 0 });

                spawnCharacter(charPos, sceneData.id).then(() => {
                    if (currentLoadingSceneId.value !== sceneData.id) return resolve();
                    const spList = sceneData.scene_personages || sceneData.scenePersonages || [];
                    spawnNPCs(spList, sceneData.id).then(() => {
                        if (currentLoadingSceneId.value !== sceneData.id) return resolve();
                        
                        // Anti-loop: If we spawned inside a gateway, mark it as already triggered
                        const entryGw = getGatewayAtPosition();
                        if (entryGw) {
                            console.log(`[DEBUG] Anti-loop: Spawned inside gateway ${entryGw.target_scene_id}. Ignoring first trigger.`);
                            lastTriggeredGateway.value = entryGw;
                        }

                        if (typeof spawnDebugHelpers === 'function') {
                            spawnDebugHelpers(spawnPoints);
                        }
                        spawnProps(spawnPoints, sceneData.id).then(resolve);
                    });
                });
            });


        }, undefined, (err) => {
            console.error("[DEBUG] Main GLB load failed:", err, "URL:", url);
            resolve();
        });
    });
};

const spawnVehicle = (spawnPoint, animate = true) => {
    return new Promise((resolve) => {
        const spinnerPath = settings.value.spinner2049 || 'glb/vehicle--spinner-2049.glb';
        const vehicleUrl = getVehicleGlbUrl(spinnerPath);
        console.log("[DEBUG] Spinner URL:", vehicleUrl);
        if (!vehicleUrl) {
            landingDone.value = true;
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
                        emit('scene-complete', { landed: true });
                        resolve();
                    }
                };
                requestAnimationFrame(animateLanding);
            } else {
                vehicle.position.set(spawnPoint.x, targetY, spawnPoint.z);
                resolve();
            }
        }, undefined, (err) => {
            console.error("Failed to load vehicle GLB", err);
            landingDone.value = true;
            resolve();
        });
    });
};

const spawnCharacter = (spawnPoint, sceneId) => {
    return new Promise((resolve) => {
        let characterName = 'Nexus Runner K';
        let customGlb = null;

        const savedPlayer = localStorage.getItem('player_character');
        if (savedPlayer) {
            try {
                const p = JSON.parse(savedPlayer);
                if (p.threefile) {
                    customGlb = p.threefile;
                } else if (p.naam) {
                    characterName = p.naam;
                } else if (p.name) {
                    characterName = p.name;
                }
            } catch (e) {
                console.warn("Failed to parse player_character from localStorage", e);
            }
        } else {
            characterName = localStorage.getItem('selected_character_naam') || settings.value.playable || 'Nexus Runner K';
        }

        const charUrl = customGlb 
            ? resolveAssetUrl(`glb/${customGlb}`)
            : getCharacterGlbUrl(characterName);

        console.log(`[DEBUG] Resolved Character URL: ${charUrl} (Name: ${characterName}, CustomGLB: ${customGlb})`);
        
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

const spawnedThreeProps = []; // Track spawned props for cleanup

const spawnProps = async (spawnPoints, sceneId) => {
    // Cleanup old props
    spawnedThreeProps.forEach(p => scene.remove(p));
    spawnedThreeProps.length = 0;

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
            const aanwijzing = currentScene.value.location.aanwijzingen ?.find(a => a.id === p.aanwijzing_id);
            if (aanwijzing && aanwijzing.glb_pad) {
                const url = getImageUrl(aanwijzing.glb_pad);
                const gltf = await new Promise((res, rej) => loader.load(url, res, undefined, rej));
                const propMesh = gltf.scene;
                const scale = p.scale || 1.0;
                propMesh.scale.set(scale, scale, scale);
                propMesh.position.set(p.x, p.y, p.z);
                propMesh.rotation.y = THREE.MathUtils.degToRad(p.direction || 0);
                scene.add(propMesh);
                spawnedThreeProps.push(propMesh);
            }
        } catch (e) {
            console.warn("Failed to spawn prop", p.id, e);
        }
    }
};

const debugHelpers = [];
const spawnDebugHelpers = (spawnPoints) => {
    debugHelpers.forEach(h => scene.remove(h));
    debugHelpers.length = 0;

    if (!show3DHelpers.value) return;

    spawnPoints.forEach(p => {
        // Sphere for the base point
        const geo = new THREE.SphereGeometry(0.1, 16, 16);
        const mat = new THREE.MeshBasicMaterial({ color: 0x00ff00 });
        const sphere = new THREE.Mesh(geo, mat);
        sphere.position.set(p.x, p.y, p.z);
        scene.add(sphere);
        debugHelpers.push(sphere);

        // Arrow for direction
        const dir = new THREE.Vector3(0, 0, -1);
        dir.applyAxisAngle(new THREE.Vector3(0, 1, 0), THREE.MathUtils.degToRad(p.direction || 0));
        const arrow = new THREE.ArrowHelper(dir, new THREE.Vector3(p.x, p.y + 0.1, p.z), 0.5, 0x00ff00);
        scene.add(arrow);
        debugHelpers.push(arrow);

        // Label using sprite
        const canvas = document.createElement('canvas');
        canvas.width = 256;
        canvas.height = 64;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#00ff00';
        ctx.font = '32px monospace';
        ctx.fillText(p.name || p.type || 'spawn', 10, 40);
        const tex = new THREE.CanvasTexture(canvas);
        const spriteMat = new THREE.SpriteMaterial({ map: tex, sizeAttenuation: true });
        const sprite = new THREE.Sprite(spriteMat);
        sprite.position.set(p.x, p.y + 0.6, p.z);
        sprite.scale.set(1.5, 0.4, 1);
        scene.add(sprite);
        debugHelpers.push(sprite);
    });
};

const spawnNPCs = async (scenePersonages, sceneId) => {
    // Cleanup old NPCs (redundant but safe)
    if (sceneId === currentLoadingSceneId.value) {
        // ... cleanup already happened in loadSceneGLB
    }

    if (!scenePersonages || scenePersonages.length === 0) return;

    const currentSectorId = sectorId.value;
    const allSpawnPoints = currentScene.value.location ?.spawn_points || {};
    const spawnPoints = allSpawnPoints[currentSectorId] || allSpawnPoints[Number(currentSectorId)] || [];

    for (const sp of scenePersonages) {
        try {
            if (currentLoadingSceneId.value !== sceneId) break;
            const p = sp.personage;
            if (!p) continue;

            // Find assigned spawn point
            const spawnPos = spawnPoints.find(point => point.name === sp.spawn_point_name || point.id === sp.spawn_point_name) ||
                spawnPoints.find(point => point.type === 'personage') ||
                { x: 0, y: 0, z: 0 };

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
            if (gltf.animations ?.length > 0) {
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
            console.error("Failed to spawn NPC", sp.personage ?.naam, e);
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
    const w = rect.width;
    const h = rect.height;

    const mouseX = ((e.clientX - rect.left) / w) * 2 - 1;
    const mouseY = -((e.clientY - rect.top) / h) * 2 + 1;

    // 1. Detect Gateway Click (Screen Space -> Image Space Mapping)
    let screenX, screenY;

    if (isEngine.value) {
        // Handle object-fit: cover mapping
        const scale = Math.max(w / VIEW_WIDTH, h / VIEW_HEIGHT);
        const fullW = VIEW_WIDTH * scale;
        const fullH = VIEW_HEIGHT * scale;
        const offsetX = (fullW - w) / 2;
        const offsetY = (fullH - h) / 2;

        const imgX = (e.clientX - rect.left + offsetX) / scale;
        const imgY = (e.clientY - rect.top + offsetY) / scale;

        screenX = (imgX / VIEW_WIDTH) * 100;
        screenY = (imgY / VIEW_HEIGHT) * 100;
    } else {
        // Standard mapping (fixed aspect container)
        screenX = (mouseX + 1) / 2 * 100;
        screenY = -(mouseY - 1) / 2 * 100;
    }

    const clickedGateway = currentScene.value ?.gateways ?.find(gw =>
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
        const currentSectorId = sectorId.value;
        const allSpawnPoints = currentScene.value.location ?.spawn_points || {};
        const spawnPoints = allSpawnPoints[currentSectorId] || allSpawnPoints[Number(currentSectorId)] || [];
        const waypoints = spawnPoints.filter(p => p.type === 'waypoint');

        if (waypoints.length > 0) {
            let nearest = null;
            let minDist = Infinity;
            waypoints.forEach(wp => {
                const vec = new THREE.Vector3(wp.x, wp.y, wp.z);
                vec.project(camera);

                let wpX, wpY;
                if (isEngine.value) {
                    const scale = Math.max(w / VIEW_WIDTH, h / VIEW_HEIGHT);
                    const fullW = VIEW_WIDTH * scale;
                    const fullH = VIEW_HEIGHT * scale;
                    const offsetX = (fullW - w) / 2;
                    const offsetY = (fullH - h) / 2;

                    const pixelX = (vec.x + 1) / 2 * w;
                    const pixelY = -(vec.y - 1) / 2 * h;

                    wpX = ((pixelX + offsetX) / scale) / VIEW_WIDTH * 100;
                    wpY = ((pixelY + offsetY) / scale) / VIEW_HEIGHT * 100;
                } else {
                    wpX = (vec.x + 1) / 2 * 100;
                    wpY = -(vec.y - 1) / 2 * 100;
                }

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

            let res;
            if (isEngine.value && importedDialogs) {
                // Find in imported JS array
                res = importedDialogs.find(d => String(d.id) === String(dialoogId));
            }

            if (!res) {
                res = await fetchRobustData(`dialogues/${dialoogId}.json`, `dialogen/${dialoogId}`);
            }

            activeDialogue.value = res;
            dialogueNPCName.value = activeDialogue.value.personage?.naam || 'NPC';

            // Turn player towards NPC
            if (playableCharacter && activeDialogue.value.personage_id) {
                const speakerId = activeDialogue.value.personage_id;
                const speaker = spawnedNPCs[speakerId];
                if (speaker && speaker.mesh) {
                    const targetPos = speaker.mesh.position.clone();
                    targetPos.y = playableCharacter.position.y; // Lock Y
                    playableCharacter.lookAt(targetPos);
                    console.log(`[DIALOGUE] Player turning to face speaker ${speakerId}`);
                }
            }

            // Default to 'root', or fallback to first key
            const nodeKeys = Object.keys(activeDialogue.value.tree ?.nodes || {});
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
    if (option.actions ?.length > 0) {
        for (const action of option.actions) {
            await runAction(action, actorId);
        }
    }
    if (option.actions ?.some(a => a.type === 'END TALK')) {
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
const blockTriggers = ref(false);

watch(() => props.id, async (newId) => {
    if (newId) {
        console.log(`[DEBUG] Scene ID changed to ${newId}, reloading...`);
        // Reset all states
        isWalking.value = false;
        pendingGateway.value = null;
        lastTriggeredGateway.value = null;
        isSwapping.value = false;
        blockTriggers.value = true;
        
        // Clear landing status if ID changed (App.vue manages isLanded prop)
        landingDone.value = !!props.isLanded;

        // Cleanup and reload
        await fetchData();
        await loadSceneGLB(currentScene.value, props.targetSpawnPoint);
        
        // check position after reload to ensure we aren't stuck in a loop
        const entryGw = getGatewayAtPosition();
        if (entryGw) lastTriggeredGateway.value = entryGw;
        
        blockTriggers.value = false;
    }
}, { immediate: true });

const triggerGateway = async (gateway, isForced = false) => {
    if (isSwapping.value) return;

    const type = gateway.type || (gateway.target_scene_id ? 'gateway' : 'trigger');

    // 1. Behavioral Logic (SYSTEM_TRIGGER or Gateway with behavior)
    if (gateway.gedrag_id && (!isBehaviorActive.value || isForced)) {
        if (isForced || lastExecutedBehaviorGateway.value !== gateway) {

            const gedrag = gedragingen.value.find(g => g.id === gateway.gedrag_id);

            // Find matching spawned NPCs
            const spRelation = currentScene.value ?.scene_personages || currentScene.value ?.scenePersonages || [];
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

        lastTriggeredGateway.value = gateway;

        // Emit for parent (Engine/Player) to handle navigation
        console.log(`[TRIGGER] Emitting scene-complete for target: ${gateway.target_scene_id}`);
        emit('scene-complete', {
            targetSceneId: gateway.target_scene_id,
            targetSpawnPoint: gateway.target_spawn_point
        });

        // Also fallback to internal swap if no one handles it or if in a mode that needs it
        // In Engine mode, the parent likely reloads us with new props, effectively resetting.
        isSwapping.value = true;
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
                console.log("tag_acquired")
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
                const currentSectorId = sectorId.value;
                const locSpawnPoints = currentScene.value.location ?.spawn_points || {};
                const spawnPoints = locSpawnPoints[currentSectorId] || locSpawnPoints[Number(currentSectorId)] || [];
                const sp = spawnPoints.find(p => p.name === spName || p.id === spName);
                if (sp) {
                    if (targetActor) {
                        targetActor.targetPos.set(sp.x, sp.y, sp.z);
                        targetActor.isWalking = true;
                        await new Promise(resolve => {
                            const check = setInterval(() => {
                                if (!targetActor.isWalking) { clearInterval(check);
                                    resolve(); }
                            }, 100);
                        });
                    } else if (actorId === null && playableCharacter) {
                        targetPosition.set(sp.x, sp.y, sp.z);
                        isWalking.value = true;
                        await new Promise(resolve => {
                            const check = setInterval(() => {
                                if (!isWalking.value) { clearInterval(check);
                                    resolve(); }
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

    const rect = renderer.domElement.getBoundingClientRect();
    const w = rect.width;
    const h = rect.height;

    let screenX, screenY;

    if (isEngine.value) {
        // Reverse object-fit: cover mapping for character projection
        const scale = Math.max(w / VIEW_WIDTH, h / VIEW_HEIGHT);
        const fullW = VIEW_WIDTH * scale;
        const fullH = VIEW_HEIGHT * scale;
        const offsetX = (fullW - w) / 2;
        const offsetY = (fullH - h) / 2;

        // Convert normalized device coordinates (NDC) to absolute screen pixels
        const pixelX = (vector.x + 1) / 2 * w;
        const pixelY = -(vector.y - 1) / 2 * h;

        // Convert absolute screen pixels to image space (0-100%)
        const imgX = (pixelX + offsetX) / scale;
        const imgY = (pixelY + offsetY) / scale;

        screenX = (imgX / VIEW_WIDTH) * 100;
        screenY = (imgY / VIEW_HEIGHT) * 100;
    } else {
        screenX = (vector.x + 1) / 2 * 100;
        screenY = -(vector.y - 1) / 2 * 100;
    }

    for (const gw of currentScene.value.gateways) {
        if (screenX >= gw.x && screenX <= gw.x + gw.width && screenY >= gw.y && screenY <= gw.y + gw.height) {
            return gw;
        }
    }
    return null;
};

const swapScene = async (gateway) => {
    loading.value = true;
    try {
        let nextScene = null;
        let location = null;

        if (isEngine.value && sectorData.value) {
            nextScene = sectorData.value.scenes.find(s => parseInt(s.id) === parseInt(gateway.target_scene_id));
            if (nextScene) {
                location = nextScene.location;
            }
        }

        if (!nextScene) {
            const nextSceneRes = await fetchRobustData(`scenes/${gateway.target_scene_id}.json`, `scenes/${gateway.target_scene_id}`);
            nextScene = nextSceneRes;
            const locRes = await fetchRobustData(`locations/${nextScene.locatie_id}.json`, `locaties/${nextScene.locatie_id}`);
            location = locRes;
        }

        currentScene.value = { ...nextScene, location: location };

        // Handle nested gedrag in swapped scene
        if (currentScene.value.gedrag) {
            gedragingen.value = currentScene.value.gedrag;
        }

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
    if (!currentScene.value || !currentScene.value.gateways || !playableCharacter || isSwapping.value || blockTriggers.value) return;

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
        if (!blockTriggers.value) checkGateways();
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

    // Refresh 3D spawn markers
    if (typeof spawnDebugHelpers === 'function') {
        spawnDebugHelpers(currentSpawnPoints.value);
    }
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
    if (currentScene.value.location ?.artwork && currentScene.value.location.artwork.length > 0) {
        return getImageUrl(currentScene.value.location.artwork[0].bestandspad);
    }

    return '';
});

const gatewaysToDraw = computed(() => {
    return currentScene.value?.gateways || props.gateways || [];
});

const debugOverlayStyle = computed(() => {
    const w = containerWidth.value;
    const h = containerHeight.value;

    // Logic from onMapClick/updateDimensions for object-fit: cover
    const scale = Math.max(w / VIEW_WIDTH, h / VIEW_HEIGHT);
    const fullW = VIEW_WIDTH * scale;
    const fullH = VIEW_HEIGHT * scale;
    const offsetX = (fullW - w) / 2;
    const offsetY = (fullH - h) / 2;

    return {
        position: 'absolute',
        width: `${fullW}px`,
        height: `${fullH}px`,
        left: `${-offsetX}px`,
        top: `${-offsetY}px`,
        pointerEvents: 'none',
        zIndex: 100
    };
});
</script>
<template>
    <div class="scene-container" :style="containerStyle" @click="onMapClick">
        <img v-if="backgroundImageUrl" :src="backgroundImageUrl" class="scene-background" alt="Background" />
        <div ref="canvasContainer" class="three-canvas-container"></div>
        <div v-if="!landingDone" class="landing-overlay">
            system_init_landing
        </div>

        <!-- Debug Overlay Layer -->
        <div v-if="show3DHelpers" class="debug-ui-overlay" :style="debugOverlayStyle">
            <svg class="debug-svg" :viewBox="`0 0 ${VIEW_WIDTH} ${VIEW_HEIGHT}`" preserveAspectRatio="none">
                <g v-for="(gw, idx) in gatewaysToDraw" :key="idx">
                    <rect
                        :x="(gw.x / 100) * VIEW_WIDTH"
                        :y="(gw.y / 100) * VIEW_HEIGHT"
                        :width="(gw.width / 100) * VIEW_WIDTH"
                        :height="(gw.height / 100) * VIEW_HEIGHT"
                        class="debug-gateway-rect"
                    />
                    <text
                        :x="(gw.x / 100) * VIEW_WIDTH + 5"
                        :y="(gw.y / 100) * VIEW_HEIGHT + 15"
                        class="debug-gateway-label"
                    >
                        GW: {{ gw.label || 'AUTO' }} -> {{ gw.target_scene_id }}
                    </text>
                </g>
            </svg>
        </div>
        <div v-if="activeDialogue" class="dialogue-layer">
            <div class="dialogue-content">
                <!-- NPC Dialogue Box -->
                <transition name="fade">
                    <div v-if="typewriterText" class="npc-dialogue-box">
                        <div class="npc-name">
                            {{ dialogueNPCName || 'contact' }}
                        </div>
                        <div class="dialogue-text">
                            {{ typewriterText }}<span class="text-cursor">_</span>
                        </div>
                    </div>
                </transition>
                <!-- Player Options -->
                <transition name="slide-up">
                    <div v-if="showDialogueOptions" class="options-container">
                        <button v-for="(option, idx) in activeDialogue.tree.nodes[currentNodeId].options" :key="idx" @click="selectOption(option)" class="option-button">
                            <div class="option-text-wrapper">
                                <span class="option-number">[{{ String(idx + 1).padStart(2, '0') }}]</span>
                                <span class="option-label">{{ option.text }}</span>
                            </div>
                            <span class="option-hint">select_response >></span>
                        </button>
                    </div>
                </transition>
            </div>
        </div>
    </div>
</template>

<style scoped>
.scene-container {
    position: relative;
    border: var(--noir-border-width, 4px) solid #0a0a0a;
    box-shadow: 0 0 50px rgba(0,0,0,0.5);
    background-color: #000;
    overflow: hidden;
    margin-left: auto;
    margin-right: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
}

.debug-ui-overlay {
    pointer-events: none;
    z-index: 100;
    overflow: hidden;
}

.debug-svg {
    width: 100%;
    height: 100%;
}

.debug-gateway-rect {
    fill: rgba(255, 0, 255, 0.2);
    stroke: #ff00ff;
    stroke-width: 2;
    stroke-dasharray: 4 2;
}

.debug-gateway-label {
    fill: #ff00ff;
    font-size: 10px;
    font-family: monospace;
    font-weight: bold;
    text-transform: uppercase;
}

/* In engine mode, remove borders and margins for a cleaner look */
:where(.scene-container) {
    --noir-border-width: 4px;
}

.scene-background {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    pointer-events: none;
}

.three-canvas-container {
    position: absolute;
    inset: 0;
    pointer-events: auto;
    z-index: 10;
}

.landing-overlay {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background-color: rgba(0, 0, 0, 0.6);
    color: #00ffcc;
    padding: 0.25rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    border: 1px solid rgba(0, 255, 204, 0.3);
    animation: animate-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.dialogue-layer {
    position: absolute;
    left: 0;
    right: 0;
    /*bottom: 1rem;*/
    top: 55%;
    z-index: 40;
    display: flex;
    flex-direction: column;
    align-items: center;
    pointer-events: none;
    padding-left: 3rem;
    padding-right: 3rem;
    padding-bottom: 2rem;
}

.dialogue-content {
    max-width: 48rem; /* 3xl */
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.5rem;
}

.npc-dialogue-box {
    width: 100%;
    background-color: rgba(10, 10, 10, 0.85); /* noir-panel */
    backdrop-filter: blur(12px);
    border: 1px solid rgba(0, 255, 204, 0.3);
    padding: 1.5rem;
    border-radius: 0.5rem;
    pointer-events: auto;
    box-shadow: 0 4px 24px rgba(0,0,0,0.8);
}

.npc-name {
    font-size: 10px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    color: #00ffcc;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.2em;
}

.dialogue-text {
    color: #fff;
    font-size: 1.25rem;
    line-height: 1.625;
    font-weight: 300;
    font-style: italic;
}

.text-cursor {
    animation: animate-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    opacity: 0.5;
}

.options-container {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 0.5rem;
    width: 100%;
    max-width: 36rem; /* xl */
    pointer-events: auto;
}

.option-button {
    width: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    border: 1px solid rgba(0, 255, 204, 0.2);
    padding: 0.75rem 1.5rem;
    border-radius: 0.25rem;
    text-align: left;
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 300ms;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
}

.option-button:hover {
    background-color: rgba(0, 255, 204, 0.2);
    border-color: rgba(0, 255, 204, 0.6);
}

.option-text-wrapper {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.option-number {
    font-size: 0.75rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    color: rgba(0, 255, 204, 0.4);
}

.option-button:hover .option-number {
    color: #00ffcc;
}

.option-label {
    color: #fff;
    font-size: 0.875rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.option-hint {
    font-size: 8px;
    color: #00ffcc;
    opacity: 0;
    transition-property: opacity;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 300ms;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    text-align: right;
}

.option-button:hover .option-hint {
    opacity: 1;
}

@keyframes animate-pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: .5;
    }
}

:deep(canvas) {
    background: transparent !important;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
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
</style>