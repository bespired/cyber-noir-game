<script setup>
import { ref, onMounted } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';
import Modal from '../../components/Modal.vue';
import { useToast } from '../../composables/useToast';
import ClickButton from '../../components/inputs/ClickButton.vue';
import DialoogThumb from '../../components/thumbs/DialoogThumb.vue';
import HeaderBar from '../../components/bars/HeaderBar.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

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
        <header-bar label="DIALOOG DATABASE">
            <template #actions>
                <click-button label="NIEUWE SEQUENCE" icon="+" buttonType="add" @click="openModal" />
            </template>
        </header-bar>

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
        <Modal :isOpen="showModal" title="NEW DIALOGUE SEQUENCE" okLabel="INITIALIZE" @close="showModal = false" @ok="createConversation">
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
            </form>
        </Modal>
    </div>
</template>
