<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../../axios';
import ArtworkManager from '../../components/ArtworkManager.vue';

const route = useRoute();
const router = useRouter();
const locatie = ref(null);
const loading = ref(true);

onMounted(async () => {
    try {
        const response = await axios.get(`/api/locaties/${route.params.id}`);
        locatie.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
});

const saveChanges = async () => {
    try {
        await axios.put(`/api/locaties/${locatie.value.id}`, locatie.value);
        alert('SECTOR_UPDATED');
    } catch (e) {
        alert('ERROR_UPDATING');
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
</script>

<template>
    <div v-if="loading" class="container mx-auto p-6 text-center text-noir-muted animate-pulse">
        ACCESSING_SECTOR_DATA...
    </div>

    <div v-else-if="locatie" class="container mx-auto p-6">
        <div class="flex items-center mb-6 text-sm text-noir-muted">
            <RouterLink to="/locaties" class="hover:text-white">&lt; BACK_TO_MAP</RouterLink>
            <span class="mx-2">/</span>
            <span class="text-white">{{ locatie.naam }}</span>
        </div>

        <div class="bg-noir-panel border border-noir-dark rounded shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="p-6 border-b border-noir-dark flex justify-between items-start bg-noir-dark/50">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-1">{{ locatie.naam }}</h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-xs text-noir-muted">SECTOR_ID: {{ String(locatie.id).padStart(8, '0') }}</span>
                    </div>
                </div>
                <button @click="saveChanges" class="bg-noir-warning/20 text-noir-warning border border-noir-warning px-4 py-2 rounded hover:bg-noir-warning hover:text-black hover:shadow-[0_0_15px_rgba(245,158,11,0.5)] transition-all duration-300 uppercase font-bold text-xs tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                    UPDATE_SECTOR
                </button>
            </div>

            <!-- Content -->
            <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Artwork -->
                <div class="lg:col-span-1">
                    <ArtworkManager
                        model-type="locatie"
                        :model-id="locatie.id"
                        :artwork="locatie.artwork"
                        @upload-success="handleUploadSuccess"
                        @image-deleted="handleDeleteSuccess"
                    />
                </div>

                <!-- Right Column: Inputs -->
                <div class="lg:col-span-2 space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-noir-muted uppercase mb-2">Naam</label>
                        <input v-model="locatie.naam" type="text" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-warning focus:outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-noir-muted uppercase mb-2">Beschrijving</label>
                        <textarea v-model="locatie.beschrijving" rows="6" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-warning focus:outline-none transition-colors"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-noir-muted uppercase mb-2">GM Notities (Hidden)</label>
                        <textarea v-model="locatie.notities" rows="4" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-warning focus:outline-none transition-colors placeholder-gray-700" placeholder="Secret details about this location..."></textarea>
                    </div>

                    <!-- Linked Clues -->
                    <div v-if="locatie.aanwijzingen && locatie.aanwijzingen.length > 0">
                        <h3 class="text-sm font-bold text-white uppercase mb-4 border-b border-noir-dark pb-2">Evidence Found Here</h3>
                        <ul class="space-y-2">
                            <li v-for="clue in locatie.aanwijzingen" :key="clue.id" class="flex items-center text-sm">
                                <span class="w-2 h-2 bg-noir-warning rounded-full mr-2"></span>
                                <span class="text-noir-text">{{ clue.titel }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div v-else class="container mx-auto p-6 text-center text-noir-danger">
        SECTOR_DATA_NOT_FOUND
    </div>
</template>
