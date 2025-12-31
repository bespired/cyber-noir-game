<script setup>
import { ref, onMounted } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';
import Modal from '../../components/Modal.vue';
import ClickButton from '../../components/inputs/ClickButton.vue';
import AanwijzingThumb from '../../components/thumbs/AanwijzingThumb.vue';

const aanwijzingen = ref([]);
const loading = ref(true);
const showModal = ref(false);
const form = ref({
    titel: '',
    beschrijving: '',
    is_kritisch: false
});

onMounted(async () => {
    await fetchAanwijzingen();
});

const fetchAanwijzingen = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/aanwijzingen');
        aanwijzingen.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

const openModal = () => {
    form.value = {
        titel: '',
        beschrijving: '',
        is_kritisch: false
    };
    showModal.value = true;
};

const createAanwijzing = async () => {
    try {
        await axios.post('/api/aanwijzingen', form.value);
        showModal.value = false;
        await fetchAanwijzingen();
    } catch (e) {
        console.error("Failed to create aanwijzing", e);
    }
};
</script>

<template>
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-8">
            <h1 class="page-header">AANWIJZINGEN</h1>
            <click-button label="AANWIJZING TOEVOEGEN" icon="+" buttonType="add" @click="openModal" />
        </div>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            BESTANDEN ONTSLEUTELEN...
        </div>

        <div v-else class="panel overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-noir-dark text-noir-muted uppercase text-xs tracking-wider border-b border-noir-panel">
                        <th class="p-4 font-bold">Visueel</th>
                        <th class="p-4 font-bold">Status</th>
                        <th class="p-4 font-bold">Titel</th>
                        <th class="p-4 font-bold">Gelinkt Persoon</th>
                        <th class="p-4 font-bold">Locatie</th>
                        <th class="p-4 font-bold text-right">Actie</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-noir-dark">
                    <AanwijzingThumb v-for="clue in aanwijzingen" :key="clue.id" :clue="clue" />
                </tbody>
            </table>
        </div>

        <!-- Create Modal -->
        <Modal :isOpen="showModal" title="LOG NIEUWE AANWIJZING" @close="showModal = false">
            <form @submit.prevent="createAanwijzing" class="space-y-4">
                <div>
                    <label class="form-label">Titel</label>
                    <input v-model="form.titel" type="text" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Beschrijving</label>
                    <textarea v-model="form.beschrijving" required rows="3" class="form-input"></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input v-model="form.is_kritisch" type="checkbox" id="is_kritisch" class="rounded bg-noir-darker border-noir-dark text-noir-danger focus:ring-noir-danger">
                    <label for="is_kritisch" class="text-white text-sm uppercase">Kritieke Aanwijzing?</label>
                </div>
                <div class="pt-4 flex justify-end gap-2 text-sm">
                    <button type="button" @click="showModal = false" class="btn btn--secondary">ANNULEREN</button>
                    <button type="submit" class="btn btn--danger">LOG AANWIJZING</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
