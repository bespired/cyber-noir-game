<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../../axios';
import ArtworkManager from '../../components/ArtworkManager.vue';
import Character3DViewer from '../../components/Character3DViewer.vue';
import ClickButton  from '../../components/inputs/ClickButton.vue';
import DetailHeader from '../../components/bars/DetailHeader.vue';
import { useToast } from '../../composables/useToast';
import { useI18n } from 'vue-i18n';

const { t }  = useI18n();
const toast  = useToast();
const route  = useRoute();
const router = useRouter();
const personage = ref(null);
const loading   = ref(true);
const uploadingGlb = ref(false);
const activeTab = ref('public'); // 'public' or 'private'

onMounted(async () => {
    try {
        const response = await axios.get(`/api/personages/${route.params.id}`);
        personage.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
});

const saveChanges = async () => {
    try {
        await axios.put(`/api/personages/${personage.value.id}`, personage.value);
        toast.success(t('scenes.changes_saved'));
    } catch (e) {
        toast.error(t('scenes.error_saving'));
    }
};

const handleUploadSuccess = (newImage) => {
    if (!personage.value.artwork) {
        personage.value.artwork = [];
    }
    personage.value.artwork.push(newImage);
};

const handleDeleteSuccess = (deletedId) => {
    if (personage.value.artwork) {
        personage.value.artwork = personage.value.artwork.filter(img => img.id !== deletedId);
    }
};

const getGlbUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    const cleanPath = path.startsWith('/') ? path : `/${path}`;
    // Ensure it points to :8000 or the VITE_API_URL
    const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000';
    return `${baseUrl}${cleanPath}`;
};

const triggerGlbUpload = () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.glb';
    input.onchange = async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('glb', file);

        uploadingGlb.value = true;
        try {
            await axios.post(`/api/personages/${personage.value.id}/glb`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            toast.success('GLB_UPLOAD_SUCCESSFUL');
            // Refresh character data to update glb_url
            const response = await axios.get(`/api/personages/${personage.value.id}`);
            personage.value = response.data;
        } catch (err) {
            console.error(err);
            toast.error('UPLOAD_FAILED: NEURAL_REJECTION');
        } finally {
            uploadingGlb.value = false;
        }
    };
    input.click();
};

const headerLabel = computed(() => {
    if (!personage.value) return '';
    return personage.value.type === 'voertuig' ? t('personages.vehicles') : t('personages.characters')
});


</script>

