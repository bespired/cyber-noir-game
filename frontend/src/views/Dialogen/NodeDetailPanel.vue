<script setup>
import { ref, watch, toRaw } from 'vue';

const props = defineProps({
    nodeId: {
        type: String,
        required: true
    },
    nodeData: {
        type: Object,
        default: () => ({ text: '', options: [] })
    }
});

const emit = defineEmits(['update', 'close', 'delete']);

// Use toRaw or structuredClone if available to avoid Proxy issues during deep copy
const cloneData = (data) => {
    try {
        return JSON.parse(JSON.stringify(data || { text: '', options: [] }));
    } catch (e) {
        return { text: '', options: [] };
    }
};

const editedNode = ref(cloneData(props.nodeData));

watch(() => props.nodeData, (newVal) => {
    editedNode.value = cloneData(newVal);
}, { deep: true });

const save = () => {
    emit('update', editedNode.value);
};

const addOption = () => {
    if (!editedNode.value.options) editedNode.value.options = [];
    editedNode.value.options.push({
        text: 'Nieuw antwoord',
        next: '',
        actions: []
    });
};

const removeOption = (index) => {
    editedNode.value.options.splice(index, 1);
};

const addAction = (option) => {
    if (!option.actions) option.actions = [];
    option.actions.push({
        type: 'SAY',
        value: ''
    });
};

const removeAction = (option, index) => {
    option.actions.splice(index, 1);
};

const hasEndTalk = (option) => {
    return option.actions?.some(a => a.type === 'END TALK');
};

const actionTypes = [
    { value: 'END TALK', label: 'EINDE' },
    { value: 'SET GAME TAG', label: 'PLAATS GAME TAG' },
    { value: 'REMOVE GAME TAG', label: 'VERWIJDER GAME TAG' },
    { value: 'SET NPC TAG', label: 'PLAATS NPC TAG' },
    { value: 'REMOVE NPC TAG', label: 'VERWIJDER NPC TAG' },
    { value: 'GOTO SCENE', label: 'GOTO SCENE' },
    { value: 'WAIT x SECONDS', label: 'WACHT x SECONDEN' },
    { value: 'WALK_TO', label: 'LOOP NAAR' },
];

const getActionPlaceholder = (type) => {
    switch (type) {
        case 'WAIT x SECONDS': return 'Aantal seconden (bijv. 2)';
        case 'GOTO SCENE': return 'Scene ID of Naam';
        case 'SET GAME TAG':
        case 'REMOVE GAME TAG':
        case 'SET NPC TAG':
        case 'REMOVE NPC TAG': return 'Tag naam';
        default: return 'Parameter...';
    }
};
</script>

<template>
    <div class="bg-noir-panel border-l border-noir-dark flex flex-col h-full shadow-2xl z-[70] min-w-[380px]" >
        <!-- Header -->
        <div class="p-2 border-b border-noir-dark flex justify-between items-center bg-noir-dark/50">
            <h2 class="text-lg font-bold text-white uppercase tracking-wider">Properties</h2>
            <button @click="$emit('close')" class="text-noir-muted hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="flex-grow overflow-y-auto p-4 space-y-6 custom-scrollbar">
            <!-- ID Area -->
            <div>
                <label class="block text-[10px] font-bold text-noir-muted uppercase mb-1">Naam (ID)</label>
                <div class="w-full bg-black/40 border border-noir-dark text-noir-muted p-2 rounded text-sm font-mono">
                    {{ nodeId }}
                </div>
            </div>

            <!-- Content Area -->
            <div>
                <label class="block text-[10px] font-bold text-noir-muted uppercase mb-1">Tekst / Beschrijving</label>
                <textarea v-model="editedNode.text"
                    class="w-full bg-black border border-noir-dark text-white p-3 rounded text-sm min-h-[100px] focus:border-noir-accent outline-none"
                    placeholder="Wat zegt het personage?"></textarea>
            </div>

            <!-- Options Area -->
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-xs font-bold text-noir-muted uppercase tracking-widest">KEUZES</h3>
                    <span class="text-[10px] text-noir-muted font-mono">{{ editedNode.options?.length || 0 }} KEUZE(S)</span>
                </div>

                <div v-for="(option, idx) in editedNode.options" :key="idx" class="bg-noir-dark/30 border border-noir-dark rounded p-3 space-y-3">
                    <div class="flex justify-between items-start gap-2">
                        <span class="text-[10px] font-mono text-noir-accent opacity-50">{{ String(idx + 1).padStart(2, '0') }}</span>
                        <textarea v-model="option.text" class="flex-grow bg-black border border-noir-dark text-white p-2 rounded text-xs focus:border-noir-accent outline-none" rows="2"></textarea>
                        <button @click="removeOption(idx)" class="text-noir-danger hover:bg-noir-danger/10 p-1 rounded transition-colors">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <!-- Volgende stap or END indicator -->
                    <div v-if="!hasEndTalk(option)">
                         <label class="block text-[9px] font-bold text-noir-muted uppercase mb-1">Volgende stap</label>
                         <input v-model="option.next" class="w-full bg-black border border-noir-dark text-noir-accent p-1.5 rounded text-[11px] font-mono focus:border-noir-accent outline-none" placeholder="ID van volgende node">
                    </div>
                    <div v-else class="flex items-center gap-2 bg-noir-danger/10 border border-noir-danger/20 p-2 rounded">
                        <div class="w-2 h-2 rounded-full bg-noir-danger"></div>
                        <span class="text-[10px] font-bold text-noir-danger uppercase">TERMINAL: END TALK ACTIVE</span>
                    </div>

                    <!-- Actions -->
                    <div class="space-y-2">
                         <div class="flex justify-between items-center border-t border-noir-dark pt-2">
                             <span class="text-[9px] font-bold text-noir-muted uppercase">Actions</span>
                             <button @click="addAction(option)" class="text-[9px] text-noir-accent hover:underline uppercase">Add Action</button>
                         </div>
                         <div v-for="(action, aidx) in option.actions" :key="aidx" class="flex gap-2 items-center">
                             <select v-model="action.type" class="bg-black border border-noir-dark text-white text-[10px] p-1.5 rounded focus:border-noir-accent outline-none">
                                 <option v-for="type in actionTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                             </select>
                             <input v-if="action.type !== 'END TALK'"
                                    v-model="action.value"
                                    class="flex-grow bg-black border border-noir-dark text-white text-[10px] p-1.5 rounded focus:border-noir-accent outline-none"
                                    :placeholder="getActionPlaceholder(action.type)">
                             <button @click="removeAction(option, aidx)" class="text-noir-danger hover:bg-noir-danger/10 p-1 rounded transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                             </button>
                         </div>
                    </div>
                </div>

                <button @click="addOption" class="w-full py-3 border-2 border-dashed border-noir-dark text-noir-muted hover:border-noir-accent hover:text-white transition-all rounded text-xs font-bold uppercase tracking-widest">
                    + Keuze Toevoegen
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-noir-dark grid grid-cols-2 gap-3 bg-noir-dark/30">
            <button @click="$emit('delete', nodeId)" class="py-2.5 border border-noir-danger text-noir-danger hover:bg-noir-danger hover:text-white transition-all rounded text-xs font-bold uppercase tracking-wider">
                Verwijderen
            </button>
            <button @click="save" class="py-2.5 bg-noir-success/20 text-noir-success border border-noir-success hover:bg-noir-success hover:text-black transition-all rounded text-xs font-bold uppercase tracking-wider">
                Opslaan
            </button>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #1a1a1a;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #2a2a2a;
}
</style>
