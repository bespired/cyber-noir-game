<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../../axios';
import ArtworkManager from '../../components/ArtworkManager.vue';
import Prop3DViewer from '../../components/Prop3DViewer.vue';
import { useToast } from '../../composables/useToast';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';

const { t } = useI18n();
const toast = useToast();
const route = useRoute();
const router = useRouter();
const aanwijzing = ref(null);
const personages = ref([]);
const locaties = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const [aRes, pRes, lRes] = await Promise.all([
            axios.get(`/api/aanwijzingen/${route.params.id}`),
            axios.get('/api/personages'),
            axios.get('/api/locaties')
        ]);
        aanwijzing.value = aRes.data;
        if (!aanwijzing.value.data) {
            aanwijzing.value.data = {};
        }
        personages.value = pRes.data;
        locaties.value = lRes.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
});

const getImageUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    if (path.startsWith('/storage')) return `http://localhost:8000${path}`;
    const cleanPath = path.startsWith('/') ? path : `/${path}`;
    return `http://localhost:8000/storage${cleanPath}`;
};

const gamestateKey = computed(() => {
    if (!aanwijzing.value || !aanwijzing.value.titel) return '';
    return aanwijzing.value.titel
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
});

const glbUrl = computed(() => {
    if (!aanwijzing.value || !aanwijzing.value.artwork) return '';
    const glb = aanwijzing.value.artwork.find(a => a.bestandspad.toLowerCase().endsWith('.glb'));
    return glb ? getImageUrl(glb.bestandspad) : '';
});

const saveChanges = async () => {
    try {
        await axios.put(`/api/aanwijzingen/${aanwijzing.value.id}`, aanwijzing.value);
        toast.success(t('clues.evidence_updated'));
    } catch (e) {
        toast.error(t('clues.error_updating'));
    }
};

const handleUploadSuccess = (newImage) => {
    if (!aanwijzing.value.artwork) {
        aanwijzing.value.artwork = [];
    }
    aanwijzing.value.artwork.push(newImage);
};

const handleDeleteSuccess = (deletedId) => {
    if (aanwijzing.value.artwork) {
        aanwijzing.value.artwork = aanwijzing.value.artwork.filter(img => img.id !== deletedId);
    }
};
</script>

<template>
    <div v-if="loading" class="container mx-auto p-6 text-center text-noir-muted animate-pulse">
        {{ t('clues.decrypting_file') }}
    </div>

    <div v-else-if="aanwijzing" class="container mx-auto p-6">
        <div class="flex items-center mb-6 text-sm text-noir-muted">
            <RouterLink to="/aanwijzingen" class="hover:text-white">&lt; {{ t('clues.back') }}</RouterLink>
            <span class="mx-2">/</span>
            <span class="text-white">{{ aanwijzing.titel }}</span>
        </div>

        <div class="bg-noir-panel border border-noir-dark rounded shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="detail-panel-header">
                <div>
                    <h1 class="page-header">{{ aanwijzing.titel }}</h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-xs text-noir-muted">{{ t('clues.evidence_id') }}: {{ String(aanwijzing.id).padStart(8, '0') }}</span>
                    </div>
                </div>
                <button @click="saveChanges" class="bg-noir-danger/20 text-noir-danger border border-noir-danger px-4 py-2 rounded hover:bg-noir-danger hover:text-white hover:shadow-[0_0_15px_rgba(239,68,68,0.5)] transition-all duration-300 uppercase font-bold text-xs tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                    {{ t('clues.save') }}
                </button>
            </div>

            <!-- Content -->
            <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Visualization / Artwork -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- 3D Viewer for objects -->
                    <div v-if="aanwijzing.type === 'object'" class="mb-6">
                        <Prop3DViewer v-if="glbUrl" :glb-url="glbUrl" />
                        <div v-else class="aspect-square bg-noir-dark rounded border border-noir-panel flex flex-col items-center justify-center text-noir-muted p-4 text-center">
                            <span class="text-2xl mb-2">📦</span>
                            <span class="text-xs uppercase font-mono">{{ t('clues.no_3d_model') }}</span>
                        </div>
                    </div>

                    <ArtworkManager
                        :model-type="'aanwijzing'"
                        :model-id="aanwijzing.id"
                        :artwork="aanwijzing.artwork"
                        :accept="aanwijzing.type === 'object' ? '.glb' : 'image/*'"
                        @upload-success="handleUploadSuccess"
                        @image-deleted="handleDeleteSuccess"
                    />
                </div>

                <!-- Right Column: Inputs -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-noir-danger/10 border border-noir-danger/30 p-4 rounded mb-6">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" v-model="aanwijzing.is_kritisch" class="form-checkbox h-5 w-5 text-noir-danger rounded bg-noir-dark border-noir-danger focus:ring-0 focus:ring-offset-0 cursor-pointer">
                            <span class="text-noir-danger font-bold uppercase tracking-wider">{{ t('clues.critical_clue') }}</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-noir-muted uppercase mb-2">{{ t('clues.title') }}</label>
                            <input v-model="aanwijzing.titel" type="text" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-danger focus:outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-noir-muted uppercase mb-2">{{ t('clues.type') }}</label>
                            <select v-model="aanwijzing.type" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-danger focus:outline-none transition-colors cursor-pointer">
                                <option value="image">{{ t('clues.type_image') }}</option>
                                <option value="object">{{ t('clues.type_object') }}</option>
                                <option value="gamestate">{{ t('clues.type_gamestate') }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-noir-muted uppercase mb-2">{{ t('clues.description') }}</label>
                        <textarea v-model="aanwijzing.beschrijving" rows="4" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-danger focus:outline-none transition-colors"></textarea>
                    </div>

                    <!-- Gamestate Data (Simplified) -->
                    <div v-if="aanwijzing.type === 'gamestate'" class="bg-noir-dark/30 p-4 rounded border border-noir-panel">
                        <label class="block text-xs font-bold text-noir-muted uppercase mb-1">{{ t('clues.gamestate_key') }}</label>
                        <div class="text-white font-mono text-sm py-2 px-3 bg-noir-dark rounded border border-white/10 flex items-center justify-between">
                            <span>{{ gamestateKey }}</span>
                            <span class="text-[10px] text-noir-accent font-bold uppercase">{{ t('clues.auto_generated') }}</span>
                        </div>
                        <p class="text-[10px] text-noir-muted mt-2 italic">{{ t('clues.gamestate_hint') }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-noir-muted uppercase mb-2">{{ t('clues.linked_character') }}</label>
                            <select v-model="aanwijzing.personage_id" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-danger focus:outline-none transition-colors cursor-pointer">
                                <option :value="null">{{ t('clues.none') }}</option>
                                <option v-for="p in personages" :key="p.id" :value="p.id">{{ p.naam }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-noir-muted uppercase mb-2">{{ t('clues.where_to_find') }}</label>
                            <select v-model="aanwijzing.locatie_id" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-danger focus:outline-none transition-colors cursor-pointer">
                                <option :value="null">{{ t('clues.none') }}</option>
                                <option v-for="l in locaties" :key="l.id" :value="l.id">{{ l.naam }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-else class="container mx-auto p-6 text-center text-noir-danger">
        {{ t('clues.file_not_found') }}
    </div>
</template>
