<script setup>
import { ref, onMounted, computed } from 'vue';
import Character3DViewer from '../Character3DViewer.vue';
import ClickButton from '../inputs/ClickButton.vue';
import { useDataRobustness } from '../../composables/useDataRobustness';

const props = defineProps({
    title: { type: String, default: 'SELECT_RUNNER' },
    nextSceneId: { type: [String, Number], default: null }
});

const emit = defineEmits(['scene-complete']);

const { fetchData, resolveAssetUrl, getCharacterGlbUrl } = useDataRobustness();

const characters = ref([]);
const currentIndex = ref(0);
const loading = ref(true);

const loadCharacters = async () => {
    try {
        const data = await fetchData('personages.json', 'personages');
        characters.value = data.filter(p => p.is_playable);
        loading.value = false;
    } catch (err) {
        console.error('Failed to load character data:', err);
    }
};

const currentCharacter = computed(() => characters.value[currentIndex.value] || null);

const nextCharacter = () => {
    currentIndex.value = (currentIndex.value + 1) % characters.value.length;
};

const prevCharacter = () => {
    currentIndex.value = (currentIndex.value - 1 + characters.value.length) % characters.value.length;
};

const selectCharacter = () => {
    if (!currentCharacter.value) return;
    
    console.log('Selected character:', currentCharacter.value.naam);
    localStorage.setItem('player_character', JSON.stringify(currentCharacter.value));
    
    emit('scene-complete', { 
        targetSceneId: props.nextSceneId,
        selectedCharacterId: currentCharacter.value.id 
    });
};

const getGlbUrl = (personage) => {
    if (!personage) return '';
    
    // 1. Try to find the first artwork that ends with .glb in the database
    if (personage.artwork && personage.artwork.length > 0) {
        const glbArtwork = personage.artwork.find(a => a.bestandspad.toLowerCase().endsWith('.glb'));
        if (glbArtwork) {
            return resolveAssetUrl(glbArtwork.bestandspad);
        }
    }
    
    // 2. Fallback: Try to calculate the path from the character's name/slug
    return getCharacterGlbUrl(personage.naam);
};

onMounted(() => {
    loadCharacters();
});
</script>

<template>
    <div class="selection-container">
        <h1 class="scene-title glitch-text" :data-text="title">{{ title }}</h1>

        <div v-if="loading" class="loading-state">
            <div class="loading-spinner"></div>
            <p>BUFFERING_NEURAL_RECORDS...</p>
        </div>

        <div v-else-if="currentCharacter" class="selector-content">
            <!-- Left Arrow -->
            <div class="nav-control" @click="prevCharacter">
                <span class="nav-arrow">&lt;</span>
            </div>

            <!-- 3D View Card -->
            <div class="character-card">
                <div class="viewer-wrapper">
                    <Character3DViewer 
                        v-if="getGlbUrl(currentCharacter)"
                        :glb-url="getGlbUrl(currentCharacter)" 
                        type="persoon"
                        class="viewer-component"
                    />
                    <div v-else class="error-placeholder">
                        SCAN_ERROR: GEOMETRY_MISSING
                    </div>
                </div>

                <div class="character-info">
                    <h2 class="name">{{ currentCharacter.naam }}</h2>
                    <p class="role">{{ currentCharacter.rol }}</p>
                    <div class="description custom-scrollbar">
                        {{ currentCharacter.beschrijving }}
                    </div>
                </div>

                <!-- Decorative corner elements -->
                <div class="corner tl"></div>
                <div class="corner tr"></div>
                <div class="corner bl"></div>
                <div class="corner br"></div>
            </div>

            <!-- Right Arrow -->
            <div class="nav-control" @click="nextCharacter">
                <span class="nav-arrow">&gt;</span>
            </div>
        </div>

        <div v-if="!loading && currentCharacter" class="action-wrapper">
            <ClickButton 
                label="INITIALIZE_DATA_RECORD" 
                buttonType="red" 
                @click="selectCharacter"
                style="padding: 15px 40px; font-weight: bold; letter-spacing: 2px;"
            />
        </div>

        <div class="grid-overlay"></div>
    </div>
</template>

<style scoped>
.selection-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100vh;
    width: 100vw;
    background: radial-gradient(circle at center, #0f0000 0%, #000 100%);
    color: white;
    padding: 40px;
    font-family: 'Wallace', 'Courier New', Courier, monospace;
    position: relative;
    overflow: hidden;
    box-sizing: border-box;
}

