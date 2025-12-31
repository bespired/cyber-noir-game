<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';
import Modal from '../../components/Modal.vue';
import ClickButton from '../../components/inputs/ClickButton.vue';
import PersonageThumb from '../../components/thumbs/PersonageThumb.vue';

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
            <PersonageThumb
                v-for="personage in personages"
                :key="personage.id"
                :personage="personage"
                :type="props.type"
            />
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
