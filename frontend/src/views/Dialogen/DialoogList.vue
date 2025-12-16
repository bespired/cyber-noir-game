<script setup>
import { ref, onMounted } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';
import Modal from '../../components/Modal.vue';

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
                alert("Invalid JSON");
                return;
            }
        }

        await axios.post('/api/dialogen', payload);
        showModal.value = false;
        await fetchConversations();
    } catch (e) {
        console.error("Failed to create conversation", e);
    }
};
</script>

<template>
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white tracking-tight">DIALOGUE DATABASE</h1>
            <button @click="openModal" class="bg-noir-accent text-white px-4 py-2 rounded hover:bg-blue-500 hover:shadow-[0_0_15px_rgba(59,130,246,0.5)] transition-all duration-300 uppercase font-bold text-sm tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                + NEW SEQUENCE
            </button>
        </div>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            LOADING_TRANSCRIPTS...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="conv in conversations" :key="conv.id" class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg hover:border-noir-accent transition-colors group">
                <div class="flex justify-between items-start mb-2">
                    <h2 class="text-xl font-bold text-white group-hover:text-noir-accent transition-colors">{{ conv.titel }}</h2>
                    <span v-if="conv.personage" class="text-xs text-noir-muted bg-noir-dark px-2 py-1 rounded border border-noir-dark">
                        {{ conv.personage.naam }}
                    </span>
                </div>

                <p class="text-noir-muted text-xs font-mono mb-4">ID: {{ conv.id }}</p>

                <div class="flex justify-between items-center mt-4 pt-4 border-t border-noir-dark">
                    <span class="text-xs text-noir-muted truncate max-w-[100px]">{{ conv.is_active ? 'ACTIEF' : 'INACTIEF' }}</span>
                    <RouterLink :to="`/dialogen/${conv.id}`" class="text-noir-accent text-sm hover:text-white hover:underline decoration-noir-accent underline-offset-4 uppercase font-semibold transition-all">
                        EDIT NODE LOGIC >
                    </RouterLink>
                </div>
            </div>
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
