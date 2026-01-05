<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios       from '../../axios';
import { RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';
import Modal       from '../../components/Modal.vue';
import ClickButton from '../../components/inputs/ClickButton.vue';
import SceneThumb  from '../../components/thumbs/SceneThumb.vue';
import HeaderBar   from '../../components/bars/HeaderBar.vue';
import OptionList  from '../../components/inputs/OptionList.vue';

const { t } = useI18n();
const scenes = ref([]);
const locaties = ref([]);
const loading = ref(true);
const showModal = ref(false);
const form = ref({
    titel: '',
    locatie_id: '',
    sector_id: '',
    type: 'walkable-area',
    beschrijving: '',
    entry_point: '',
    exit_point: '',
    status: 'active'
});

const sectors = ref([]);
const selectedSector = ref('');
const selectedType = ref('');

const types = [
    { value: 'walkable-area', label: 'Walkable Area' },
    { value: 'investigation', label: 'Investigation' },
    { value: 'interrogation', label: 'Interrogation' },
    { value: 'combat',        label: 'Combat' },
    { value: 'practice',      label: 'Practice' },
    { value: 'vue-component', label: 'Custom Vue Component' }
];

onMounted(async () => {
    // Restore filters from sessionStorage
    const savedSectorFilter = sessionStorage.getItem('scene-sector-filter');
    if (savedSectorFilter) selectedSector.value = savedSectorFilter;

    const savedTypeFilter = sessionStorage.getItem('scene-type-filter');
    if (savedTypeFilter) selectedType.value = savedTypeFilter;

    await Promise.all([
        fetchScenes(),
        fetchLocaties(),
        fetchSectors()
    ]);
});

watch(selectedSector, (newVal) => sessionStorage.setItem('scene-sector-filter', newVal));
watch(selectedType, (newVal) => sessionStorage.setItem('scene-type-filter', newVal));

const fetchSectors = async () => {
    try {
        const response = await axios.get('/api/sectoren');
        sectors.value = response.data.sort((a, b) => a.naam.localeCompare(b.naam));
    } catch (e) {
        console.error("Failed to fetch sectors", e);
    }
}

const filteredScenes = computed(() => {
    return scenes.value.filter(s => {
        const matchSector = !selectedSector.value || s.sector_id == selectedSector.value;
        const matchType = !selectedType.value || s.type === selectedType.value;
        return matchSector && matchType;
    });
});

const fetchScenes = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/scenes');
        scenes.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const fetchLocaties = async () => {
    try {
        const response = await axios.get('/api/locaties');
        locaties.value = response.data;
    } catch (e) {
        console.error("Failed to fetch locations", e);
    }
};

const openModal = () => {
    form.value = {
        titel: '',
        locatie_id: '',
        sector_id: selectedSector.value || '',
        type: 'walkable-area',
        beschrijving: '',
        entry_point: '',
        exit_point: '',
        status: 'active'
    };
    showModal.value = true;
};

const createScene = async () => {
    try {
        await axios.post('/api/scenes', form.value);
        showModal.value = false;
        await fetchScenes();
    } catch (e) {
        console.error("Failed to create scene", e);
    }
};
</script>

<template>
    <div class="container mx-auto p-6">
        <header-bar :label="t('scenes.title')">
            <template #filters>
                <option-list
                    v-model="selectedSector"
                    :options="sectors"
                    :placeholder="t('scenes.all_sectors')"
                />
                <option-list
                    v-model="selectedType"
                    :options="types"
                    :placeholder="t('scenes.all_types')"
                />
            </template>
            <template #actions>
                <click-button :label="t('scenes.new_scene')" icon="+" buttonType="add" @click="openModal" />
            </template>
        </header-bar>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            {{ t('scenes.loading_data') }}
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <SceneThumb v-for="scene in filteredScenes" :key="scene.id" :scene="scene" />
        </div>

        <!-- Create Modal -->
        <Modal :isOpen="showModal" :title="t('scenes.new_entry')" :okLabel="t('scenes.create_record')" @close="showModal = false" @ok="createScene">
            <form @submit.prevent="createScene" class="space-y-4">
                <div>
                    <label class="form-label">{{ t('scenes.form_title') }}</label>
                    <input v-model="form.titel" type="text" required class="form-input">
                </div>

                <div class="grid grid-cols-2 gap-4">
                     <div>
                        <label class="form-label">{{ t('scenes.form_type') }}</label>
                        <select v-model="form.type" required class="form-input">
                            <option v-for="t in types" :key="t.value" :value="t.value">
                                {{ t.label }}
                            </option>
                        </select>
                    </div>
                     <div>
                        <label class="form-label">{{ t('scenes.form_status') }}</label>
                        <select v-model="form.status" required class="form-input">
                            <option value="active">Active</option>
                            <option value="entry">Entry</option>
                            <option value="locked">Locked</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4" v-if="['outside', 'inside'].includes(form.type)">
                    <div>
                        <label class="form-label">{{ t('scenes.form_sector') }}</label>
                        <select v-model="form.sector_id" required class="form-input">
                            <option value="" disabled>{{ t('scenes.select_sector') }}</option>
                            <option v-for="sector in sectors" :key="sector.id" :value="sector.id">{{ sector.naam }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">{{ t('scenes.form_location') }}</label>
                        <select v-model="form.locatie_id" required class="form-input">
                            <option value="" disabled>{{ t('scenes.select_location') }}</option>
                            <option v-for="loc in locaties" :key="loc.id" :value="loc.id">{{ loc.naam }}</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="form-label">{{ t('scenes.description') }}</label>
                    <textarea v-model="form.beschrijving" required rows="3" class="form-input"></textarea>
                </div>
            </form>
        </Modal>
    </div>
</template>
