<script setup>
import { ref, onMounted } from 'vue';
import axios from '../../axios';
import Modal from '../../components/Modal.vue';

const notes = ref([]);
const loading = ref(true);
const showModal = ref(false);
const form = ref({
    titel: '',
    inhoud: '',
    is_afgerond: false
});

onMounted(async () => {
    await fetchNotes();
});

const fetchNotes = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/notities');
        notes.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const openModal = () => {
    form.value = {
        titel: '',
        inhoud: '',
        is_afgerond: false
    };
    showModal.value = true;
};

const createNote = async () => {
    try {
        await axios.post('/api/notities', form.value);
        showModal.value = false;
        await fetchNotes();
    } catch (e) {
        console.error("Failed to create note", e);
    }
};

const toggleNote = async (note) => {
    try {
        await axios.put(`/api/notities/${note.id}`, {
            ...note,
            is_afgerond: !note.is_afgerond
        });
        // Optimistic update or refresh
        note.is_afgerond = !note.is_afgerond;
    } catch (e) {
        console.error("Failed to update note", e);
    }
};

const deleteNote = async (id) => {
    if (!confirm('BEVESTIG WISSEN?')) return;
    try {
        await axios.delete(`/api/notities/${id}`);
        await fetchNotes();
    } catch (e) {
        console.error("Failed to delete note", e);
    }
};
</script>

<template>
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white tracking-tight">MYN NOTITIES</h1>
            <button @click="openModal" class="bg-noir-accent text-white px-4 py-2 rounded hover:bg-blue-500 hover:shadow-[0_0_15px_rgba(59,130,246,0.5)] transition-all duration-300 uppercase font-bold text-sm tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                + NIEUW IDEE
            </button>
        </div>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            DECRYPTING DATA SHARDS...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="note in notes" :key="note.id"
                 :class="['bg-noir-panel border p-6 rounded shadow-lg transition-all group relative overflow-hidden',
                          note.is_afgerond ? 'border-noir-success/50 opacity-75' : 'border-noir-dark hover:border-noir-accent']">

                <div class="flex justify-between items-start mb-4 relative z-10">
                    <h2 :class="['text-xl font-bold transition-colors', note.is_afgerond ? 'text-noir-success line-through decoration-2' : 'text-white group-hover:text-noir-accent']">
                        {{ note.titel }}
                    </h2>
                    <div class="flex gap-2">
                        <button @click="toggleNote(note)"
                            :class="['cursor-pointer w-1 p-1 rounded border transition-colors', note.is_afgerond ? 'bg-noir-success text-black border-noir-success' : 'border-noir-muted text-noir-muted hover:text-noir-success hover:border-noir-success']" title="Toggle Status">
                            ✓
                        </button>
                        <button @click="deleteNote(note.id)"
                        class="cursor-pointer w-1 p-1 rounded border border-noir-muted text-noir-muted hover:text-noir-danger hover:border-noir-danger transition-colors" title="Delete">
                            🗑️
                        </button>
                    </div>
                </div>

                <p :class="['text-sm mb-4 whitespace-pre-wrap max-h-[7.5rem] overflow-y-auto noir-scrollbar', note.is_afgerond ? 'text-noir-muted' : 'text-noir-text']">{{ note.inhoud }}</p>

                <div class="text-xs text-noir-muted border-t border-noir-dark pt-4 mt-auto flex justify-between">
                    <span>{{ new Date(note.created_at).toLocaleDateString() }}</span>
                    <span>{{ note.is_afgerond ? 'ARCHIEF' : 'ACTIEF' }}</span>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Modal :isOpen="showModal" title="NEW DATA ENTRY" @close="showModal = false">
            <form @submit.prevent="createNote" class="space-y-4">
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Title</label>
                    <input v-model="form.titel" type="text" required class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none" placeholder="Enter topic...">
                </div>
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Content</label>
                    <textarea v-model="form.inhoud" required rows="5" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none" placeholder="Describe your idea..."></textarea>
                </div>
                <div class="pt-4 flex justify-end gap-2 text-sm">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-noir-muted hover:text-white transition-colors">CANCEL</button>
                    <button type="submit" class="px-4 py-2 bg-noir-accent text-white rounded hover:bg-blue-600 transition-colors">BEWAAR RECORD</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
