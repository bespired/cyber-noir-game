<script setup>
import { ref, defineEmits, computed } from 'vue';
import axios from '../axios';
import ClickButton from './inputs/ClickButton.vue';
import { useToast } from '../composables/useToast';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const toast = useToast();

const props = defineProps({
    modelType: {
        type: String,
        required: true
    },
    modelId: {
        type: Number,
        required: true
    },
    artwork: {
        type: Array,
        default: () => []
    },
    assetType: {
        type: String,
        default: 'persoon'
    },
    accept: {
        type: String,
        default: 'image/*'
    }
});

const emit = defineEmits(['upload-success', 'image-deleted']);

const selectedFile = ref(null);
const artworkTitle = ref('');
const uploading = ref(false);

const isUploadOpen = ref(false);

const toggleUpload = () => {
    isUploadOpen.value = !isUploadOpen.value;
};

const onFileChange = (e) => {
    selectedFile.value = e.target.files[0];
};

const handleUpload = async () => {
    if (!selectedFile.value) return;

    uploading.value = true;
    const formData = new FormData();
    formData.append('artwork', selectedFile.value);
    formData.append('titel', artworkTitle.value);

    try {
        const response = await axios.post(`/api/upload/${props.modelType}/${props.modelId}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        emit('upload-success', response.data.artwork);
        selectedFile.value = null;
        artworkTitle.value = '';
        // Reset file input
        const fileInput = document.getElementById('file-input');
        if (fileInput) fileInput.value = '';
        isUploadOpen.value = false; // Close form on success
    } catch (error) {
        console.error("Upload failed:", error);
        toast.error("UPLOAD_FAILED");
    } finally {
        uploading.value = false;
    }
};

const getImageUrl = (path) => {
    if (!path) return '';
    // If path already starts with http, return it
    if (path.startsWith('http')) return path;

    // If path starts with /storage, just prepend host
    if (path.startsWith('/storage')) {
        return `http://localhost:8000${path}`;
    }

    // Otherwise, assume it's a relative path from storage root (e.g. artwork/...)
    // Ensure it starts with /
    const cleanPath = path.startsWith('/') ? path : `/${path}`;
    return `http://localhost:8000/storage${cleanPath}`;
};

const aspectRatioClass = computed(() => {
    switch (props.modelType) {
        case 'personage':
            if (props.assetType === 'voertuig') return 'aspect-video';
            return 'aspect-[2/3]'; // Portrait

        case 'aanwijzing':
            return 'aspect-square'; // Square

        case 'locatie':
        default:
            return 'aspect-video'; // Landscape (16:9)
    }
});

const gridClass = computed(() => {
    if (props.modelType === 'locatie' || props.assetType === 'voertuig') {
        return 'grid-cols-1';
    }
    return 'grid-cols-1';
});

const deleteImage = async (id) => {
    if (!confirm(t('artwork.confirm_delete'))) return;

    try {
        await axios.delete(`/api/artwork/${id}`);
        emit('image-deleted', id);
    } catch (error) {
        console.error("Delete failed:", error);
        toast.error("DELETE_FAILED");
    }
};
</script>

<template>
    <div class="bg-noir-panel">
        <div class="flex justify-between items-center pb-2">
            <label class="block text-xs font-bold text-noir-muted uppercase mb-2">
                {{ t('artwork.title') }}
            </label>
        </div>

        <!-- Gallery -->
        <div v-if="artwork && artwork.length > 0" class="grid gap-4 mb-6" :class="gridClass">
            <div v-for="img in artwork" :key="img.id" class="relative group">
                <img
                    :src="getImageUrl(img.bestandspad)"
                    :alt="img.titel"
                    class="w-full object-cover rounded border border-noir-dark group-hover:border-noir-accent transition-colors"
                    :class="aspectRatioClass"
                >
                <div class="absolute bottom-0 left-0 right-0 bg-black/70 text-white text-xs p-1 flex justify-between items-center">
                    <span class="truncate mr-2">{{ img.titel || 'Untitled' }}</span>
                    <ClickButton
                        @click="deleteImage(img.id)"
                        buttonType="black"
                        class="!p-1"
                        :title="t('artwork.confirm_delete')"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </ClickButton>
                </div>
            </div>
        </div>
        <div v-else class="text-noir-muted text-sm italic mb-6">
            {{ t('artwork.no_data') }}
        </div>

        <!-- Footer Actions -->
        <div class="mt-4 border-t border-b border-noir-dark p-4">
            <ClickButton
                @click="toggleUpload"
                :label="isUploadOpen ? t('artwork.close_upload') : (props.accept === '.glb' ? t('artwork.add_glb') : t('artwork.add_record'))"
                :buttonType="isUploadOpen ? 'red' : 'add'"
            />
        </div>

        <!-- Upload Form (Collapsible) -->
        <div v-if="isUploadOpen" class="bg-noir-dark/50 p-4 rounded border border-noir-dark animate-fade-in-down">
            <h4 class="text-sm font-bold text-noir-muted uppercase mb-3 text-noir-accent">
                {{ props.accept === '.glb' ? t('artwork.upload_new_glb') : t('artwork.upload_new') }}
            </h4>
            <form @submit.prevent="handleUpload" class="space-y-3">
                <div>
                    <input
                        id="file-input"
                        type="file"
                        @change="onFileChange"
                        :accept="props.accept"
                        required
                        class="block w-full text-sm text-noir-muted file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-noir-accent file:text-white hover:file:bg-blue-600 file:cursor-pointer cursor-pointer"
                    >
                </div>
                <div>
                    <input
                        type="text"
                        v-model="artworkTitle"
                        :placeholder="t('artwork.optional_title')"
                        class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-sm text-white focus:border-noir-accent focus:outline-none transition-colors"
                    >
                </div>
                <ClickButton
                    type="submit"
                    :disabled="!selectedFile || uploading"
                    :label="uploading ? t('artwork.uploading') : (props.accept === '.glb' ? t('artwork.upload_btn_glb') : t('artwork.upload_btn'))"
                    buttonType="green"
                    class="w-full justify-center"
                    @click="handleUpload"
                />
            </form>
        </div>
    </div>
</template>
