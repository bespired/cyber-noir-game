<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from '../../axios';
import { RouterLink } from 'vue-router';
import Modal from '../../components/Modal.vue';
import SectorSceneThumb  from '../../components/thumbs/SectorSceneThumb.vue';
import LinkButton from '../../components/inputs/LinkButton.vue';
import ClickButton from '../../components/inputs/ClickButton.vue';
import { useToast } from '../../composables/useToast';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const toast = useToast();

const route = useRoute();
const sector = ref(null);
const loading = ref(true);
const showEditModal = ref(false);
const fileInput = ref(null);
const uploadLoading = ref(false);

const editForm = ref({
    naam: '',
    beschrijving: '',
    kaart_coordinaten: '',
    is_ontdekt: false,
    x: 0,
    y: 0,
    width: 100,
    height: 100
});

const scenes = ref([]);

// Link Scene State
const showLinkSceneModal = ref(false);
const selectedSceneId = ref('');

onMounted(async () => {
    await Promise.all([
        fetchSector(),
        fetchScenes()
    ]);
});

const fetchScenes = async () => {
    try {
        const response = await axios.get('/api/scenes');
        scenes.value = response.data;
    } catch (e) {
        console.error("Failed to fetch scenes", e);
    }
};

const fetchSector = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/sectoren/${route.params.id}`);
        sector.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const openEditModal = () => {
    editForm.value = {
        naam: sector.value.naam,
        beschrijving: sector.value.beschrijving,
        kaart_coordinaten: sector.value.kaart_coordinaten,
        is_ontdekt: sector.value.is_ontdekt,
        x: sector.value.x || 0,
        y: sector.value.y || 0,
        width: sector.value.width || 100,
        height: sector.value.height || 100
    };
    showEditModal.value = true;
};

const updateSector = async () => {
    try {
        const response = await axios.put(`/api/sectoren/${sector.value.id}`, editForm.value);
        sector.value = response.data;
        showEditModal.value = false;
    } catch (e) {
        console.error("Failed to update sector", e);
    }
};

const triggerUpload = () => {
    fileInput.value.click();
};

const handleFileUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('artwork', file);

    uploadLoading.value = true;
    try {
        // Upload to /upload/sector/{id}
        await axios.post(`/api/upload/sector/${sector.value.id}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        await fetchSector(); // Refresh to show new image
    } catch (e) {
        console.error("Upload failed", e);
        toast.error("Upload failed: " + (e.response?.data?.message || e.message));
    } finally {
        uploadLoading.value = false;
    }
};

const openLinkSceneModal = () => {
    selectedSceneId.value = '';
    showLinkSceneModal.value = true;
};

const linkScene = async () => {
    if (!selectedSceneId.value) return;

    try {
        await axios.put(`/api/scenes/${selectedSceneId.value}`, {
            sector_id: sector.value.id
        });
        showLinkSceneModal.value = false;
        await fetchSector();
    } catch (e) {
        console.error("Failed to link scene", e);
    }
};

const getImageUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    if (path.startsWith('/storage')) return `http://localhost:8000${path}`;
    const cleanPath = path.startsWith('/') ? path : `/${path}`;
    return `http://localhost:8000/storage${cleanPath}`;
};
</script>

