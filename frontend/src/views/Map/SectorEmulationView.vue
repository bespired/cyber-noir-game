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

// Animation refs
let characterMixer = null;
const characterActions = {};
let activeAction = null;
const isCaution = ref(false);

// Scaling & Visibility refs
const characterScale = ref(0.5);
const vehicleScale = ref(0.5);
const show3DHelpers = ref(false);
const ambientIntensity = ref(0.8);
const sunIntensity = ref(1.0);

const VIEW_WIDTH = 1216;
const VIEW_HEIGHT = 832;
const ASPECT_RATIO = VIEW_WIDTH / VIEW_HEIGHT;

const slugify = (str) => {
    if (!str) return '';
    return str.toLowerCase().replace(/[^\w\s-]/g, '').replace(/[\s_]+/g, '-').replace(/^-+|-+$/g, '');
};

const getImageUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    const cleanPath = path.startsWith('/') ? path : `/${path}`;
    return `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/storage${cleanPath}`;
};

const getGlbUrl = (location, sector) => {
    if (!location || !sector) return '';
    const sectorSlug = slugify(sector.naam);
    const locationSlug = slugify(location.naam);
    return `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/storage/glb/${sectorSlug}--${locationSlug}.glb`;
};

const getCharacterGlbUrl = (name) => {
    if (!name) return '';
    const slug = slugify(name);
    return `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/storage/glb/${slug}.glb`;
};

