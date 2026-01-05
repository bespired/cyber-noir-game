<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from '../../axios';
import { useI18n } from 'vue-i18n';
import Modal from '../../components/Modal.vue';
import ClickButton  from '../../components/inputs/ClickButton.vue';
import LinkButton   from '../../components/inputs/LinkButton.vue';
import LocatieThumb from '../../components/thumbs/LocatieThumb.vue';
import HeaderBar from '../../components/bars/HeaderBar.vue';
import OptionList from '../../components/inputs/OptionList.vue';

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
        <header-bar :label="t('locations.title')">
            <template #filters>
                <option-list
                    v-model="selectedSector"
                    :options="sectors"
                    :placeholder="t('locations.all_sectors')"
                />
            </template>
            <template #actions>
                <link-button  :label="t('locations.order')" icon="⇅" name="locaties-reorder" buttonType="blue" />
                <click-button :label="t('locations.new_location')" icon="+" buttonType="add" @click="openModal"  />
            </template>
        </header-bar>

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
        <Modal 
            :isOpen="showModal" 
            :title="t('locations.new_entry')" 
            :okLabel="t('locations.authorize')"
            @close="showModal = false"
            @ok="createLocatie"
        >
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
            </form>
        </Modal>
    </div>
</template>
