<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from '../../axios';
import { RouterLink } from 'vue-router';

const route = useRoute();
const conversation = ref(null);
const loading = ref(true);
const jsonString = ref('');
const error = ref(null);

onMounted(async () => {
    await fetchConversation();
});

const fetchConversation = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/conversations/${route.params.id}`);
        conversation.value = response.data;
        // Pretty print JSON
        jsonString.value = JSON.stringify(conversation.value.tree, null, 2);
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const saveChanges = async () => {
    error.value = null;
    try {
        // Validate JSON
        const parsedTree = JSON.parse(jsonString.value);
        
        // Update
        const payload = {
            ...conversation.value,
            tree: parsedTree
        };
        
        await axios.put(`/api/conversations/${conversation.value.id}`, payload);
        alert('CHANGES_SAVED');
    } catch (e) {
            if (e instanceof SyntaxError) {
                error.value = "Invalid JSON Syntax";
            } else {
                error.value = "Failed to save";
            }
        console.error(e);
    }
};
</script>

<template>
    <div v-if="loading" class="container mx-auto p-6 text-center text-noir-muted animate-pulse">
        DECODING_SEQUENCE...
    </div>

    <div v-else-if="conversation" class="container mx-auto p-6 h-[calc(100vh-4rem)] flex flex-col">
        <div class="flex items-center mb-6 text-sm text-noir-muted flex-shrink-0">
            <RouterLink to="/conversations" class="hover:text-white">&lt; BACK_TO_LIST</RouterLink>
            <span class="mx-2">/</span>
            <span class="text-white">{{ conversation.titel }}</span>
        </div>

        <div class="bg-noir-panel border border-noir-dark rounded shadow-xl overflow-hidden flex flex-col flex-grow">
            <!-- Header -->
            <div class="p-6 border-b border-noir-dark flex justify-between items-start bg-noir-dark/50 flex-shrink-0">
                <div>
                     <h1 class="text-3xl font-bold text-white mb-1">{{ conversation.titel }}</h1>
                     <div class="text-sm font-mono text-noir-accent" v-if="conversation.personage">Target: {{ conversation.personage.naam }}</div>
                </div>
                <button @click="saveChanges" class="bg-noir-success/20 text-noir-success border border-noir-success px-4 py-2 rounded hover:bg-noir-success hover:text-black hover:shadow-[0_0_15px_rgba(16,185,129,0.5)] transition-all duration-300 uppercase font-bold text-xs tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                    SAVE_TREE
                </button>
            </div>

            <!-- Editor Area -->
            <div class="flex-grow p-6 flex flex-col">
                <div class="flex justify-between items-center mb-2">
                     <label class="block text-xs font-bold text-noir-muted uppercase">Dialogue Tree (JSON)</label>
                     <span v-if="error" class="text-noir-danger text-xs font-bold">{{ error }}</span>
                </div>
                
                <textarea 
                    v-model="jsonString" 
                    class="flex-grow w-full bg-[#0a0a0a] border border-noir-dark text-white p-4 rounded focus:border-noir-accent focus:outline-none font-mono text-sm leading-relaxed resize-none"
                    spellcheck="false"
                ></textarea>
                
                <div class="mt-2 text-xs text-noir-muted">
                    Defining Structure: { "root": { "text": "...", "options": [ { "text": "...", "next": "node_id" } ] } }
                </div>
            </div>
        </div>
    </div>
</template>
