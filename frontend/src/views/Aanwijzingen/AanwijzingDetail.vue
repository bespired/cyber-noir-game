<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../../axios';
import ArtworkManager from '../../components/ArtworkManager.vue';
import { useToast } from '../../composables/useToast';
import { useI18n } from 'vue-i18n';

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
        personages.value = pRes.data;
        locaties.value = lRes.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
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
                <!-- Left Column: Artwork -->
                <div class="lg:col-span-1">
                    <ArtworkManager
                        model-type="aanwijzing"
                        :model-id="aanwijzing.id"
                        :artwork="aanwijzing.artwork"
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

                    <div>
                        <label class="block text-xs font-bold text-noir-muted uppercase mb-2">{{ t('clues.title') }}</label>
                        <input v-model="aanwijzing.titel" type="text" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-danger focus:outline-none transition-colors">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-noir-muted uppercase mb-2">{{ t('clues.description') }}</label>
                        <textarea v-model="aanwijzing.beschrijving" rows="6" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-danger focus:outline-none transition-colors"></textarea>
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