.scene-title {
    font-size: 2.5rem;
    font-weight: bold;
    letter-spacing: 0.5em;
    color: #ff0000;
    margin-bottom: 40px;
    text-transform: uppercase;
    z-index: 10;
}

.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    z-index: 10;
}

.loading-spinner {
    width: 48px;
    height: 48px;
    border: 4px solid #ff0000;
    border-top: 4px solid transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 16px;
}

.loading-state p {
    font-size: 0.75rem;
    letter-spacing: 0.2em;
    animation: pulse 2s infinite;
}

.selector-content {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-around;
    width: 100%;
    max-width: 1100px;
    height: 65vh;
    z-index: 10;
}

.nav-control {
    cursor: pointer;
    padding: 20px;
    transition: all 0.3s;
    user-select: none;
}

.nav-arrow {
    font-size: 3rem;
    color: rgba(255, 0, 0, 0.5);
    transition: transform 0.3s, color 0.3s;
}

.nav-control:hover .nav-arrow {
    color: #ff0000;
    transform: scale(1.2);
}

.character-card {
    position: relative;
    width: 380px;
    height: 100%;
    border: 1px solid rgba(255, 0, 0, 0.3);
    background: rgba(127, 0, 0, 0.05);
    padding: 20px;
    display: flex;
    flex-direction: column;
    backdrop-filter: blur(10px);
    box-shadow: 0 0 30px rgba(255, 0, 0, 0.1);
}

.viewer-wrapper {
    flex-grow: 1;
    position: relative;
    overflow: hidden;
    margin-bottom: 20px;
    background: rgba(0, 0, 0, 0.2);
}

.viewer-component {
    width: 100%;
    height: 100%;
}

.error-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255, 0, 0, 0.4);
    font-size: 0.6rem;
}

.character-info {
    border-top: 1px solid rgba(255, 0, 0, 0.3);
    padding-top: 20px;
}

.name {
    font-size: 1.5rem;
    font-weight: bold;
    color: #ff5555;
    margin: 0 0 5px 0;
    text-transform: uppercase;
}

.role {
    font-size: 0.7rem;
    color: rgba(255, 100, 100, 0.6);
    text-transform: uppercase;
    letter-spacing: 0.2em;
    margin-bottom: 12px;
}

.description {
    font-size: 0.8rem;
    color: #ccc;
    line-height: 1.5;
    height: 60px;
    overflow-y: auto;
    padding-right: 5px;
}

.action-wrapper {
    margin-top: 50px;
    z-index: 10;
}

/* Corner Decorations */
.corner {
    position: absolute;
    width: 15px;
    height: 15px;
    border-color: #ff0000;
    border-style: solid;
}
.tl { top: 0; left: 0; border-width: 2px 0 0 2px; }
.tr { top: 0; right: 0; border-width: 2px 2px 0 0; }
.bl { bottom: 0; left: 0; border-width: 0 0 2px 2px; }
.br { bottom: 0; right: 0; border-width: 0 2px 2px 0; }

.grid-overlay {
    position: absolute;
    inset: 0;
    background-image: 
        linear-gradient(rgba(255, 0, 0, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 0, 0, 0.03) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
    z-index: 0;
}

@keyframes spin { to { transform: rotate(360deg); } }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

/* Scrollbar */
.custom-scrollbar::-webkit-scrollbar { width: 3px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(255, 0, 0, 0.05); }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 0, 0, 0.3); }

/* Glitch Effect */
.glitch-text { position: relative; }
.glitch-text::before, .glitch-text::after {
    content: attr(data-text);
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    opacity: 0;
}
.glitch-text:hover::before {
    opacity: 0.7; color: #00ffff;
    animation: glitch 0.3s infinite;
    z-index: -1;
}
.glitch-text:hover::after {
    opacity: 0.7; color: #ff00ff;
    animation: glitch 0.3s infinite reverse;
    z-index: -2;
}

@keyframes glitch {
    0% { transform: translate(0); }
    25% { transform: translate(-2px, 2px); }
    50% { transform: translate(2px, -2px); }
    75% { transform: translate(-2px, -2px); }
    100% { transform: translate(2px, 2px); }
}
</style>
