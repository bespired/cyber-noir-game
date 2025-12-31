<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';
import Modal from '../../components/Modal.vue';
import ClickButton from '../../components/inputs/ClickButton.vue';

const props = defineProps({
    type: {
        type: String,
        default: 'persoon'
    }
});

const personages = ref([]);
const loading = ref(true);
const showModal = ref(false);
const form = ref({
    naam: '',
    rol: '',
    beschrijving: '',
    menselijke_status: '',
    replicant_status: '',
    motief: '',
    is_replicant: false,
    is_playable: false
});

onMounted(async () => {
    await fetchPersonages();
});

const fetchPersonages = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/personages', {
            params: { type: props.type }
        });
        personages.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

watch(() => props.type, () => {
    fetchPersonages();
});

const openModal = () => {
    form.value = {
        type: props.type,
        naam: '',
        rol: '',
        beschrijving: '',
        menselijke_status: '',
        replicant_status: '',
        motief: '',
        is_replicant: false,
        is_playable: false
    };
    showModal.value = true;
};

const createPersonage = async () => {
    try {
        await axios.post('/api/personages', form.value);
        showModal.value = false;
        await fetchPersonages(); // Refresh list
    } catch (e) {
        console.error("Failed to create personage", e);
        // Ideally handle validation errors here
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
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-8">
            <h1 class="page-header">{{ props.type === 'voertuig' ? 'VOERTUIGEN' : 'PERSONAGES' }}</h1>
            <click-button icon="+" :label="`NIEUW ${props.type === 'voertuig' ? 'VOERTUIG' : 'PERSOON'}`" buttonType="add" @click="openModal" />
        </div>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            DATABASE LADEN...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="personage in personages" :key="personage.id"
                 :class="[
                     'panel panel--hover group relative overflow-hidden flex',
                     props.type === 'voertuig' ? 'flex-col' : 'gap-4'
                 ]">
                <!-- Playable Badge -->
                <div v-if="personage.is_playable" class="absolute -right-6 top-4 bg-noir-accent text-black text-[10px] font-bold px-8 py-1 rotate-45 border-y border-white z-10 shadow-lg">
                    PLAYABLE
                </div>

                <!-- Thumbnail (Vehicle Mode: Top, full width) -->
                <div v-if="props.type === 'voertuig'" class="mb-4">
                    <img v-if="personage.artwork && personage.artwork.length > 0"
                        :src="getImageUrl(personage.artwork[0].bestandspad)"
                        :alt="personage.naam"
                        class="w-full aspect-video object-cover rounded border border-noir-dark opacity-80 group-hover:opacity-100 transition-opacity"
                    >
                    <div v-else class="aspect-video bg-noir-darker rounded border border-noir-dark flex items-center justify-center text-noir-muted text-xs uppercase tracking-widest">
                        NO_VISUAL_DATA
                    </div>
                </div>

                <!-- Thumbnail (Personage Mode: Left, fixed width) -->
                <div v-else-if="personage.artwork && personage.artwork.length > 0" class="flex-shrink-0 w-20">
                    <img
                        :src="getImageUrl(personage.artwork[0].bestandspad)"
                        :alt="personage.naam"
                        class="w-full aspect-[2/3] object-cover rounded border border-noir-dark"
                    >
                </div>

                <div class="flex-grow flex flex-col">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h2 class="text-xl font-bold text-white transition-colors leading-tight"
                                :class="props.type === 'voertuig' ? 'group-hover:text-noir-warning' : 'group-hover:text-noir-accent'">
                                {{ personage.naam }}
                            </h2>
                            <span class="text-xs text-noir-muted uppercase tracking-wider">{{ personage.rol }}</span>
                        </div>
                        <div class="h-2 w-2 rounded-full"
                             v-if="props.type === 'persoon'"
                             :class="personage.is_replicant ? 'bg-noir-danger' : 'bg-noir-success'"
                             :title="personage.is_replicant ? 'Replicant Gedetecteerd' : 'Mens Geverifieerd'">
                        </div>
                    </div>

                    <p class="text-noir-text text-sm mb-4 line-clamp-3 flex-grow">{{ personage.beschrijving }}</p>

                    <div class="flex justify-between items-center mt-4 pt-4 border-t border-noir-dark">
                        <span class="text-xs text-noir-muted font-mono">
                            {{ props.type === 'voertuig' ? 'VEH_ID' : 'ID' }}: {{ String(personage.id).padStart(4, '0') }}
                        </span>
                        <RouterLink :to="`/personages/${personage.id}`"
                                    :class="['btn--link', props.type === 'voertuig' ? 'btn--link-warning' : 'btn--link-accent']">
                            DETAILS >
                        </RouterLink>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Modal :isOpen="showModal" :title="props.type === 'voertuig' ? 'NIEUW VOERTUIG' : 'NIEUW PERSONAGE'" @close="showModal = false">
            <form @submit.prevent="createPersonage" class="space-y-4">
                <div>
                    <label class="form-label">Naam</label>
                    <input v-model="form.naam" type="text" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Rol</label>
                    <input v-model="form.rol" type="text" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Beschrijving</label>
                    <textarea v-model="form.beschrijving" required rows="3" class="form-input"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4" v-if="props.type === 'persoon'">
                     <div>
                        <label class="form-label">Menselijke Status</label>
                        <input v-model="form.menselijke_status" type="text" class="form-input">
                    </div>
                     <div>
                        <label class="form-label">Replicant Status</label>
                        <input v-model="form.replicant_status" type="text" class="form-input">
                    </div>
                </div>
                 <div>
                    <label class="form-label">Motief</label>
                    <textarea v-model="form.motief" rows="2" class="form-input"></textarea>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2" v-if="props.type === 'persoon'">
                        <input v-model="form.is_replicant" type="checkbox" id="is_replicant" class="rounded bg-noir-darker border-noir-dark text-noir-accent focus:ring-noir-accent">
                        <label for="is_replicant" class="text-white text-sm">Is Replicant?</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="form.is_playable" type="checkbox" id="is_playable" class="rounded bg-noir-darker border-noir-dark text-noir-warning focus:ring-noir-warning">
                        <label for="is_playable" class="text-white text-sm">{{ props.type === 'voertuig' ? 'Inzetbaar voertuig?' : 'Speelbaar Personage?' }}</label>
                    </div>
                </div>
                <div class="pt-4 flex justify-end gap-2 text-sm">
                    <button type="button" @click="showModal = false" class="btn btn--secondary">ANNULEREN</button>
                    <button type="submit" class="btn btn--primary">AANMAKEN</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
