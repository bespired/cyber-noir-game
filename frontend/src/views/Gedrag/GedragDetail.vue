<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import axios from '../../axios';
import DetailHeader from '../../components/bars/DetailHeader.vue';
import { useToast } from '../../composables/useToast';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const toast = useToast();
const route = useRoute();
const router = useRouter();
const gedrag = ref(null);
const loading = ref(true);
const spawnOptions = ref([]); // From instellingen
const dialogen = ref([]); // From api

const actionTypes = [
    { id: 'walk_to', labelKey: 'behavior.walk_to', icon: '🚶', svg: 'behavior' },
    { id: 'idle',    labelKey: 'behavior.idle',    icon: '⏳', svg: 'idle' },
    { id: 'look_at', labelKey: 'behavior.look_at',   icon: '👀', svg: 'look' },
    { id: 'talk',    labelKey: 'behavior.talk',   icon: '💬', svg: 'dialogue' },
    { id: 'custom',  labelKey: 'behavior.custom',   icon: '⚙️', svg: 'cog' }
];

const getActionIcon = (type) => {
    return actionTypes.find(t => t.id === type)?.svg || 'help';
};
const fetchGedrag = async () => {
    try {
        const [gedragRes, instRes, dialoogRes] = await Promise.all([
            axios.get(`/api/gedrag/${route.params.id}`),
            axios.get('/api/instellingen'),
            axios.get('/api/dialogen')
        ]);

        gedrag.value = gedragRes.data;
        if (!gedrag.value.acties) gedrag.value.acties = [];
        dialogen.value = dialoogRes.data;

        // Handle object-based response from Instelling pluck
        const optionsStr = instRes.data.spawn_options;
        if (optionsStr) {
            try {
                spawnOptions.value = JSON.parse(optionsStr);
            } catch(e) {
                console.error("Failed to parse spawn_options", e);
            }
        }
    } catch (e) {
        toast.error(t('behavior.fetch_data_error'));
    } finally {
        loading.value = false;
    }
};

const saveGedrag = async () => {
    try {
        await axios.put(`/api/gedrag/${gedrag.value.id}`, gedrag.value);
        toast.success(t('behavior.saved'));
    } catch (e) {
        toast.error(t('behavior.save_error'));
    }
};

const deleteGedrag = async () => {
    if (confirm(t('behavior.delete_confirm'))) {
        try {
            await axios.delete(`/api/gedrag/${gedrag.value.id}`);
            router.push('/gedrag');
        } catch (e) {
            toast.error(t('behavior.delete_error'));
        }
    }
};

const addAction = (typeId) => {
    const action = {
        id: Date.now(),
        type: typeId,
        params: {}
    };

    if (typeId === 'walk_to') action.params = { spawn_point: '', distance: 0 };
    if (typeId === 'idle') action.params = { duration: 2.0 };
    if (typeId === 'look_at') action.params = { target: 'player' };
    if (typeId === 'talk') action.params = { dialoog_id: null };

    gedrag.value.acties.push(action);
};

const removeAction = (index) => {
    gedrag.value.acties.splice(index, 1);
};

const moveAction = (index, direction) => {
    const newIndex = index + direction;
    if (newIndex < 0 || newIndex >= gedrag.value.acties.length) return;
    const item = gedrag.value.acties.splice(index, 1)[0];
    gedrag.value.acties.splice(newIndex, 0, item);
};

onMounted(fetchGedrag);
</script>

