<script setup>
import { ref, onMounted, onUnmounted, computed, defineProps, defineEmits } from 'vue';
import { useDataRobustness } from '../../composables/useDataRobustness';
import { sectorData } from '../../sectordata.js';


const { fetchData, resolveImgUrl, isEngine } = useDataRobustness();

const sectors  = ref([]);
const loading  = ref(true);
const progress = ref({ discovered: [] });
const gpsCoords = ref({ x: '0000', y: '0000', zone: 'N-0' }); // HUD Stats
const sectorLog = ref([]); // Persistent Log

const addLog = (msg, type = 'INFO') => {
   const time = new Date().toLocaleTimeString('nl-NL', { hour12: false });
   sectorLog.value.unshift({ id: Date.now() + Math.random(), time, msg, type });
   if (sectorLog.value.length > 8) sectorLog.value.pop();
};

let hoverTimer = null;
let randomLogTimer = null;

const randomMessages = [
    { msg: "WEATHER DRONE INFO: ACID RAIN", type: "INFO" },
    { msg: "ENCRYPTED PACKET RECEIVED", type: "NET"  },
    { msg: "PING: 12ms TO MAINFRAME",   type: "NET"  },
    { msg: "PACKET LOST: RETRYING",     type: "NET"  },
    { msg: "UPDATING LOCAL MAP...",     type: "SYS"  },
    { msg: "UNKNOWN SIGNAL DETECTED",   type: "SYS"  },
    { msg: "BACKGROUND SCAN: CLEAN",    type: "SYS"  },
    { msg: "MEMORY OPTIMIZATION...",    type: "SYS"  },
    { msg: "POLICE FREQ INTERCEPTED",   type: "SYS"  },
    { msg: "THERMAL EMISSION: NORMAL",  type: "INFO" },
];

const startRandomLogs = () => {
    const delay = Math.random() * 4000 + 3000; // 3-7 seconds
    randomLogTimer = setTimeout(() => {
        if(Math.random() > 0.3) {
             const item = randomMessages[Math.floor(Math.random() * randomMessages.length)];
             addLog(item.msg, item.type);
        }
        startRandomLogs();
    }, delay);
};

const handleSectorHover = (sector) => {
    if (hoverTimer) clearTimeout(hoverTimer);

    addLog(`TARGET LOCKED: ${sector.naam.toUpperCase()}`, 'SCAN');
    hoverTimer = setTimeout(() => {
        if (sector.beschrijving) {
            addLog(`${sector.beschrijving}`, 'INFO');
        }
    }, 800);
};

const handleSectorClick = (sector) => {
    // 1. Determine Entry Scene
    let targetSceneId = null;
    
    if (sector.scenes && sector.scenes.length > 0) {
        // Feature: "If the sector has no entry in status... lowest ID is the entry scene"
        // TODO: Check progress/status for last visited scene in this sector if needed.
        // For now, defaulting to the scene with the LOWEST ID.
        const sortedScenes = [...sector.scenes].sort((a, b) => Number(a.id) - Number(b.id));
        targetSceneId = sortedScenes[0].id;
    } else {
        addLog(`ERROR: NO SCENE DATA FOR SECTOR ${sector.id}`, 'SYS');
        console.warn("Sector click failed: No scenes found in sector data", sector);
        return;
    }

    // Log for local HUD
    addLog(`INITIATING JUMP TO SECTOR ${sector.id} (SCENE ${targetSceneId})...`, 'SYS');
    
    // Emit for Engine/Emulator
    emit('scene-complete', { 
        targetSceneId: targetSceneId,
        sectorData: sector 
    });
};

const updateGps = (e) => {
    const rect = e.currentTarget.getBoundingClientRect();
    const x = (e.clientX - rect.left) / rect.width;
    const y = (e.clientY - rect.top) / rect.height;

    // Fake Noir-style coordinates
    gpsCoords.value = {
        x: (12400 + (x * 1000)).toFixed(2),
        y: (4500 - (y * 1000)).toFixed(2),
        zone: `SEC-${Math.floor(x * 8) + 1}${String.fromCharCode(65 + Math.floor(y * 4))}`
    };
};

