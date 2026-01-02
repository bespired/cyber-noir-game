<script setup>
import { ref, onMounted } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';
import Modal from '../../components/Modal.vue';
import SectorThumb from '../../components/thumbs/SectorThumb.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const sectors = ref([]);
const loading = ref(true);
const showModal = ref(false);
const form = ref({
    naam: '',
    beschrijving: '',
    kaart_coordinaten: '',
    is_ontdekt: false
});

onMounted(async () => {
    await fetchSectors();
});

const fetchSectors = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/sectoren');
        sectors.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

const openModal = () => {
    form.value = {
        naam: '',
        beschrijving: '',
        kaart_coordinaten: '',
        is_ontdekt: false
    };
    showModal.value = true;
};

const createSector = async () => {
    try {
        await axios.post('/api/sectoren', form.value);
        showModal.value = false;
        await fetchSectors();
    } catch (e) {
        console.error("Failed to create sector", e);
    }
};
</script>

<template>
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white tracking-tight">{{ t('map.sector_map') }}</h1>
            <button @click="openModal" class="bg-noir-accent text-white px-4 py-2 rounded hover:bg-blue-500 hover:shadow-[0_0_15px_rgba(59,130,246,0.5)] transition-all duration-300 uppercase font-bold text-sm tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                {{ t('map.new_sector') }}
            </button>
        </div>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            {{ t('map.loading_map') }}
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <SectorThumb v-for="sector in sectors" :key="sector.id" :sector="sector" />
        </div>

        <!-- Create Modal -->
        <Modal :isOpen="showModal" :title="t('map.new_entry')" @close="showModal = false">
            <form @submit.prevent="createSector" class="space-y-4">
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">{{ t('map.name') }}</label>
                    <input v-model="form.naam" type="text" required class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                </div>
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">{{ t('map.description') }}</label>
                    <textarea v-model="form.beschrijving" required rows="3" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none"></textarea>
                </div>
                 <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">{{ t('map.coords') }}</label>
                    <input v-model="form.kaart_coordinaten" type="text" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none" placeholder="X-Y-Z">
                </div>
                <div class="flex items-center gap-2">
                    <input v-model="form.is_ontdekt" type="checkbox" id="is_ontdekt" class="rounded bg-noir-darker border-noir-dark text-noir-accent focus:ring-noir-accent">
                    <label for="is_ontdekt" class="text-white text-sm">{{ t('map.is_discovered') }}</label>
                </div>
                <div class="pt-4 flex justify-end gap-2 text-sm">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-noir-muted hover:text-white transition-colors">{{ t('map.cancel') }}</button>
                    <button type="submit" class="px-4 py-2 bg-noir-accent text-white rounded hover:bg-blue-600 transition-colors">{{ t('map.create_record') }}</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