<template>
    <div v-if="loading" class="container mx-auto p-6 text-center text-noir-muted">
        {{ t('behavior.loading') }}
    </div>

    <div v-else-if="gedrag" class="container mx-auto p-6">

        <detail-header
            backLink="gedrag"
            :backlabel="t('behavior.back_lib')"
            :label="gedrag.naam"
            :save="true"   @save="saveGedrag"
            :remove="true" @remove="deleteGedrag"
        />


        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Sidebar Properties -->
            <div class="lg:col-span-1 space-y-6">
                <div class="panel p-6">
                    <h2 class="text-noir-accent font-bold uppercase tracking-widest text-xs mb-4">{{ t('behavior.properties') }}</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="form-label">{{ t('behavior.name') }}</label>
                            <input v-model="gedrag.naam" type="text" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">{{ t('behavior.description') }}</label>
                            <textarea v-model="gedrag.beschrijving" rows="4" class="form-input" :placeholder="t('behavior.placeholder_desc')"></textarea>
                        </div>
                    </div>

                </div>

                <div class="panel p-6">
                    <h2 class="text-noir-warning font-bold uppercase tracking-widest text-xs mb-4">{{ t('behavior.add_actions') }}</h2>
                    <div class="grid grid-cols-1 gap-2">
                        <button
                            v-for="type in actionTypes"
                            :key="type.id"
                            @click="addAction(type.id)"
                            class="flex items-center gap-3 p-3 bg-noir-dark hover:bg-noir-panel border border-noir-dark hover:border-noir-accent rounded text-xs text-white transition-all group"
                        >
                            <span class="text-lg" style="height: 28px; width:28px">
                                <img :src="`/icons/${type.svg}.svg`" />
                            </span>
                            <span class="font-bold tracking-wider group-hover:text-noir-accent">{{ t(type.labelKey) }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right: Sequence Editor -->
            <div class="lg:col-span-2">
                <div class="panel min-h-[600px] flex flex-col">
                    <div class="p-4 border-b border-noir-dark bg-noir-dark/50 flex justify-between items-center">
                        <h2 class="text-white font-bold uppercase tracking-widest text-xs">{{ t('behavior.sequence') }}</h2>
                        <span class="text-[10px] text-noir-muted">{{ gedrag.acties.length }} {{ t('behavior.steps_total') }}</span>
                    </div>

                    <div class="flex-grow p-6 space-y-4 bg-[url('/img/grid.png')] bg-repeat">
                        <div v-if="gedrag.acties.length === 0" class="h-64 flex items-center justify-center text-noir-muted border-2 border-dashed border-noir-dark rounded italic uppercase tracking-tighter text-sm">
                            {{ t('behavior.no_actions') }}
                        </div>

                        <div
                            v-for="(action, index) in gedrag.acties"
                            :key="action.id"
                            class="relative flex gap-4 p-4 bg-noir-panel border border-noir-dark rounded shadow-xl hover:border-noir-accent/50 transition-colors group"
                        >
                            <!-- Step Number -->
                            <div class="flex flex-col items-center justify-center w-8 text-noir-muted font-mono text-xs border-r border-noir-dark">
                                <span>{{ String(index + 1).padStart(2, '0') }}</span>
                            </div>

                            <!-- Icon -->
                            <div class="text-2xl flex items-center justify-center w-10 h-10">
                                <img :src="`/icons/${getActionIcon(action.type)}.svg`" class="w-8 h-8" />
                            </div>

                            <!-- Action Config -->
                            <div class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="col-span-2 md:col-span-1">
                                    <h4 class="text-[10px] text-noir-muted uppercase font-bold mb-1">
                                        {{ t(actionTypes.find(t => t.id === action.type)?.labelKey) }}
                                    </h4>

                                    <!-- Context-specific inputs -->
                                    <div v-if="action.type === 'walk_to'" class="flex gap-2">
                                        <div class="flex-grow">
                                            <select v-model="action.params.spawn_point" class="form-input text-xs py-1">
                                                <option value="">{{ t('behavior.select_spawn') }}</option>
                                                <option v-for="opt in spawnOptions" :key="opt.id" :value="opt.id">{{ opt.label }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div v-if="action.type === 'idle'" class="flex items-center gap-2">
                                        <input v-model.number="action.params.duration" type="number" step="0.5" class="form-input text-xs py-1 w-24">
                                        <span class="text-[10px] text-noir-muted">{{ t('behavior.seconds') }}</span>
                                    </div>

                                    <div v-if="action.type === 'look_at'" class="flex gap-2">
                                        <select v-model="action.params.target" class="form-input text-xs py-1">
                                            <option value="player">{{ t('behavior.player') }}</option>
                                            <option value="last_spawn">{{ t('behavior.last_spawn') }}</option>
                                        </select>
                                    </div>

                                    <div v-if="action.type === 'talk'" class="flex gap-2">
                                        <div class="flex-grow">
                                            <select v-model="action.params.dialoog_id" class="form-input text-xs py-1">
                                                <option :value="null">{{ t('behavior.select_dialogue') }}</option>
                                                <option v-for="d in dialogen" :key="d.id" :value="d.id">{{ d.titel }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Controls -->
                            <div class="flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="moveAction(index, -1)" class="btn btn--small btn--secondary p-1" :disabled="index === 0">▲</button>
                                <button @click="moveAction(index, 1)" class="btn btn--small btn--secondary p-1" :disabled="index === gedrag.acties.length - 1">▼</button>
                                <button @click="removeAction(index)" class="btn btn--small btn--danger p-1 mt-auto">✕</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
