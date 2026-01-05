<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from '../../axios';
import { useRouter } from 'vue-router';
import draggable from 'vuedraggable';
import ClickButton  from '../../components/inputs/ClickButton.vue';
import { useToast } from '../../composables/useToast';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const toast = useToast();

const router = useRouter();
const locaties = ref([]);
const loading = ref(true);
const saving = ref(false);
const sectors = ref([]);
const selectedSector = ref('');

onMounted(async () => {
    await Promise.all([
        fetchSectors(),
        fetchLocaties()
    ]);
});

const fetchSectors = async () => {
    try {
        const response = await axios.get('/api/sectoren');
        sectors.value = response.data;
    } catch (e) {
        console.error("Failed to fetch sectors", e);
    }
}

const fetchLocaties = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/locaties');
        locaties.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

// Draggable list logic
const draggableLocaties = computed({
    get: () => {
        if (!selectedSector.value) return locaties.value;
        return locaties.value.filter(l =>
            l.scenes && l.scenes.some(s => s.sector_id == selectedSector.value)
        );
    },
    set: (newVisibleOrder) => {
        // We handle the local state update before saving
        const oldVisibleList = draggableLocaties.value;
        const updatedLocaties = [...locaties.value];
        const orderValues = oldVisibleList.map(l => l.volgorde).sort((a, b) => a - b);

        newVisibleOrder.forEach((item, index) => {
            const globalIndex = updatedLocaties.findIndex(l => l.id === item.id);
            if (globalIndex !== -1) {
                updatedLocaties[globalIndex].volgorde = orderValues[index];
            }
        });

        updatedLocaties.sort((a, b) => (a.volgorde || 0) - (b.volgorde || 0) || a.id - b.id);
        locaties.value = updatedLocaties;
    }
});

const saveOrder = async () => {
    saving.value = true;
    try {
        await axios.post('/api/locaties/reorder', {
            ids: locaties.value.map(l => l.id)
        });
        router.push('/locaties');
    } catch (e) {
        console.error("Failed to save order", e);
        toast.error(t('locations.reorder_error'));
    } finally {
        saving.value = false;
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
    <div class="container mx-auto p-6 min-h-screen">
        <div class="flex justify-between items-center mb-8 border-b border-noir-dark pb-6">
            <div class="flex items-center gap-6">
                <button @click="router.push('/locaties')" class="text-noir-muted hover:text-white transition-colors flex items-center gap-2 font-bold uppercase tracking-widest text-sm">
                    <span class="text-xl">←</span> {{ t('locations.title') }}
                </button>
                <h1 class="page-header">{{ t('locations.reorder_title') }}</h1>

                <div class="flex items-center gap-3 ml-4">
                    <span class="text-xs text-noir-muted uppercase">{{ t('locations.filter') }}</span>
                    <select v-model="selectedSector" class="bg-noir-darker text-noir-text border border-noir-dark rounded px-3 py-1 text-xs focus:border-noir-warning focus:outline-none uppercase">
                        <option value="">{{ t('locations.all_sectors') }}</option>
                        <option v-for="sector in sectors" :key="sector.id" :value="sector.id">
                            {{ sector.naam }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="flex gap-4">
<!--                 <button @click="saveOrder" :disabled="saving" class="bg-noir-accent text-white px-6 py-2 rounded hover:bg-blue-600 hover:shadow-[0_0_15px_rgba(59,130,246,0.5)] transition-all duration-300 uppercase font-bold text-sm tracking-wider transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ saving ? t('locations.transmitting') : t('locations.commit') }}
                </button> -->

                <click-button
                    :label="`${saving ? t('locations.transmitting') : t('locations.commit')}`"
                    buttonType="green"
                />
            </div>
        </div>

        <div v-if="loading" class="flex flex-col items-center justify-center py-40 gap-4">
            <div class="w-12 h-12 border-4 border-noir-accent border-t-transparent rounded-full animate-spin"></div>
            <div class="text-noir-muted animate-pulse font-mono tracking-widest text-lg">{{ t('locations.init_buffer') }}</div>
        </div>

        <div v-else>
            <draggable
                v-model="draggableLocaties"
                item-key="id"
                class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4"
                ghost-class="opacity-0"
                drag-class="brightness-125"
                animation="200"
            >
                <template #item="{ element: locatie }">
                    <div class="bg-noir-panel border border-noir-dark rounded overflow-hidden shadow-lg hover:border-noir-accent transition-all cursor-grab active:cursor-grabbing group">
                        <!-- Thumbnail Wrapper (250x200 requested, we use aspect-ratio for responsiveness) -->
                        <div class="w-full h-[180px] bg-noir-darker relative overflow-hidden">
                            <img v-if="locatie.artwork && locatie.artwork.length > 0"
                                :src="getImageUrl(locatie.artwork[0].bestandspad)"
                                :alt="locatie.naam"
                                class="w-full h-full object-cover  group-hover:opacity-100 transition-opacity"
                            >
                            <div v-else class="w-full h-full flex flex-col items-center justify-center text-noir-muted p-4 text-center">
                                <span class="text-2xl mb-2">📷</span>
                                <span class="text-[10px] uppercase tracking-tighter line-clamp-2">{{ locatie.naam }}</span>
                            </div>

                            <!-- Index Badge -->
                            <div class="absolute top-1 left-1 bg-black/80 text-[10px] text-noir-accent font-mono px-1.5 py-0.5 rounded border border-noir-accent/30">
                                #{{ String(locatie.id).padStart(3, '0') }}
                            </div>
                        </div>

                        <!-- Name label -->
                        <div class="p-2 bg-noir-dark border-t border-noir-dark">
                            <h3 class="text-[11px] font-bold text-white truncate uppercase tracking-wider">{{ locatie.naam }}</h3>
                        </div>
                    </div>
                </template>
            </draggable>

            <div v-if="draggableLocaties.length === 0" class="text-center py-40 border-2 border-dashed border-noir-dark rounded text-noir-muted uppercase tracking-widest bg-noir-darker/30">
                {{ t('locations.no_locations_buffer') }}
            </div>
        </div>

        <div class="mt-12 text-center text-[10px] text-noir-muted uppercase tracking-[0.2em] font-mono">
            {{ t('locations.drag_hint') }}
        </div>
    </div>
</template>

<style scoped>
.cursor-grab { cursor: grab; }
.cursor-grabbing { cursor: grabbing; }
</style>
