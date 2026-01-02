<script setup>
import { ref, onMounted } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';
import Modal from '../../components/Modal.vue';
import { useToast } from '../../composables/useToast';
import ClickButton from '../../components/inputs/ClickButton.vue';
import DialoogThumb from '../../components/thumbs/DialoogThumb.vue';

const toast = useToast();

const conversations = ref([]);
const personages = ref([]);
const loading = ref(true);
const showModal = ref(false);
const form = ref({
    titel: '',
    personage_id: '',
    tree: null
});

onMounted(async () => {
    await Promise.all([fetchConversations(), fetchPersonages()]);
});

const fetchConversations = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/dialogen');
        conversations.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const fetchPersonages = async () => {
    try {
        const response = await axios.get('/api/personages');
        personages.value = response.data;
    } catch (e) {
        console.error(e);
    }
};

const openModal = () => {
    form.value = {
        titel: '',
        personage_id: '',
        // Default simple tree
        tree: JSON.stringify({
            root: {
                text: "Start dialogue...",
                options: []
            }
        }, null, 2)
    };
    showModal.value = true;
};

const createConversation = async () => {
    try {
        const payload = { ...form.value };
        // Parse JSON if string (though we might want to just initialize as object in editor)
        // For simple creation let's just create an empty structure or parse the string if we made it editable text
        if (typeof payload.tree === 'string') {
            try {
                payload.tree = JSON.parse(payload.tree);
            } catch (e) {
                toast.error("Invalid JSON");
                return;
            }
        }

        await axios.post('/api/dialogen', payload);
        showModal.value = false;
        await fetchConversations();
    } catch (e) {
    }
};

const deleteConversation = async (id) => {
    if (!window.confirm("CONFIRM DELETION?")) return;

    try {
        await axios.delete(`/api/dialogen/${id}`);
        await fetchConversations();
    } catch (e) {
        console.error("Failed to delete conversation", e);
        toast.error("DELETE FAILED");
    }
};
</script>

<template>
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white tracking-tight">DIALOOG DATABASE</h1>
            <click-button label="NIEUWE SEQUENCE" icon="+" buttonType="add" @click="openModal" />
        </div>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            LOADING_TRANSCRIPTS...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <DialoogThumb 
                v-for="conv in conversations" 
                :key="conv.id" 
                :conv="conv" 
                @delete="deleteConversation" 
            />
        </div>

        <!-- Create Modal -->
        <Modal :isOpen="showModal" title="NEW DIALOGUE SEQUENCE" @close="showModal = false">
            <form @submit.prevent="createConversation" class="space-y-4">
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Title</label>
                    <input v-model="form.titel" type="text" required class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                </div>
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Target Personage</label>
                    <select v-model="form.personage_id" required class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                        <option value="" disabled>SELECT TARGET</option>
                        <option v-for="p in personages" :key="p.id" :value="p.id">{{ p.naam }}</option>
                    </select>
                </div>
                 <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Initial Data (JSON)</label>
                    <textarea v-model="form.tree" rows="5" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none font-mono text-xs"></textarea>
                </div>
                <div class="pt-4 flex justify-end gap-2 text-sm">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-noir-muted hover:text-white transition-colors">CANCEL</button>
                    <button type="submit" class="px-4 py-2 bg-noir-accent text-white rounded hover:bg-blue-600 transition-colors">INITIALIZE</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