const getVehicleGlbUrl = (path) => {
    if (!path) return '';
    // Strip 'artwork/' if it's at the start, as 3D is in glb/
    let cleanPath = path.replace(/^artwork\//, '');
    cleanPath = cleanPath.startsWith('/') ? cleanPath : `/${cleanPath}`;
    return `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/storage${cleanPath}`;
};

// Responsive Logic
const containerWidth = ref(VIEW_WIDTH);
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
        sectorData.value = sectorRes.data;
        scenesInSector.value = sectorData.value.scenes || [];
        settings.value = settingsRes.data;
        gedragingen.value = gedragRes.data;


        // Find start scene (scene with vehicle spawn point)
        for (const s of scenesInSector.value) {
            const locRes = await axios.get(`/api/locaties/${s.locatie_id}`);
            const loc = locRes.data;
            const allSpawnPoints = loc.spawn_points || {};
            const spawnPoints = allSpawnPoints[sectorId] || allSpawnPoints[Number(sectorId)] || [];
            if (spawnPoints.some(p => p.type === 'vehicle')) {
                currentScene.value = { ...s, location: loc };
                break;
            }
        }

        if (!currentScene.value && scenesInSector.value.length > 0) {
            // Fallback to first scene
            const s = scenesInSector.value[0];
            const locRes = await axios.get(`/api/locaties/${s.locatie_id}`);
             currentScene.value = { ...s, location: locRes.data };
        }

    } catch (e) {
        console.error("Failed to fetch data", e);
        error.value = "Failed to load sector data.";
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

        // 1. Cleanup before loading
        if (currentGltf) { scene.remove(currentGltf); currentGltf = null; }
        if (playableCharacter) { scene.remove(playableCharacter); playableCharacter = null; }
        if (vehicle) { scene.remove(vehicle); vehicle = null; }

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
                const charPos = charSpawnPoint ||
                                (vehicleSpawn ? { x: vehicleSpawn.x + 2, y: vehicleSpawn.y, z: vehicleSpawn.z } : { x: 0, y: 0, z: 0 });

                spawnCharacter(charPos).then(() => {
                    spawnProps(spawnPoints).then(resolve);
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

const spawnCharacter = (spawnPoint) => {
    return new Promise((resolve) => {
        const charUrl = getCharacterGlbUrl(settings.value.playable);
        if (!charUrl) {
            resolve();
            return;
        }

        const loader = new GLTFLoader();
        loader.load(charUrl, (gltf) => {
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
            playableCharacter.traverse(child => {
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

const spawnProps = async (spawnPoints) => {
    // Cleanup old props
    props.forEach(p => scene.remove(p));
    props.length = 0;

    const propSpawnPoints = spawnPoints.filter(p => p.type === 'aanwijzing');
    if (propSpawnPoints.length === 0) return;

    const loader = new GLTFLoader();

    for (const p of propSpawnPoints) {
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
    if (!playableCharacter || !landingDone.value) return;

    const rect = renderer.domElement.getBoundingClientRect();
    const mouseX = ((e.clientX - rect.left) / rect.width) * 2 - 1;
    const mouseY = -((e.clientY - rect.top) / rect.height) * 2 + 1;

    // 1. Check for Gateway Click (Screen Space)
    const screenX = (mouseX + 1) / 2 * 100;
    const screenY = -(mouseY - 1) / 2 * 100;

    const clickedGateway = currentScene.value?.gateways?.find(gw =>
        screenX >= gw.x && screenX <= gw.x + gw.width &&
        screenY >= gw.y && screenY <= gw.y + gw.height
    );

    if (clickedGateway) {
        // Find nearest waypoint to the gateway
        const currentSectorId = route.params.id;
        const allSpawnPoints = currentScene.value.location?.spawn_points || {};
        const spawnPoints = allSpawnPoints[currentSectorId] || allSpawnPoints[Number(currentSectorId)] || [];
        const waypoints = spawnPoints.filter(p => p.type === 'waypoint');

        if (waypoints.length > 0) {
            let nearest = null;
            let minDist = Infinity;

            waypoints.forEach(wp => {
                // Project waypoint to screen space to find one closest to click
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
                pendingGateway.value = clickedGateway;
                targetPosition.set(nearest.x, nearest.y, nearest.z);
                isWalking.value = true;
                if (targetPointMesh) {
                    targetPointMesh.position.copy(targetPosition);
                    targetPointMesh.position.y += 0.01;
                    targetPointMesh.visible = true;
                }
                return;
            }
        }
    }

    // 2. Standard Floor Raycasting
    pendingGateway.value = null; // Reset if floor clicked
    raycaster.setFromCamera(new THREE.Vector2(mouseX, mouseY), camera);
    const intersects = raycaster.intersectObjects(scene.children, true);

    // Check floor or any mesh (fallback)
    const floorIntersect = intersects.find(i => i.object.name === 'floor' || i.object.name === 'plane' || i.object.isMesh);
    if (floorIntersect) {
        targetPosition.copy(floorIntersect.point);
        isWalking.value = true;
        if (targetPointMesh) {
            targetPointMesh.position.copy(floorIntersect.point);
            targetPointMesh.position.y += 0.01; // Avoid Z-fighting
            targetPointMesh.visible = true;
        }
    }
};

const triggerGateway = async (gateway) => {
    // 1. Run Behavior if present
    if (gateway.gedrag_id) {
        const gedrag = gedragingen.value.find(g => g.id === gateway.gedrag_id);
        if (gedrag && gedrag.acties) {
            console.log("Triggering behavior:", gedrag.naam);
            for (const action of gedrag.acties) {
                await runAction(action);
            }
        }
    }

    // 2. Swap scene if present
    if (gateway.target_scene_id) {
        await swapScene(gateway);
    }
};

const runAction = async (action) => {
    console.log("Running action:", action.type, action.value);
    switch (action.type) {
        case 'SET GAME TAG':
            if (!gameState.tags.includes(action.value)) {
                gameState.tags.push(action.value);
                toast.success(`TAG_ACQUIRED: ${action.value}`);
            }
            break;
        case 'REMOVE GAME TAG':
            gameState.tags = gameState.tags.filter(t => t !== action.value);
            break;
        case 'GOTO SCENE':
            // Recursive scene swap if target is ID
            if (!isNaN(action.value)) {
                await swapScene({ target_scene_id: parseInt(action.value) });
            }
            break;
        case 'WAIT x SECONDS':
            await new Promise(res => setTimeout(res, parseFloat(action.value) * 1000));
            break;
        case 'START_DIALOGUE':
             // If we have a dialogue ID, we could show an overlay or redirect
             // For now just log it
             toast.info(`DIALOGUE_REQUESTED: ${action.value}`);
             break;
    }
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
        if (targetPointMesh) targetPointMesh.visible = false;

        // When swapping, landing is already done
        landingDone.value = true;

        await loadSceneGLB(currentScene.value, gateway.target_spawn_point);
    } catch (e) {
        console.error("Failed to swap scene", e);
    } finally {
        loading.value = false;
    }
};

const checkGateways = () => {
    if (!currentScene.value || !currentScene.value.gateways || !playableCharacter) return;

    // Project character position to screen space
    const vector = playableCharacter.position.clone();
    vector.project(camera);

    const x = (vector.x + 1) / 2 * 100;
    const y = -(vector.y - 1) / 2 * 100;

    for (const gw of currentScene.value.gateways) {
        if (x >= gw.x && x <= gw.x + gw.width && y >= gw.y && y <= gw.y + gw.height) {
            triggerGateway(gw);
            break;
        }
    }
};


const animate = () => {
    animationFrameId = requestAnimationFrame(animate);
    const delta = clock ? clock.getDelta() : 0.016;

    if (characterMixer) {
        characterMixer.update(delta);
    }

    if (isWalking.value && playableCharacter) {
        const distance = playableCharacter.position.distanceTo(targetPosition);
        if (distance > 0.1) {
            // Rotate towards target
            const lookPos = new THREE.Vector3(targetPosition.x, playableCharacter.position.y, targetPosition.z);
            playableCharacter.lookAt(lookPos);

            // Move towards target
            const direction = new THREE.Vector3().subVectors(targetPosition, playableCharacter.position).normalize();
            playableCharacter.position.add(direction.multiplyScalar(characterSpeed * delta));
        } else {
            isWalking.value = false;
            if (targetPointMesh) targetPointMesh.visible = false;
            
            if (pendingGateway.value) {
                triggerGateway(pendingGateway.value);
                pendingGateway.value = null;
            } else {

                checkGateways();
            }
        }
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
            <div class="flex items-center text-sm text-noir-muted mb-4">
                <RouterLink :to="`/map/${sectorId}`" class="hover:text-white">&lt; TERUG_NAAR_SECTOR</RouterLink>
                <span class="mx-2">/</span>
                <span class="text-white uppercase mr-4">Sector_Emulatie_Mode</span>

                <div v-if="currentScene" class="ml-auto">
                    <RouterLink
                        :to="`/locaties/${currentScene.locatie_id}/sector/${sectorId}/3d`"
                        class="btn btn--small btn--primary"
                    >
                        WIJZIG_3D_SCENE
                    </RouterLink>
                </div>
            </div>

            <div v-if="error" class="bg-noir-panel border border-noir-danger p-6 rounded text-noir-danger text-center">
                {{ error }}
            </div>

            <div class="flex flex-col lg:flex-row gap-8 items-start relative">
                <!-- Loading Overlay -->
                <div v-if="loading" class="absolute inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm pointer-events-auto">
                    <div class="flex flex-col items-center gap-4">
                        <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-noir-accent font-mono text-[8px] flex items-center justify-center">LADEN</div>
                        <div class="text-noir-accent font-mono text-[10px] animate-pulse uppercase tracking-[0.2em]">Data_Transfer_In_Progress...</div>
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
                            SISTEEM_INITIALISATIE: VEHICLE_LANDING_INGESCHAKELD...
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                         <div class="bg-noir-panel p-4 border border-noir-dark rounded">
                            <h3 class="text-white font-bold mb-2 uppercase border-b border-noir-dark pb-1 text-[10px]">SCENE_INFO</h3>
                            <p class="text-noir-text">SEC: {{ sectorData?.naam }}</p>
                            <p class="text-noir-text">SCN: {{ currentScene?.titel }}</p>
                        </div>
                        <div class="bg-noir-panel p-4 border border-noir-dark rounded">
                            <h3 class="text-white font-bold mb-2 uppercase border-b border-noir-dark pb-1 text-[10px]">CHARACTER</h3>
                            <p class="text-noir-text">PLAYABLE: {{ settings.playable }}</p>
                            <p class="text-noir-text">MODE: {{ isWalking ? 'WALKING' : 'IDLE' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Info Sidebar -->
                <div class="w-full lg:w-96 shrink-0 space-y-6">
                    <div class="bg-noir-panel p-4 border border-noir-dark rounded text-xs space-y-4">
                        <h3 class="text-white font-bold mb-2 uppercase border-b border-noir-dark pb-1 text-[10px]">BESTURING</h3>
                        <p class="text-noir-text font-mono mb-2">• CLICK_FLOOR: MOVE_CHARACTER</p>
                        <p class="text-noir-text font-mono mb-2">• WALK_TO_WAYPOINT: SWAP_SCENE</p>

                        <div class="pt-4 border-t border-noir-dark/50 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] text-noir-muted uppercase">3D Helpers</span>
                                <button @click="show3DHelpers = !show3DHelpers"
                                        class="w-8 h-4 rounded-full relative transition-colors duration-200"
                                        :class="show3DHelpers ? 'bg-noir-accent' : 'bg-noir-dark'">
                                    <div class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full transition-transform duration-200"
                                         :class="show3DHelpers ? 'translate-x-4' : ''"></div>
                                </button>
                            </div>

                            <div v-if="characterActions['caution']" class="flex items-center justify-between">
                                <span class="text-[10px] text-noir-muted uppercase">Caution Mode</span>
                                <button @click="isCaution = !isCaution"
                                        class="w-8 h-4 rounded-full relative transition-colors duration-200"
                                        :class="isCaution ? 'bg-noir-danger' : 'bg-noir-dark'">
                                    <div class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full transition-transform duration-200"
                                         :class="isCaution ? 'translate-x-4' : ''"></div>
                                </button>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-[10px] text-noir-muted uppercase">Ambient</span>
                                <input type="range" v-model.number="ambientIntensity" min="0" max="2" step="0.1" class="w-24 accent-noir-accent" />
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-[10px] text-noir-muted uppercase">Sun</span>
                                <input type="range" v-model.number="sunIntensity" min="0" max="2" step="0.1" class="w-24 accent-noir-accent" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-noir-panel p-4 border border-noir-dark rounded text-[10px] text-noir-muted italic">
                         <div class="flex items-center gap-2 mb-2">
                             <div class="w-2 h-2 rounded-full bg-noir-accent animate-pulse"></div>
                             <span>VLOER_VISIBILITEIT: ACTIEF</span>
                         </div>
                         <p>COLLISION_DETECTION: OPTISCH</p>
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
