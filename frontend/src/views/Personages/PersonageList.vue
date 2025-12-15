<script setup>
import { ref, onMounted } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';
import Modal from '../../components/Modal.vue';

const personages = ref([]);
const loading = ref(true);
const showModal = ref(false);
const form = ref({
    naam: '',
    rol: '',
    beschrijving: '',
    menselijke_status: '',
    replicant_status: '',
    motief: '',
    is_replicant: false,
    is_playable: false
});

onMounted(async () => {
    await fetchPersonages();
});

const fetchPersonages = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/personages');
        personages.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

const openModal = () => {
    form.value = {
        naam: '',
        rol: '',
        beschrijving: '',
        menselijke_status: '',
        replicant_status: '',
        motief: '',
        is_replicant: false,
        is_playable: false
    };
    showModal.value = true;
};

const createPersonage = async () => {
    try {
        await axios.post('/api/personages', form.value);
        showModal.value = false;
        await fetchPersonages(); // Refresh list
    } catch (e) {
        console.error("Failed to create personage", e);
        // Ideally handle validation errors here
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
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white tracking-tight">PERSONAGES</h1>
            <button @click="openModal" class="bg-noir-accent text-white px-4 py-2 rounded hover:bg-blue-500 hover:shadow-[0_0_15px_rgba(59,130,246,0.5)] transition-all duration-300 uppercase font-bold text-sm tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                + NEW ENTRY
            </button>
        </div>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            LOADING_DATABASE...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="personage in personages" :key="personage.id" class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg hover:border-noir-accent transition-colors group flex gap-4 relative overflow-hidden">
                <!-- Playable Badge -->
                <div v-if="personage.is_playable" class="absolute -right-6 top-4 bg-noir-accent text-black text-[10px] font-bold px-8 py-1 rotate-45 border-y border-white z-10 shadow-lg">
                    PLAYABLE
                </div>

                <!-- Thumbnail -->
                <div v-if="personage.artwork && personage.artwork.length > 0" class="flex-shrink-0 w-20">
                    <img 
                        :src="getImageUrl(personage.artwork[0].bestandspad)" 
                        :alt="personage.naam" 
                        class="w-full aspect-[2/3] object-cover rounded border border-noir-dark"
                    >
                </div>

                <div class="flex-grow">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-white group-hover:text-noir-accent transition-colors">{{ personage.naam }}</h2>
                            <span class="text-xs text-noir-muted uppercase tracking-wider">{{ personage.rol }}</span>
                        </div>
                        <div class="h-2 w-2 rounded-full" :class="personage.is_replicant ? 'bg-noir-danger' : 'bg-noir-success'" :title="personage.is_replicant ? 'Replicant Detected' : 'Human Verified'"></div>
                    </div>
                    
                    <p class="text-noir-text text-sm mb-4 line-clamp-3">{{ personage.beschrijving }}</p>
                    
                    <div class="flex justify-between items-center mt-4 pt-4 border-t border-noir-dark">
                        <span class="text-xs text-noir-muted">ID: {{ String(personage.id).padStart(4, '0') }}</span>
                        <RouterLink :to="`/personages/${personage.id}`" class="text-noir-accent text-sm hover:text-white hover:underline decoration-noir-accent underline-offset-4 uppercase font-semibold transition-all">
                            ACCESS_FILE >
                        </RouterLink>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Modal :isOpen="showModal" title="NEW PERSONAGE ENTRY" @close="showModal = false">
            <form @submit.prevent="createPersonage" class="space-y-4">
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Naam</label>
                    <input v-model="form.naam" type="text" required class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                </div>
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Rol</label>
                    <input v-model="form.rol" type="text" required class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                </div>
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Beschrijving</label>
                    <textarea v-model="form.beschrijving" required rows="3" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                     <div>
                        <label class="block text-noir-muted text-xs uppercase mb-1">Menselijke Status</label>
                        <input v-model="form.menselijke_status" type="text" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                    </div>
                     <div>
                        <label class="block text-noir-muted text-xs uppercase mb-1">Replicant Status</label>
                        <input v-model="form.replicant_status" type="text" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                    </div>
                </div>
                 <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Motief</label>
                    <textarea v-model="form.motief" rows="2" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none"></textarea>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <input v-model="form.is_replicant" type="checkbox" id="is_replicant" class="rounded bg-noir-darker border-noir-dark text-noir-accent focus:ring-noir-accent">
                        <label for="is_replicant" class="text-white text-sm">Is Replicant?</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="form.is_playable" type="checkbox" id="is_playable" class="rounded bg-noir-darker border-noir-dark text-noir-warning focus:ring-noir-warning">
                        <label for="is_playable" class="text-white text-sm">Playable Character?</label>
                    </div>
                </div>
                <div class="pt-4 flex justify-end gap-2 text-sm">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-noir-muted hover:text-white transition-colors">CANCEL</button>
                    <button type="submit" class="px-4 py-2 bg-noir-accent text-white rounded hover:bg-blue-600 transition-colors">CREATE RECORD</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