<template>
    <div v-if="loading" class="container mx-auto p-6 text-center text-noir-muted animate-pulse">
        {{ t('map.loading') }}
    </div>

    <div v-else-if="sector" class="container mx-auto p-6">
        <div class="flex items-center mb-6 text-sm text-noir-muted">
            <RouterLink to="/map" class="hover:text-white">&lt; {{ t('map.back_to_sectors') }}</RouterLink>
            <span class="mx-2">/</span>
            <span class="text-white">{{ sector.naam }}</span>
        </div>

        <div class="bg-noir-panel border border-noir-dark rounded shadow-xl overflow-hidden mb-8 grid grid-cols-1 lg:grid-cols-3">
            <!-- Image Section -->
            <div class="lg:col-span-1 border-r border-noir-dark bg-black relative group">
                <div v-if="sector.artwork && sector.artwork.length > 0" class="w-full h-full min-h-[200px]">
                    <img :src="getImageUrl(sector.artwork[0].bestandspad)" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity">
                </div>
                <div v-else class="w-full h-full min-h-[200px] flex items-center justify-center text-noir-muted bg-noir-dark/50">
                    {{ t('map.no_visual_data') }}
                </div>

                <!-- Upload Overlay -->
                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                    <button @click="triggerUpload" class="px-4 py-2 border border-white text-white hover:bg-white hover:text-black transition-colors uppercase text-xs font-bold tracking-wider">
                        {{ uploadLoading ? t('map.uploading') : t('map.change_visual') }}
                    </button>
                    <input type="file" ref="fileInput" @change="handleFileUpload" class="hidden" accept="image/*">
                </div>
            </div>

            <!-- Details Section -->
            <div class="lg:col-span-2 p-6 flex flex-col">
                <div class="flex justify-between items-start mb-4">
                    <h1 class="page-header">{{ sector.naam }}</h1>
                    <button @click="openEditModal" class="text-noir-accent hover:text-white text-sm font-bold uppercase tracking-wider border border-noir-accent hover:border-white px-3 py-1 rounded transition-colors">
                        {{ t('map.edit_data') }}
                    </button>
                </div>
                <p class="text-noir-text mb-6 flex-grow">{{ sector.beschrijving }}</p>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs text-noir-muted border-t border-noir-dark pt-4 font-mono">
                    <div>
                        <div class="uppercase mb-1">{{ t('map.coordinates') }}</div>
                        <div class="text-white">{{ sector.kaart_coordinaten || 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="uppercase mb-1">{{ t('map.grid_position') }}</div>
                        <div class="text-white">X:{{ sector.x }} Y:{{ sector.y }}</div>
                    </div>
                    <div>
                        <div class="uppercase mb-1">{{ t('map.dimensions') }}</div>
                        <div class="text-white">{{ sector.width }}x{{ sector.height }}</div>
                    </div>
                     <div>
                        <div class="uppercase mb-1">{{ t('map.status') }}</div>
                        <div :class="sector.is_ontdekt ? 'text-noir-success' : 'text-noir-muted'">{{ sector.is_ontdekt ? t('map.discovered') : t('map.unknown') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-white uppercase tracking-wider">{{ t('map.scenes_in_sector') }}</h2>
            <div class="flex gap-2">
                <LinkButton
                    name="sector-emulate"
                    :params="{ id: sector.id }"
                    buttonType="green"
                    :label="t('map.emulate')"
                    icon="ƈ"
                >
                </LinkButton>
                <LinkButton
                    name="sector-map"
                    :params="{ id: sector.id }"
                    buttonType="blue"
                    :label="t('map.visual_map')"
                    icon="Ɗ"
                >
                </LinkButton>
                <ClickButton
                    :label="t('map.link_scene')"
                    buttonType="add"
                    @click="openLinkSceneModal"
                />
            </div>
        </div>

        <!-- SectorScenes -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <SectorSceneThumb
                v-for="scene in sector.scenes" :key="scene.id" :scene="scene" :sector="sector"
            />

            <!-- Empty State -->
            <div v-if="!sector.scenes || sector.scenes.length === 0" class="col-span-full text-center py-12 border-2 border-dashed border-noir-dark rounded text-noir-muted">
                {{ t('map.no_scenes_found') }}
            </div>
        </div>

        <!-- Edit Modal -->
        <Modal :isOpen="showEditModal" :title="t('map.edit_sector')"
            :okLabel="t('map.save')"
            @close="showEditModal = false"
            @ok="updateSector"
        >
            <form @submit.prevent="updateSector" class="space-y-4">
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">{{ t('map.name') }}</label>
                    <input v-model="editForm.naam" type="text" required class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                </div>
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">{{ t('map.description') }}</label>
                    <textarea v-model="editForm.beschrijving" required rows="4" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-noir-muted text-xs uppercase mb-1">{{ t('map.width') }}</label>
                        <input v-model="editForm.width" type="number" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-noir-muted text-xs uppercase mb-1">{{ t('map.height') }}</label>
                        <input v-model="editForm.height" type="number" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                    </div>
                </div>
                <!-- X/Y are managed via map but editable here if needed -->
            </form>
        </Modal>

        <!-- Link Scene Modal -->
        <Modal :isOpen="showLinkSceneModal" :title="t('map.link_scene_title')"
            :okLabel="t('map.link_scene_title')"
            @close="showLinkSceneModal = false"
            @ok="linkScene"
        >
            <form @submit.prevent="linkScene" class="space-y-4">
                <div>
                     <label class="block text-noir-muted text-xs uppercase mb-1">{{ t('map.select_scene') }}</label>
                     <select v-model="selectedSceneId" required class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-warning focus:outline-none">
                        <option value="" disabled>{{ t('map.select_scene_placeholder') }}</option>
                        <option v-for="sce in scenes" :key="sce.id" :value="sce.id">
                            {{ sce.titel }} {{ sce.sector_id ? t('map.already_linked') : t('map.free') }}
                        </option>
                     </select>
                </div>
            </form>
        </Modal>
    </div>
</template>
