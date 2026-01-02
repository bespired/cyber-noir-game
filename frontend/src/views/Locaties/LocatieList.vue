<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';
import Modal from '../../components/Modal.vue';
import ClickButton from '../../components/inputs/ClickButton.vue';
import LinkButton  from '../../components/inputs/LinkButton.vue';
import LocatieThumb from '../../components/thumbs/LocatieThumb.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const locaties = ref([]);
const loading = ref(true);
const showModal = ref(false);
const form = ref({
    naam: '',
    beschrijving: '',
    notities: ''
});

const sectors = ref([]);
const selectedSector = ref('');

onMounted(async () => {
    // Restore filter from sessionStorage
    const savedFilter = sessionStorage.getItem('locatie-sector-filter');
    if (savedFilter) {
        selectedSector.value = savedFilter;
    }

    await fetchSectors();
    await fetchLocaties();
});

watch(selectedSector, (newVal) => {
    sessionStorage.setItem('locatie-sector-filter', newVal);
});

const fetchSectors = async () => {
    try {
        const response = await axios.get('/api/sectoren');
        sectors.value = response.data.sort((a, b) => a.naam.localeCompare(b.naam));
    } catch (e) {
        console.error("Failed to fetch sectors", e);
    }
}

const filteredLocaties = computed(() => {
    if (!selectedSector.value) return locaties.value;
    // Filter locaties that have at least one scene in the selected sector
    return locaties.value.filter(l =>
        l.scenes && l.scenes.some(s => s.sector_id == selectedSector.value)
    );
});

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

const openModal = () => {
    form.value = {
        naam: '',
        beschrijving: '',
        notities: ''
    };
    showModal.value = true;
};

const createLocatie = async () => {
    try {
        await axios.post('/api/locaties', form.value);
        showModal.value = false;
        await fetchLocaties();
    } catch (e) {
        console.error("Failed to create locatie", e);
    }
};
</script>

<template>
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-8">
            <div class="flex items-center gap-4">
                <h1 class="page-header">{{ t('locations.title') }}</h1>
                <div class="flex items-center gap-2">
                    <select v-model="selectedSector" class="form-input text-sm w-auto uppercase">
                        <option value="">{{ t('locations.all_sectors') }}</option>
                        <option v-for="sector in sectors" :key="sector.id" :value="sector.id">
                            {{ sector.naam }}
                        </option>
                    </select>
                    <click-button v-if="selectedSector" icon="✕" buttonType="black" @click="selectedSector = ''" />
                </div>
            </div>
            <div class="flex gap-4">
                <link-button :label="t('locations.order')" icon="⇅" name="locaties-reorder" buttonType="blue" />
                <click-button :label="t('locations.new_location')" icon="+" buttonType="add" @click="openModal"  />
            </div>
        </div>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse font-mono tracking-widest text-lg py-20">
            {{ t('locations.loading') }}
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <LocatieThumb v-for="locatie in filteredLocaties" :key="locatie.id" :locatie="locatie" />
        </div>

        <div v-if="!loading && filteredLocaties.length === 0" class="text-center py-20 border-2 border-dashed border-noir-dark rounded text-noir-muted uppercase tracking-widest bg-noir-darker/30">
            {{ t('locations.no_locations_sector') }}
        </div>

        <!-- Create Modal -->
        <Modal :isOpen="showModal" :title="t('locations.new_entry')" @close="showModal = false">
            <form @submit.prevent="createLocatie" class="space-y-4">
                <div>
                    <label class="form-label">{{ t('locations.name_label') }}</label>
                    <input v-model="form.naam" type="text" required class="form-input" :placeholder="t('locations.placeholder_name')">
                </div>
                <div>
                    <label class="form-label">{{ t('locations.coords_label') }}</label>
                    <textarea v-model="form.beschrijving" required rows="3" class="form-input" :placeholder="t('locations.placeholder_desc')"></textarea>
                </div>
                <div>
                    <label class="form-label">{{ t('locations.intel_label') }}</label>
                    <textarea v-model="form.notities" rows="2" class="form-input" :placeholder="t('locations.placeholder_notes')"></textarea>
                </div>
                <div class="pt-4 flex justify-end gap-3 text-sm">
                    <button type="button" @click="showModal = false" class="btn btn--secondary">{{ t('locations.discard') }}</button>
                    <button type="submit" class="btn btn--warning">{{ t('locations.authorize') }}</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