const props = defineProps({
    nextSceneId: { type: [String, Number], default: null },
    opensectors: { type: Array, default: () => [] }, // IDs passed from editor
    currentSectorId: { type: [String, Number], default: 1 } // Default to 1 for testing
});

const emit = defineEmits(['scene-complete']);

onMounted(async () => {
    addLog('SYSTEM INITIALIZED', 'SYS');
    addLog('CONNECTING TO SAT-LINK...', 'NET');
    setTimeout(() => addLog('CONNECTION ESTABLISHED', 'OK'), 800);
    startRandomLogs();
    loadProgress();
    await fetchSectors();
});

onUnmounted(() => {
    if (hoverTimer) clearTimeout(hoverTimer);
    if (randomLogTimer) clearTimeout(randomLogTimer);
});

const loadProgress = () => {
    const saved = localStorage.getItem('cyber-noir-progress');
    if (saved) {
        try {
            progress.value = JSON.parse(saved);
        } catch (e) {
            console.error("Failed to load progress", e);
        }
    }
};

const saveProgress = () => {
    localStorage.setItem('cyber-noir-progress', JSON.stringify(progress.value));
};

const fetchSectors = async () => {
    loading.value = true;
    
    if (isEngine.value && sectorData && sectorData.length > 0) {
        sectors.value = sectorData;
        loading.value = false;
        return;
    }

    try {
        const data = await fetchData('sectors.json', 'sectoren');
        sectors.value = data;
    } catch (e) {
        console.error("Failed to fetch sectors", e);
    } finally {
        loading.value = false;
    }
};

const getSectorStyle = (sector) => {
    let path = sector.artwork[0]?.bestandspad.replace('artwork/','')
    let background = "linear-gradient(to top, rgb(0,0,0), transparent)"

    return {
        backgroundImage: `${background}, url( ${resolveImgUrl(path)}`,
        left:   `${(sector.x / 1536) * 100}%`,
        top:    `${(sector.y / 1024) * 100}%`,
        width:  `${(sector.width  / 1536) * 110}%`,
        height: `${(sector.height / 1024) * 90}%`
    };
};

const isSectorOpen = (id) => {
    if (!isEngine.value) return true

    // A sector is open if it's in the prop list OR already discovered in localStorage progress
    let fid= parseInt(id)
    return props.opensectors.includes(fid) ||
           progress.value.discovered.includes(fid)
};


</script>

<template>
    <div class="scene-container">
        <!-- Layer 1: Car Interior Background -->
        <div class="background-layer"
            :style="`background-image: url(${resolveImgUrl('/algemeen/inside.png')})`" ></div>

        <!-- Layer 2: TV HUD Perspective Wrapper -->
        <div class="hud-perspective-wrapper">
            <div class="tv-frame-container">
                <!-- Layer 3: TV Frame (Overlay) -->
                <img :src="resolveImgUrl('/algemeen/hud.png')" class="tv-frame-img" alt="TV HUD Frame">

                <!-- Layer 4: Screen Content -->
                <div class="tv-screen-content" @mousemove="updateGps">

                    <div v-if="loading" class="matrix-loading">
                        <img src="/font/loading.svg" />
                        <div class="glitch-text">
                            BOOTING
                        </div>
                    </div>

                    <template v-if="!loading">
                        <!-- Map Image Background -->
                        <img :src="resolveImgUrl('/algemeen/map-noir.png')"
                            class="map-img" alt="Sector Map">

                         <!-- HUD GPS & Wires Layer -->
                         <div class="hud-overlay">
                            <!-- Top Left: Sector Log Panel -->
                            <div class="sector-log-panel">
                                <div class="sd-header">SYSTEM LOG /// RECORDING</div>
                                <div class="log-entries">
                                    <div v-for="entry in sectorLog" :key="entry.id" class="log-row">
                                        <span class="log-time">[{{ entry.time }}]</span>
                                        <span class="log-type" :class="entry.type">{{ entry.type }}</span>
                                        <span class="log-msg">> {{ entry.msg }}</span>
                                    </div>
                                </div>
                                <div class="sd-deco-bar"></div>
                            </div>

                            <div class="gps-readout">
                                <span class="gps-label">TRGT:</span> {{ gpsCoords.x }} // {{ gpsCoords.y }} <br>
                                <span class="gps-label">ZONE:</span> {{ gpsCoords.zone }}
                            </div>

                         </div>

                            <template v-for="sector in sectors" :key="sector.id" >
                                <div
                                    v-if="isSectorOpen(sector.id)"
                                    :style="getSectorStyle(sector)"
                                    class="sector-node"
                                    @mouseenter="handleSectorHover(sector)"
                                    @click="handleSectorClick(sector)"
                                >
                                    <div class="node-border"></div>
                                    <div class="node-name">{{ sector.naam }}</div>
                                    
                                    <!-- Current Location Marker -->
                                    <div v-if="sector.id == currentSectorId" class="current-location-marker">
                                        <div class="pulse-ring"></div>
                                        <div class="center-dot"></div>
                                    </div>

                                </div>
                            </template>

                        <!-- <div class="scanline-overlay"></div> -->
                        <!-- <div class="vignette"></div> -->
                    </template>
                </div>
            </div>
        </div>

        <div class="interaction-hint">
            <span class="accent-text">SCANNING SECTORS</span> | KIES JE SECTOR
        </div>
    </div>
