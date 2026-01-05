<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import axios from '../../axios';
import ArtworkManager from '../../components/ArtworkManager.vue';
import DetailHeader from '../../components/bars/DetailHeader.vue';
import { useToast } from '../../composables/useToast';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const toast = useToast();
const route = useRoute();
const router = useRouter();
const locatie = ref(null);
const loading = ref(true);
const uploadingGlbForSectorId = ref(null);
const glbFileInput = ref(null);
const isUploadingGlb = ref(false);

const backgroundImageUrl = computed(() => {
    if (!locatie.value || !locatie.value.artwork || locatie.value.artwork.length === 0) {
        return '';
    }
    // Use first artwork as background
    const path = locatie.value.artwork[0].bestandspad;
    if (path.startsWith('http')) return path;
    const cleanPath = path.startsWith('/') ? path : `/${path}`;
    return `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/storage${cleanPath}`;
});

const fetchLocatie = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/locaties/${route.params.id}`);
        locatie.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchLocatie);

const saveChanges = async () => {
    try {
        await axios.put(`/api/locaties/${locatie.value.id}`, locatie.value);
        toast.success(t('locations.sector_updated'));
    } catch (e) {
        toast.error(t('locations.update_error'));
    }
};

const handleUploadSuccess = (newImage) => {
    if (!locatie.value.artwork) {
        locatie.value.artwork = [];
    }
    locatie.value.artwork.push(newImage);
};

const handleDeleteSuccess = (deletedId) => {
    if (locatie.value.artwork) {
        locatie.value.artwork = locatie.value.artwork.filter(img => img.id !== deletedId);
    }
};

const triggerGlbUpload = (sectorId) => {
    uploadingGlbForSectorId.value = sectorId;
    glbFileInput.value.click();
};

const handleGlbFileChange = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('glb', file);
    formData.append('sector_id', uploadingGlbForSectorId.value);

    isUploadingGlb.value = true;
    try {
        await axios.post(`/api/locaties/${locatie.value.id}/glb`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        await fetchLocatie();
        toast.success(t('locations.upload_success'));
    } catch (e) {
        console.error(e);
        toast.error(t('locations.upload_error'));
    } finally {
        isUploadingGlb.value = false;
        uploadingGlbForSectorId.value = null;
    }
};

const navigateTo3D = (sectorId) => {
    router.push(`/locaties/${locatie.value.id}/sector/${sectorId}/3d`);
};
</script>

<template>
    <div v-if="loading"
        class="container mx-auto p-6 text-center text-noir-muted animate-pulse">
        {{ t('locations.accessing') }}
    </div>

    <div v-else-if="locatie" class="container mx-auto p-6">

        <detail-header
            backLink="locaties"
            :backlabel="t('locations.title')"
            :label="locatie.naam"
            :save="true"   @save="saveChanges"
        />


        <div class="panel overflow-hidden">
            <!-- Header -->
            <div class="detail-panel-header">
                <div>
                    <h1 class="page-header">{{ locatie.naam }}</h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-xs text-noir-muted">{{ t('locations.sector_id') }}: {{ String(locatie.id).padStart(8, '0') }}</span>
                    </div>
                </div>
                <!-- <button @click="saveChanges" class="btn btn--warning">
                    {{ t('locations.update') }}
                </button> -->
            </div>

            <!-- Content -->
            <div class="pt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Artwork -->
                <div class="lg:col-span-1">
                    <ArtworkManager
                        model-type="locatie"
                        :model-id="locatie.id"
                        :artwork="locatie.artwork"
                        @upload-success="handleUploadSuccess"
                        @image-deleted="handleDeleteSuccess"
                    />

                <div class="mt-8 border-t border-noir-dark">
                <div v-if="locatie.glb_status && locatie.glb_status.length > 0" class="">
                    <div v-for="status in locatie.glb_status" :key="status.sector_id"
                    class="p-4 bg-noir-dark/30 rounded border border-noir-dark flex flex-col justify-between">
                        <div>
                            <span class="text-xs text-noir-muted uppercase mb-1 mr-1">{{ t('locations.sector') }}</span>
                            <span class="text-white font-bold mb-3 mr-1">{{ status.sector_naam }}</span>&nbsp;
                            <span class="flex items-center gap-2 mb-4">
                                <span :class="['h-2 w-2 rounded-full', status.exists ? 'bg-noir-success' : 'bg-noir-danger']"></span>
                                <span class="text-xs font-mono" :class="status.exists ? 'text-noir-success' : 'text-noir-danger'">
                                    {{ status.exists ? t('locations.glb_detected') : t('locations.no_glbs') }}
                                </span>
                            </span>
                        </div>

                        <div class="flex gap-2">
                            <button
                                v-if="status.exists"
                                @click="navigateTo3D(status.sector_id)"
                                class="btn btn--primary btn--small flex-grow"
                            >
                                {{ t('locations.view_3d') }}
                            </button>
                            <button
                                @click="triggerGlbUpload(status.sector_id)"
                                class="btn btn--secondary btn--small flex-grow"
                                :disabled="isUploadingGlb"
                            >
                                {{ status.exists ? t('locations.replace') : t('locations.upload') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-10 bg-noir-darker/30 rounded border border-dashed border-noir-dark text-noir-muted uppercase text-xs tracking-widest">
                    {{ t('locations.not_in_sector') }}
                </div>
            </div>
                </div>

                <!-- Right Column: Inputs -->
                <div class="lg:col-span-2 space-y-6">
                    <div>
                        <label class="form-label">{{ t('locations.name') }}</label>
                        <input v-model="locatie.naam" type="text" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">{{ t('locations.description') }}</label>
                        <textarea v-model="locatie.beschrijving" rows="6" class="form-input"></textarea>
                    </div>
                    <div>
                        <label class="form-label">{{ t('locations.gm_notes') }}</label>
                        <textarea v-model="locatie.notities" rows="4" class="form-input" :placeholder="t('locations.placeholder_secret')"></textarea>
                    </div>

                    <!-- Linked Clues -->
                    <div v-if="locatie.aanwijzingen && locatie.aanwijzingen.length > 0">
                        <h3 class="text-sm font-bold text-white uppercase mb-4 border-b border-noir-dark pb-2">{{ t('locations.clues') }}</h3>
                        <ul class="space-y-2">
                            <li v-for="clue in locatie.aanwijzingen" :key="clue.id" class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-noir-warning rounded-full mr-2"></span>
                                <span class="text-noir-text">{{ clue.titel }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 3D Scenes Section -->
            <!-- <div class="mt-8 border-t border-noir-dark p-6">
                <div v-if="locatie.glb_status && locatie.glb_status.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="status in locatie.glb_status" :key="status.sector_id" class="p-4 bg-noir-dark/30 rounded border border-noir-dark flex flex-col justify-between">
                        <div>
                            <div class="text-xs text-noir-muted uppercase mb-1">Sector</div>
                            <div class="text-white font-bold mb-3">{{ status.sector_naam }}</div>

                            <div class="flex items-center gap-2 mb-4">
                                <span :class="['h-2 w-2 rounded-full', status.exists ? 'bg-noir-success' : 'bg-noir-danger']"></span>
                                <span class="text-xs font-mono" :class="status.exists ? 'text-noir-success' : 'text-noir-danger'">
                                    {{ status.exists ? 'GLB_DETECTED' : 'NO_GLB_FOUND' }}
                                </span>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button
                                v-if="status.exists"
                                @click="navigateTo3D(status.sector_id)"
                                class="btn btn--primary btn--small flex-grow"
                            >
                                VIEW_3D
                            </button>
                            <button
                                @click="triggerGlbUpload(status.sector_id)"
                                class="btn btn--secondary btn--small flex-grow"
                                :disabled="isUploadingGlb"
                            >
                                {{ status.exists ? 'REPLACE' : 'UPLOAD' }}
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-10 bg-noir-darker/30 rounded border border-dashed border-noir-dark text-noir-muted uppercase text-xs tracking-widest">
                    NO_SECTOR_RELATIONS_FOUND_FOR_3D
                </div>
            </div> -->

            <!-- Hidden File Input -->
            <input
                type="file"
                ref="glbFileInput"
                class="hidden"
                accept=".glb"
                @change="handleGlbFileChange"
            >
        </div>
    </div>
    <div v-else class="container mx-auto p-6 text-center text-noir-danger">
        {{ t('locations.not_found') }}
    </div>
</template>
