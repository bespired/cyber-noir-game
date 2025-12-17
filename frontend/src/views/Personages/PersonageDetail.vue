<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../../axios';
import ArtworkManager from '../../components/ArtworkManager.vue';

const route = useRoute();
const router = useRouter();
const personage = ref(null);
const loading = ref(true);
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
        alert('CHANGES_SAVED');
    } catch (e) {
        alert('ERROR_SAVING');
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
</script>

<template>
    <div v-if="loading" class="container mx-auto p-6 text-center text-noir-muted animate-pulse">
        INLADEN_VEILIGE_FILE...
    </div>

    <div v-else-if="personage" class="container mx-auto p-6">
        <div class="flex items-center mb-6 text-sm text-noir-muted">
            <RouterLink to="/personages" class="hover:text-white">&lt; PERSONAGES</RouterLink>
            <span class="mx-2">/</span>
            <span class="text-white">{{ personage.naam }}</span>
        </div>

        <div class="bg-noir-panel border border-noir-dark rounded shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="p-6 border-b border-noir-dark flex justify-between items-start bg-noir-dark/50">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-1">{{ personage.naam }}</h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-noir-accent font-mono text-sm uppercase tracking-wider">{{ personage.rol }}</span>
                        <span class="text-xs text-noir-muted">FILE_ID: {{ String(personage.id).padStart(8, '0') }}</span>
                    </div>
                </div>
                <button @click="saveChanges" class="bg-noir-success/20 text-noir-success border border-noir-success px-4 py-2 rounded hover:bg-noir-success hover:text-black hover:shadow-[0_0_15px_rgba(16,185,129,0.5)] transition-all duration-300 uppercase font-bold text-xs tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                    BEWAAR_
                </button>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-noir-dark">
                <button
                    @click="activeTab = 'public'"
                    class="flex-1 py-3 text-sm font-bold uppercase tracking-wider transition-colors border-b-2"
                    :class="activeTab === 'public' ? 'bg-noir-panel text-white border-noir-accent' : 'bg-noir-dark text-noir-muted border-transparent hover:text-white'"
                >
                    Public Data
                </button>
                <button
                    @click="activeTab = 'private'"
                    class="flex-1 py-3 text-sm font-bold uppercase tracking-wider transition-colors border-b-2"
                    :class="activeTab === 'private' ? 'bg-noir-panel text-noir-danger border-noir-danger' : 'bg-noir-dark text-noir-muted border-transparent hover:text-white'"
                >
                    Classified (GM Only)
                </button>
            </div>

            <!-- Content -->
            <div class="p-6">
                <!-- Public Tab -->
                <div v-if="activeTab === 'public'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column: Artwork -->
                    <div class="lg:col-span-1">
                        <ArtworkManager
                            model-type="personage"
                            :model-id="personage.id"
                            :artwork="personage.artwork"
                            @upload-success="handleUploadSuccess"
                            @image-deleted="handleDeleteSuccess"
                        />
                    </div>

                    <!-- Right Column: Inputs -->
                    <div class="lg:col-span-2 space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-noir-muted uppercase mb-2">Naam</label>
                            <input v-model="personage.naam" type="text" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-accent focus:outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-noir-muted uppercase mb-2">Rol</label>
                            <input v-model="personage.rol" type="text" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-accent focus:outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-noir-muted uppercase mb-2">Beschrijving</label>
                            <textarea v-model="personage.beschrijving" rows="6" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-accent focus:outline-none transition-colors"></textarea>
                        </div>

                        <!-- Linked Clues (Read Only for now) -->
                        <div v-if="personage.aanwijzingen && personage.aanwijzingen.length > 0">
                            <h3 class="text-sm font-bold text-white uppercase mb-4 border-b border-noir-dark pb-2">Linked Evidence</h3>
                            <ul class="space-y-2">
                                <li v-for="clue in personage.aanwijzingen" :key="clue.id" class="flex items-center text-sm">
                                    <span class="w-2 h-2 bg-noir-accent rounded-full mr-2"></span>
                                    <span class="text-noir-text">{{ clue.titel }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Private Tab -->
                <div v-if="activeTab === 'private'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column: Artwork (Same as public, or different if needed, but keeping consistent) -->
                    <div class="lg:col-span-1">
                        <ArtworkManager
                            model-type="personage"
                            :model-id="personage.id"
                            :artwork="personage.artwork"
                            @upload-success="handleUploadSuccess"
                            @image-deleted="handleDeleteSuccess"
                        />
                    </div>

                    <!-- Right Column: Inputs -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="bg-noir-danger/10 border border-noir-danger/30 p-4 rounded">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="checkbox" v-model="personage.is_replicant" class="form-checkbox h-5 w-5 text-noir-danger rounded bg-noir-dark border-noir-danger focus:ring-0 focus:ring-offset-0">
                                    <span class="text-noir-danger font-bold uppercase tracking-wider">Subject is Replicant</span>
                                </label>
                            </div>
                            <div class="bg-noir-warning/10 border border-noir-warning/30 p-4 rounded">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="checkbox" v-model="personage.is_playable" class="form-checkbox h-5 w-5 text-noir-warning rounded bg-noir-dark border-noir-warning focus:ring-0 focus:ring-offset-0">
                                    <span class="text-noir-warning font-bold uppercase tracking-wider">Playable Character</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-noir-muted uppercase mb-2">Menselijke Status (Human Traits)</label>
                            <textarea v-model="personage.menselijke_status" rows="3" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-danger focus:outline-none transition-colors placeholder-gray-700" placeholder="Traits that make them appear human..."></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-noir-muted uppercase mb-2">Replicant Status (Artificial Traits)</label>
                            <textarea v-model="personage.replicant_status" rows="3" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-danger focus:outline-none transition-colors placeholder-gray-700" placeholder="Traits that betray their artificial nature..."></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-noir-muted uppercase mb-2">Motief (Hidden Agenda)</label>
                            <textarea v-model="personage.motief" rows="3" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-danger focus:outline-none transition-colors placeholder-gray-700" placeholder="What do they really want?"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