</template>

<style scoped>

.scene-container {
    position: relative;
    width: 100%;
    height: 100vh;
    overflow: hidden;
    background-color: #000;
    font-family: 'Wallace', 'Courier New', Courier, monospace;
}

/* Layer 1: Background */
.background-layer {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    /*filter: brightness(0.4) contrast(1.2) blur(2px);*/
    filter: blur(2px);
    z-index: 1;
}

/* Layer 2: Perspective Wrapper */
.hud-perspective-wrapper {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    perspective: 1200px;
    z-index: 10;
}

.tv-frame-container {
    position: relative;
    width: auto;
    height: 88vh;
    max-width: 95vw;
    aspect-ratio: 4 / 3;
    transform: rotateY(-8deg) rotateX(3deg);
    filter: drop-shadow(0 0 50px rgba(0, 255, 204, 0.1));
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.5s cubic-bezier(0.23, 1, 0.32, 1);
}

.tv-frame-container:hover {
    transform: rotateY(-4deg) rotateX(1.5deg);
}

/* Layer 3: The Frame */
.tv-frame-img {
    position: relative;
    height: 100%;
    width: auto;
    display: block;
    z-index: 20;
    pointer-events: none;
}

/* Layer 4: The Content */
.tv-screen-content {
    position: absolute;
    top: 11%;
    left: 11.5%;
    right: 18.5%;
    bottom: 12%;
    background-color: #010408;
    overflow: hidden;
    z-index: 15;
    border-radius: 4px;
    border: 1px solid rgba(0, 255, 204, 0.2);
}

.matrix-loading {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #000;
}

.matrix-loading img {
    width: 20%;
    height: 100%;
    align-items: center;
    justify-content: center;
    position: absolute;
    margin-top: 4px;
    margin-left: 4px;
}

.map-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0.7;
    filter: brightness(0.8) contrast(1.1) sepia(0.2) hue-rotate(320deg);
}

/* Sector Nodes */
.sector-node {
    position: absolute;
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 10;
    background-size: cover;
    background-position: center;
    /*filter: brightness(0.8) contrast(1.1) sepia(0.2) hue-rotate(180deg);*/
}

.node-border {
    position: absolute;
    inset: 0;
    border: 1px solid rgba(0, 255, 204, 0.3);
    background: rgba(0, 255, 204, 0.05);
    transition: all 0.3s ease;
}

.sector-node:hover .node-border {
    border-color: #00ffcc;
    background: rgba(0, 255, 204, 0.15);
    box-shadow: 0 0 15px rgba(0, 255, 204, 0.3);
    transform: scale(1.05);
}


.node-name {
    position: absolute;
    left:8px;
    bottom: 4px;
    width: 80%;
    font-size: 1vh;
    text-transform: uppercase;
    overflow-wrap: anywhere;
}



.interaction-hint {
    position: absolute;
    bottom: 3rem;
    left: 50%;
    transform: translateX(-50%);
    color: #fff;
    font-size: 0.7rem;
    letter-spacing: 0.15rem;
    text-transform: uppercase;
    background: rgba(0, 20, 30, 0.6);
    padding: 0.5rem 1.5rem;
    border-top: 1px solid rgba(0, 255, 204, 0.3);
    border-bottom: 1px solid rgba(0, 255, 204, 0.3);
    backdrop-blur: 4px;
    z-index: 40;
}

