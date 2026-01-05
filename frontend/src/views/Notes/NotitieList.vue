<script setup>
import { ref, onMounted } from 'vue';
import axios from '../../axios';
import Modal from '../../components/Modal.vue';
import ClickButton from '../../components/inputs/ClickButton.vue';
import NotitieThumb from '../../components/thumbs/NotitieThumb.vue';
import { useI18n } from 'vue-i18n';
import HeaderBar from '../../components/bars/HeaderBar.vue';

const { t } = useI18n();
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
    if (!confirm(t('notes.confirm_delete'))) return;
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
        <header-bar :label="t('notes.my_notes')">
            <template #actions>
                <click-button :label="t('notes.new_idea')" icon="+" buttonType="add" @click="openModal" />
            </template>
        </header-bar>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            {{ t('notes.decrypting') }}
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
        <Modal :isOpen="showModal" :title="t('notes.new_entry')" :okLabel="t('notes.save_record')" @close="showModal = false" @ok="createNote">
            <form @submit.prevent="createNote" class="space-y-4">
                <div>
                    <label class="form-label">{{ t('notes.title') }}</label>
                    <input v-model="form.titel" type="text" required class="form-input" :placeholder="t('notes.enter_topic')">
                </div>
                <div>
                    <label class="form-label">{{ t('notes.content') }}</label>
                    <textarea v-model="form.inhoud" required rows="5" class="form-input" :placeholder="t('notes.describe_idea')"></textarea>
                </div>
            </form>
        </Modal>
    </div>
</template>
