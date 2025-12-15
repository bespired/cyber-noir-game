<script setup>
import { ref, onMounted } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';
import Modal from '../../components/Modal.vue';

const aanwijzingen = ref([]);
const loading = ref(true);
const showModal = ref(false);
const form = ref({
    titel: '',
    beschrijving: '',
    is_kritisch: false
});

onMounted(async () => {
    await fetchAanwijzingen();
});

const fetchAanwijzingen = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/aanwijzingen');
        aanwijzingen.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

const openModal = () => {
    form.value = {
        titel: '',
        beschrijving: '',
        is_kritisch: false
    };
    showModal.value = true;
};

const createAanwijzing = async () => {
    try {
        await axios.post('/api/aanwijzingen', form.value);
        showModal.value = false;
        await fetchAanwijzingen();
    } catch (e) {
        console.error("Failed to create aanwijzing", e);
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
            <h1 class="text-3xl font-bold text-white tracking-tight">AANWIJZINGEN</h1>
            <button @click="openModal" class="bg-noir-danger text-white px-4 py-2 rounded hover:bg-red-500 hover:shadow-[0_0_15px_rgba(239,68,68,0.5)] transition-all duration-300 uppercase font-bold text-sm tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                + LOG EVIDENCE
            </button>
        </div>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            DECRYPTING_FILES...
        </div>

        <div v-else class="overflow-x-auto bg-noir-panel border border-noir-dark rounded shadow-lg">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-noir-dark text-noir-muted uppercase text-xs tracking-wider border-b border-noir-panel">
                        <th class="p-4 font-bold">Visual</th>
                        <th class="p-4 font-bold">Status</th>
                        <th class="p-4 font-bold">Titel</th>
                        <th class="p-4 font-bold">Linked Person</th>
                        <th class="p-4 font-bold">Location</th>
                        <th class="p-4 font-bold text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-noir-dark">
                    <tr v-for="clue in aanwijzingen" :key="clue.id" class="hover:bg-noir-dark/50 transition-colors group">
                        <td class="p-4">
                            <div v-if="clue.artwork && clue.artwork.length > 0" class="w-12 h-12">
                                <img 
                                    :src="getImageUrl(clue.artwork[0].bestandspad)" 
                                    :alt="clue.titel" 
                                    class="w-full h-full aspect-square object-cover rounded border border-noir-dark"
                                >
                            </div>
                            <div v-else class="w-12 h-12 bg-noir-dark rounded border border-noir-dark/50 flex items-center justify-center">
                                <span class="text-xs text-noir-muted">N/A</span>
                            </div>
                        </td>
                        <td class="p-4">
                            <span v-if="clue.is_kritisch" class="inline-block px-2 py-1 bg-noir-danger/20 text-noir-danger border border-noir-danger rounded text-xs font-bold uppercase">CRITICAL</span>
                            <span v-else class="inline-block px-2 py-1 bg-noir-muted/20 text-noir-muted border border-noir-muted rounded text-xs font-bold uppercase">STANDARD</span>
                        </td>
                        <td class="p-4 font-bold text-white group-hover:text-noir-accent transition-colors">{{ clue.titel }}</td>
                        <td class="p-4 text-sm text-noir-text">
                            <span v-if="clue.personage" class="text-noir-accent">{{ clue.personage.naam }}</span>
                            <span v-else class="text-noir-muted italic">--</span>
                        </td>
                        <td class="p-4 text-sm text-noir-text">
                            <span v-if="clue.locatie" class="text-noir-warning">{{ clue.locatie.naam }}</span>
                            <span v-else class="text-noir-muted italic">--</span>
                        </td>
                        <td class="p-4 text-right">
                            <RouterLink :to="`/aanwijzingen/${clue.id}`" class="text-noir-muted hover:text-white hover:underline decoration-noir-muted underline-offset-4 uppercase text-xs font-bold transition-all cursor-pointer">
                                EDIT
                            </RouterLink>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Create Modal -->
        <Modal :isOpen="showModal" title="LOG NEW EVIDENCE" @close="showModal = false">
            <form @submit.prevent="createAanwijzing" class="space-y-4">
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Titel</label>
                    <input v-model="form.titel" type="text" required class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-danger focus:outline-none">
                </div>
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Beschrijving</label>
                    <textarea v-model="form.beschrijving" required rows="3" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-danger focus:outline-none"></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input v-model="form.is_kritisch" type="checkbox" id="is_kritisch" class="rounded bg-noir-darker border-noir-dark text-noir-danger focus:ring-noir-danger">
                    <label for="is_kritisch" class="text-white text-sm uppercase">Critical Evidence?</label>
                </div>
                <div class="pt-4 flex justify-end gap-2 text-sm">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-noir-muted hover:text-white transition-colors">CANCEL</button>
                    <button type="submit" class="px-4 py-2 bg-noir-danger text-white font-bold rounded hover:bg-red-600 transition-colors">LOG EVIDENCE</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