<template>
    <div v-if="loading" class="container mx-auto p-6 text-center text-noir-muted animate-pulse">
        {{ t('personages.loading_secure') }}
    </div>

    <div v-else-if="personage" class="container mx-auto p-6">
        <detail-header
            :label="personage.naam"
            :backLink="personage.type === 'voertuig' ? 'voertuigen' : 'personages'"
            :backlabel="headerLabel"
            :save="true"
            @save="saveChanges"
        />

        <div class="panel border-noir-dark bg-noir-darker/50 overflow-hidden">
            <!-- Inner Header -->
            <div class="detail-panel-header">
                <div class="">
                    <div>
                        <h1 class="page-header">{{ personage.naam }}</h1>
                        <div class="flex items-center gap-4">
                            <span class="text-noir-accent font-mono text-sm uppercase tracking-widest font-bold">{{ personage.rol }}</span>
                            <span class="text-xs text-noir-muted font-mono">{{ t('personages.file_id') }}: {{ personage.id }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-noir-dark bg-noir-darker">
                <button
                    @click="activeTab = 'public'"
                    class="flex-1 py-4 text-xs font-bold uppercase tracking-[0.2em] transition-all border-b-2"
                    :class="activeTab === 'public' ? 'bg-noir-panel/40 text-white border-noir-accent' : 'text-noir-muted border-transparent hover:text-white hover:bg-white/5'"
                >
                    {{ personage.type === 'voertuig' ? t('personages.technical_data') : t('personages.public_data') }}
                </button>
                <button
                    @click="activeTab = 'private'"
                    class="flex-1 py-4 text-xs font-bold uppercase tracking-[0.2em] transition-all border-b-2"
                    :class="activeTab === 'private' ? 'bg-noir-panel/40 text-noir-danger border-noir-danger' : 'text-noir-muted border-transparent hover:text-white hover:bg-white/5'"
                >
                    {{ t('personages.classified') }}
                </button>
            </div>

            <!-- Content -->
            <div class="pt-6">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">

                    <!-- Col 1: Visual Files (Span 3) - PERSISTENT -->
                    <div class="lg:col-span-3 lg:border-r border-noir-dark pr-6">
                        <ArtworkManager
                            model-type="personage"
                            :asset-type="personage.type"
                            :model-id="personage.id"
                            :artwork="personage.artwork"
                            @upload-success="handleUploadSuccess"
                            @image-deleted="handleDeleteSuccess"
                        />
                    </div>

                    <!-- Col 2: 3D Core (Span 4) - PERSISTENT -->
                    <div class="lg:col-span-4 lg:border-r border-noir-dark pr-6">
                         <div class="panel bg-noir-darker p-4 border-noir-dark">
                            <h3 class="text-[10px] font-bold text-noir-muted uppercase mb-4 flex justify-between items-center tracking-widest">
                                <span>{{ personage.type === 'voertuig' ? t('personages.3d_vehicle_core') : t('personages.3d_character_core') }}</span>
                                <button
                                    @click="triggerGlbUpload"
                                    class="text-noir-accent hover:text-white transition-colors"
                                    :disabled="uploadingGlb"
                                >
                                    {{ uploadingGlb ? t('personages.scanning') : t('personages.upload_glb') }}
                                </button>
                            </h3>

                            <div :class="[
                                'w-full bg-black/40 rounded border border-noir-panel relative overflow-hidden',
                                personage.type === 'voertuig' ? 'aspect-video' : 'aspect-[3/4]'
                            ]">
                                <Character3DViewer
                                    v-if="personage.glb_url"
                                    :glb-url="getGlbUrl(personage.glb_url)"
                                    :type="personage.type"
                                />
                                <div v-else class="w-full h-full flex flex-col items-center justify-center text-noir-muted p-6 text-center">
                                    <div class="text-4xl mb-4 opacity-30">👤</div>
                                    <p class="text-[10px] uppercase tracking-[0.2em] mb-4 text-noir-muted/60">{{ personage.type === 'voertuig' ? t('personages.no_vehicle_data') : t('personages.no_neural_data') }}</p>
                                    <button @click="triggerGlbUpload" class="btn btn--small btn--accent-outline">
                                        {{ t('personages.init_scan') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Col 3: Inputs / Content (Span 5) - SWAPPABLE -->
                    <div class="lg:col-span-5 space-y-8 animate-fade-in">

                        <!-- PUBLIC CONTENT -->
                        <div v-if="activeTab === 'public'" class="space-y-8">
                            <div>
                                <label class="form-label text-noir-muted/80">{{ t('personages.name') }}</label>
                                <input v-model="personage.naam" type="text" class="form-input bg-noir-dark border-noir-panel focus:border-noir-accent">
                            </div>
                            <div>
                                <label class="form-label text-noir-muted/80">{{ t('personages.role') }}</label>
                                <input v-model="personage.rol" type="text" class="form-input bg-noir-dark border-noir-panel focus:border-noir-accent">
                            </div>
                            <div>
                                <label class="form-label text-noir-muted/80">{{ t('personages.description') }}</label>
                                <textarea v-model="personage.beschrijving" rows="8" class="form-input bg-noir-dark border-noir-panel focus:border-noir-accent"></textarea>
                            </div>

                            <!-- Linked Clues -->
                            <div v-if="personage.aanwijzingen && personage.aanwijzingen.length > 0" class="pt-4">
                                <h3 class="text-xs font-bold text-white uppercase mb-4 tracking-widest opacity-80">{{ t('personages.linked_evidence') }}</h3>
                                <div class="grid grid-cols-1 gap-2">
                                    <div v-for="clue in personage.aanwijzingen" :key="clue.id" class="p-3 bg-noir-darker border border-noir-panel rounded flex items-center text-xs uppercase tracking-wider">
                                        <span class="w-1.5 h-1.5 bg-noir-accent rounded-full mr-3 shadow-[0_0_8px_rgba(59,130,246,0.8)]"></span>
                                        <span class="text-white">{{ clue.titel }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PRIVATE CONTENT (Classified) -->
                        <div v-if="activeTab === 'private'" class="space-y-8">
                            <div class="grid grid-cols-1 gap-4">
                                <div class="bg-noir-danger/5 border border-noir-danger/20 p-5 rounded" v-if="personage.type === 'persoon'">
                                    <label class="flex items-center space-x-4 cursor-pointer">
                                        <input type="checkbox" v-model="personage.is_replicant" class="form-checkbox h-5 w-5 text-noir-danger rounded bg-noir-darker border-noir-danger/50 focus:ring-0">
                                        <span class="text-noir-danger font-bold uppercase tracking-[0.2em] text-xs">// {{ t('personages.subject_replicant') }}</span>
                                    </label>
                                </div>
                                <div class="bg-noir-warning/5 border border-noir-warning/20 p-5 rounded">
                                    <label class="flex items-center space-x-4 cursor-pointer">
                                        <input type="checkbox" v-model="personage.is_playable" class="form-checkbox h-5 w-5 text-noir-warning rounded bg-noir-darker border-noir-warning/50 focus:ring-0">
                                        <span class="text-noir-warning font-bold uppercase tracking-[0.2em] text-xs">// {{ personage.type === 'voertuig' ? t('personages.deployable_vehicle') : t('personages.playable_character') }}</span>
                                    </label>
                                </div>
                            </div>

                            <div v-if="personage.type === 'persoon'">
                                <label class="form-label text-noir-muted/60">{{ t('personages.human_traits') }}</label>
                                <textarea v-model="personage.menselijke_status" rows="3" class="form-input bg-noir-dark border-noir-panel focus:border-noir-danger/50" :placeholder="t('personages.placeholder_traits_human')"></textarea>
                            </div>

                            <div v-if="personage.type === 'persoon'">
                                <label class="form-label text-noir-muted/60">{{ t('personages.artificial_traits') }}</label>
                                <textarea v-model="personage.replicant_status" rows="3" class="form-input bg-noir-dark border-noir-panel focus:border-noir-danger/50" :placeholder="t('personages.placeholder_traits_ai')"></textarea>
                            </div>

                            <div>
                                <label class="form-label text-noir-muted/60">{{ personage.type === 'voertuig' ? t('personages.specs_notes') : t('personages.motive_agenda') }}</label>
                                <textarea v-model="personage.motief" rows="4" class="form-input bg-noir-dark border-noir-panel focus:border-noir-danger/50" :placeholder="personage.type === 'voertuig' ? t('personages.placeholder_specs') : t('personages.placeholder_motive')"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