.matrix-loading {
    animation: fadeIn 100ms ease-in-out both;
    animation-delay: 1s;
}
/* Glitch Animation for loading */
.glitch-text {
    color: #00ffcc;
    font-size: 2vh;
    margin-top: -2.7vh;
    position: relative;

}


@keyframes fadeIn {
  from { opacity: 0; scale: 0}
  80%  { opacity: 1; scale: 1.1}
  to   { opacity: 1; scale: 1}
}

/* HUD Styles */
.hud-overlay {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 5; /* Between map (1) and sectors (10) */
    overflow: hidden;
}

.gps-readout {
    position: absolute;
    top: 20px;
    right: 15px;
    font-family: 'Courier New', monospace;
    font-size: 10px;
    color: rgba(0, 255, 204, 0.8);
    text-align: right;
    line-height: 1.4;
    text-shadow: 0 0 2px rgba(0, 255, 204, 0.5);
}

.gps-label {
    color: rgba(0, 255, 204, 0.4);
    font-size: 8px;
}

.hud-wires {
    width: 100%;
    height: 100%;
    opacity: 0.6;
}

.hud-line {
    stroke: rgba(0, 255, 204, 0.3);
    stroke-width: 0.2;
    fill: none;
}

.op-10 { opacity: 0.1; }
.op-30 { opacity: 0.3; }

.data-dot {
    filter: drop-shadow(0 0 2px #00ffcc);
}

/* Sector Log Panel */
.sector-log-panel {
position: absolute;
    top: 20px;
    left: 19px;
    width: 260px;
    box-shadow: 0 0 15px rgba(0, 255, 204, 0.1);
    font-family: 'Wallace';
    z-index: 20;
    pointer-events: none;
    min-height: 150px;
    opacity: .6;
}

.sd-header {
    font-size: 10px;
    color: #00ffcc;
    border-bottom: 1px dashed rgba(0, 255, 204, 0.3);
    padding-bottom: 4px;
    margin-bottom: 8px;
    letter-spacing: 1px;
    font-weight: bold;
    opacity: 0.8;
}

.log-entries {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.log-row {
    font-size: 9px;
    display: flex;
    align-items: center;
    gap: 6px;
    transform-origin: left;
    animation: fadeIn 0.2s ease-out;
}

.log-time {
    color: rgba(0, 255, 204, 0.4);
}

.log-type {
    font-weight: bold;
    min-width: 25px;
    font-size: 7px;
}
.log-type.SYS  { color: #00ccff; }
.log-type.NET  { color: #ffcc00; }
.log-type.OK   { color: #00ff66; }
.log-type.SCAN { color: #00ff77; }
.log-type.INFO { color: rgba(0, 255, 204, 0.7); }

.log-msg {
    color: rgba(200, 255, 200, 0.9);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-transform: uppercase;
    font-size: 7px;
}

.sd-deco-bar {
    height: 1px;
    width: 40%;
    background: #00ffcc;
    margin-top: 12px;
    opacity: 0.3;
}

/* Current Location Marker */
.current-location-marker {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    pointer-events: none;
    z-index: 15;
    display: flex;
    align-items: center;
    justify-content: center;
}

.center-dot {
    width: 6px;
    height: 6px;
    background: #00ff66;
    border-radius: 50%;
    box-shadow: 0 0 10px #00ff66;
}

.pulse-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    border: 1px solid #00ff66;
    border-radius: 50%;
    animation: locationPulse 2s infinite;
    opacity: 0;
}

@keyframes locationPulse {
    0% { transform: scale(0.5); opacity: 1; }
    100% { transform: scale(2); opacity: 0; }
}

@media (max-width: 768px) {
    .tv-frame-container {
        height: auto;
        width: 98vw;
        rotate: none !important;
        transform: scale(0.9) !important;
    }
    .interaction-hint {
        bottom: 1rem;
        font-size: 0.5rem;
        width: 90%;
        text-align: center;
    }
}

.accent-text {
    color: #00ffcc;
}
</style>