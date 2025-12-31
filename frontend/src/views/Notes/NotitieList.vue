<script setup>
import { ref, onMounted } from 'vue';
import axios from '../../axios';
import Modal from '../../components/Modal.vue';
import ClickButton from '../../components/inputs/ClickButton.vue';
import NotitieThumb from '../../components/thumbs/NotitieThumb.vue';

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
            <h1 class="page-header">MIJN NOTITIES</h1>
            <click-button label="NIEUW IDEE" icon="+" buttonType="add" @click="openModal" />
        </div>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            DECRYPTING DATA SHARDS...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <NotitieThumb
                v-for="note in notes"
                :key="note.id"
                :note="note"
                @toggle="toggleNote"
                @delete="deleteNote"
            />
        </div>

        <!-- Create Modal -->
        <Modal :isOpen="showModal" title="NEW DATA ENTRY" @close="showModal = false">
            <form @submit.prevent="createNote" class="space-y-4">
                <div>
                    <label class="form-label">Title</label>
                    <input v-model="form.titel" type="text" required class="form-input" placeholder="Enter topic...">
                </div>
                <div>
                    <label class="form-label">Content</label>
                    <textarea v-model="form.inhoud" required rows="5" class="form-input" placeholder="Describe your idea..."></textarea>
                </div>
                <div class="pt-4 flex justify-end gap-2 text-sm">
                    <button type="button" @click="showModal = false" class="btn btn--secondary">CANCEL</button>
                    <button type="submit" class="btn btn--primary">BEWAAR RECORD</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
