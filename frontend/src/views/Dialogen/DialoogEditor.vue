<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import axios from '../../axios';
import { useToast } from '../../composables/useToast';
import Modal from '../../components/Modal.vue';
import NodeDetailPanel from './NodeDetailPanel.vue';

const toast = useToast();
const route = useRoute();
const conversation = ref(null);
const loading = ref(true);
const error = ref(null);

const nodes = ref({});
const selectedNodeId = ref(null);
const dragging = ref(null);
const canvas = ref(null);
const isNewNodeModalOpen = ref(false);
const newNodeData = ref({ id: '', text: '' });

// Constants for node appearance
const NODE_WIDTH = 220;
const NODE_HEIGHT = 160;

onMounted(async () => {
    await fetchConversation();
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
});

onUnmounted(() => {
    window.removeEventListener('mousemove', onMouseMove);
    window.removeEventListener('mouseup', onMouseUp);
});

const fetchConversation = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/dialogen/${route.params.id}`);
        conversation.value = response.data;
        const tree = conversation.value.tree || { nodes: {} };
        nodes.value = tree.nodes || {};

        // Ensure starting positions if they don't exist
        Object.keys(nodes.value).forEach((id, index) => {
            if (nodes.value[id].x === undefined) {
                nodes.value[id].x = 100 + (index * 250) % 1000;
                nodes.value[id].y = 100 + Math.floor(index / 4) * 250;
            }
        });
    } catch (e) {
        console.error(e);
        error.value = "Failed to load conversation";
    } finally {
        loading.value = false;
    }
};

const saveChanges = async () => {
    error.value = null;
    try {
        const payload = {
            ...conversation.value,
            tree: { nodes: nodes.value }
        };

        await axios.put(`/api/dialogen/${conversation.value.id}`, payload);
        toast.success('CHANGES_SAVED');
    } catch (e) {
        error.value = "Failed to save";
        console.error(e);
    }
};

// Dragging Logic
const onMouseDown = (event, id) => {
    // Only drag if left clicking node header or body, not buttons
    if (event.target.closest('button') || event.target.closest('a')) return;

    selectedNodeId.value = id;
    const node = nodes.value[id];
    dragging.value = {
        node,
        startX: event.clientX,
        startY: event.clientY,
        initialX: node.x,
        initialY: node.y
    };
};

const onMouseMove = (event) => {
    if (!dragging.value) return;

    const dx = event.clientX - dragging.value.startX;
    const dy = event.clientY - dragging.value.startY;

    dragging.value.node.x = dragging.value.initialX + dx;
    dragging.value.node.y = dragging.value.initialY + dy;
};

const onMouseUp = () => {
    dragging.value = null;
};

// Node Management
const openNewNodeModal = () => {
    newNodeData.value = { id: '', text: '' };
    isNewNodeModalOpen.value = true;
};

const createNewNode = () => {
    const id = newNodeData.value.id.trim();
    if (!id || nodes.value[id]) {
        toast.error('Invalid ID or ID already exists');
        return;
    }

    nodes.value[id] = {
        text: newNodeData.value.text,
        options: [],
        x: 100,
        y: 100
    };

    isNewNodeModalOpen.value = false;
    selectedNodeId.value = id;
};

const updateNode = (id, data) => {
    nodes.value[id] = { ...nodes.value[id], ...data };
    selectedNodeId.value = null;
    saveChanges();
};

const deleteNode = (id) => {
    if (confirm(`Weet je zeker dat je node "${id}" wilt verwijderen?`)) {
        delete nodes.value[id];
        selectedNodeId.value = null;
        saveChanges();
    }
};

const isEndOption = (option) => {
    return option.actions?.some(a => a.type === 'END TALK');
};

// Connections (Wires) Logic
const connections = computed(() => {
    const list = [];
    Object.entries(nodes.value).forEach(([sourceId, sourceNode]) => {
        if (!sourceNode.options) return;

        sourceNode.options.forEach((opt, optIdx) => {
            if (opt.next && nodes.value[opt.next] && !isEndOption(opt)) {
                list.push({
                    id: `${sourceId}-${optIdx}-${opt.next}`,
                    sourceId,
                    targetId: opt.next,
                    optionIdx: optIdx,
                    optionCount: sourceNode.options.length
                });
            }
        });
    });
    return list;
});

const getLinePath = (conn) => {
    const s = nodes.value[conn.sourceId];
    const t = nodes.value[conn.targetId];
    if (!s || !t) return '';

    // Source position: right side, centered on the dot
    const sx = s.x + NODE_WIDTH - 20;

    // Y-calculation based on fixed heights:
    // Padding (16) + Header (~15) + Header MB (8) + Textbox (64) + Border/Gap (8) = 111px base
    // First option dot center is at base + (row center 12px) = 123px
    // Row height including gap is approx 28px
    const sy = s.y + 130 + (conn.optionIdx * 28.5);

    // Target position: left side, middle of the input point (top-10 = 40px)
    const tx = t.x;
    const ty = t.y + 49;

    const dx = Math.abs(tx - sx);
    const offset = Math.max(50, dx * 0.4);

    return `M ${sx} ${sy} C ${sx + offset} ${sy}, ${tx - offset} ${ty}, ${tx} ${ty}`;
};

const getLineColor = (conn) => {
    const s = nodes.value[conn.sourceId];
    const t = nodes.value[conn.targetId];
    if (t.x < s.x - 50) {
        return 'stroke-noir-danger/40'; // Loopback
    }
    return 'stroke-noir-accent/40'; // Forward
};

</script>

<template>
    <div v-if="loading" class="h-[calc(100vh-4rem)] flex items-center justify-center bg-black">
         <div class="text-noir-muted font-mono animate-pulse">DECODEREN_SEQUENCE...</div>
    </div>

    <div v-else class="h-[calc(100vh-4rem)] flex flex-col bg-black overflow-hidden relative">
        <!-- Toolbar -->
        <div class="h-16 bg-noir-dark border-b border-noir-panel px-6 flex justify-between items-center z-30 shadow-xl">
            <div class="flex items-center gap-6">
                <RouterLink to="/dialogen" class="text-noir-muted hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </RouterLink>
                <div v-if="conversation">
                    <h1 class="text-xl font-bold text-white uppercase tracking-wider">{{ conversation.titel }}</h1>
                    <p class="text-[10px] text-noir-accent font-mono" v-if="conversation.personage">TARGET: {{ conversation.personage.naam }}</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <span class="text-[10px] text-noir-muted font-mono uppercase hidden md:inline">Drag nodes to organize | Click to edit</span>
                <button @click="openNewNodeModal" class="bg-noir-accent/10 text-noir-accent border border-noir-accent/30 px-4 py-2 rounded hover:bg-noir-accent hover:text-black transition-all text-xs font-bold uppercase tracking-widest">
                    + Nieuw Praatje
                </button>
                <RouterLink :to="`/dialogen/${conversation.id}/emulate`" class="bg-noir-accent/10 text-noir-accent border border-noir-accent/30 px-4 py-2 rounded hover:bg-noir-accent hover:text-black transition-all text-xs font-bold uppercase tracking-widest">
                    Emuleer 3D
                </RouterLink>
                <button @click="saveChanges" class="bg-noir-success/20 text-noir-success border border-noir-success px-4 py-2 rounded hover:bg-noir-success hover:text-black transition-all text-xs font-bold uppercase tracking-widest">
                    Save Tree
                </button>
            </div>
        </div>

        <div class="flex-grow flex overflow-hidden">
            <!-- Canvas -->
            <div class="flex-grow relative bg-[#050505] overflow-auto cursor-crosshair custom-scrollbar" ref="canvas">
                <!-- Grid Background -->
                <div class="absolute inset-0 pointer-events-none opacity-20"
                     style="background-image: radial-gradient(#1a1a1a 1px, transparent 1px); background-size: 30px 30px;">
                </div>

                <!-- SVG Connections -->
                <svg class="absolute inset-0 pointer-events-none w-[5000px] h-[5000px] z-60">
                    <defs>
                        <filter id="glow">
                            <feGaussianBlur stdDeviation="2" result="coloredBlur"/>
                            <feMerge>
                                <feMergeNode in="coloredBlur"/>
                                <feMergeNode in="SourceGraphic"/>
                            </feMerge>
                        </filter>
                    </defs>
                    <path v-for="conn in connections" :key="conn.id"
                          :d="getLinePath(conn)"
                          fill="none"
                          stroke-width="2"
                          :class="getLineColor(conn)"
                          stroke-dasharray="5,5"
                          filter="url(#glow)"
                    />
                </svg>

                <!-- Nodes -->
                <div v-for="(node, id) in nodes" :key="id"
                     @mousedown="onMouseDown($event, id)"
                     @click="selectedNodeId = id"
                     :style="{
                        left: `${Math.round(node.x)}px`,
                        top: `${Math.round(node.y)}px`,
                        width: `${NODE_WIDTH}px`,
                        minHeight: `${NODE_HEIGHT}px`
                     }"
                     class="absolute bg-noir-panel/90 border border-noir-dark group hover:border-noir-accent z-20 shadow-2xl flex flex-col select-none backdrop-blur-sm p-4 rounded-sm cursor-grab active:cursor-grabbing"
                     :class="{
                        'border-noir-accent ring-1 ring-noir-accent/50 z-50': selectedNodeId === id,
                        'border-noir-success shadow-[0_0_15px_rgba(16,185,129,0.2)]': id === 'root'
                     }">

                    <!-- Entry Label -->
                    <div v-if="id === 'root'"
                        class="absolute -top-2 left-1/2 -translate-x-1/2 bg-noir-success text-black text-[9px] font-bold px-2 py-0.5 rounded-full z-10 uppercase">
                        ENTREE
                    </div>

                    <div class="flex justify-between items-start mb-2">
                        <span class="text-[9px] bg-black/60 px-1.5 py-0.5 rounded text-noir-accent font-mono border border-noir-accent/20">
                            {{ id }}
                        </span>
                        <div v-if="id === 'root' || id === 'start'" class="w-1.5 h-1.5 rounded-full bg-noir-success shadow-[0_0_5px_#10b981]"></div>
                    </div>

                    <div class="h-16 mb-2 overflow-hidden border-b border-white/5">
                        <p class="text-white text-xs leading-relaxed italic opacity-80">
                            "{{ node.text || '...' }}"
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <div v-for="(opt, idx) in node.options" :key="idx" class="flex items-center gap-2">
                            <div class="flex-grow bg-black/40 px-2 py-1 rounded text-[10px] text-noir-muted truncate border border-white/5"
                                 :class="{ 'border-noir-danger/30 text-noir-danger/80': isEndOption(opt) }">
                                {{ opt.text }}
                            </div>

                            <!-- Point for connection or END marker -->
                            <div v-if="isEndOption(opt)" class="flex items-center gap-1">
                                <div class="w-1.5 h-1.5 rounded-full bg-noir-danger shadow-[0_0_5px_rgba(239,68,68,0.5)]"></div>
                                <span class="text-[8px] font-bold text-noir-danger uppercase mt-0.5">END</span>
                            </div>
                            <div v-else class="w-1.5 h-1.5 rounded-full" :class="opt.next ? 'bg-noir-accent' : 'bg-noir-dark'"></div>
                        </div>
                    </div>

                    <!-- Input point -->
                    <div v-if="id !== 'root'" class="absolute -left-1 top-10 w-2 h-4 bg-noir-dark border border-noir-accent/30 rounded-r-full"></div>
                </div>

                <!-- Spacer for scrollability -->
                <div class="w-[5000px] h-[5000px] pointer-events-none"></div>
            </div>

            <!-- Detail Panel -->
            <NodeDetailPanel
                v-if="selectedNodeId"
                :nodeId="selectedNodeId"
                :nodeData="nodes[selectedNodeId]"
                @update="updateNode(selectedNodeId, $event)"
                @close="selectedNodeId = null"
                @delete="deleteNode"
            />
        </div>

        <!-- New Node Modal -->
        <Modal :isOpen="isNewNodeModalOpen" title="NIEUW PRAATJE" okLabel="Praatje Toevoegen" @close="isNewNodeModalOpen = false" @ok="createNewNode">
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-noir-muted uppercase mb-1">ID (Uniek)</label>
                    <input v-model="newNodeData.id" class="w-full bg-black border border-noir-dark text-white p-2 rounded focus:border-noir-accent outline-none font-mono" placeholder="bijv. info_sector_4">
                </div>
                <div>
                    <label class="block text-xs font-bold text-noir-muted uppercase mb-1">Eerste Tekst</label>
                    <textarea v-model="newNodeData.text" class="w-full bg-black border border-noir-dark text-white p-2 rounded focus:border-noir-accent outline-none min-h-[100px]" placeholder="Inhoud van het gesprek..."></textarea>
                </div>
            </div>
        </Modal>
    </div>
</template>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    line-clamp: 3;
    overflow: hidden;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
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

svg path {
    stroke-dashoffset: 0;
    transition: stroke 0.3s ease;
}

@keyframes dash {
    to {
        stroke-dashoffset: -20;
    }
}

svg path {
    animation: dash 5s linear infinite;
}
</style>
