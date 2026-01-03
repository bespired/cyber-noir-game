<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';
import Modal from '../../components/Modal.vue';
import ClickButton from '../../components/inputs/ClickButton.vue';
import PersonageThumb from '../../components/thumbs/PersonageThumb.vue';
import { useI18n } from 'vue-i18n';
import HeaderBar from '../../components/bars/HeaderBar.vue';

const { t } = useI18n();

const props = defineProps({
    type: {
        type: String,
        default: 'persoon'
    }
});

const personages = ref([]);
const loading    = ref(true);
const showModal  = ref(false);

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

const fetchPersonages = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/personages');
        // Filter client-side if the API returns all, or rely on API if it filters?
        // Assuming API returns all, we filter here for display if needed,
        // OR we just show all if the backend doesn't support filtering.
        // Given 'type' prop usage, let's filter if 'type' is strict?
        // Actually, PersonageThumb handles styling based on type.
        // Let's assume we show what the backend gives us, but maybe filter?
        personages.value = response.data;
    } catch (e) {
        console.error("Failed to fetch personages", e);
    } finally {
        loading.value = false;
    }
};

const filteredPersonages = computed(() => {
    return personages.value.filter(p => p.type === props.type);
});

const headerLabel = computed(() => {
    return props.type === 'voertuig' ? t('personages.vehicles') : t('personages.characters')
});

const addLabel = computed(() => {
    return props.type === 'voertuig' ? t('personages.new_vehicle') : t('personages.new_character')
});

const openModal = () => {
    form.value = {
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
        await fetchPersonages();
    } catch (e) {
        console.error("Failed to create personage", e);
    }
};



onMounted(() => {
    fetchPersonages();
});
</script>

<template>
    <div class="container mx-auto p-6">
        <header-bar
            :label="headerLabel">
            <template #actions>
                <click-button :label="addLabel" icon="+" buttonType="add" @click="openModal" />
            </template>
        </header-bar>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            {{ t('common.loading') }}
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <PersonageThumb
                v-for="personage in filteredPersonages"
                :key="personage.id"
                :personage="personage"
                :type="props.type"
            />
        </div>

        <Modal 
            :isOpen="showModal" 
            :title="props.type === 'voertuig' ? t('personages.new_vehicle') : t('personages.new_character')" 
            :okLabel="t('personages.create')"
            @close="showModal = false"
            @ok="createPersonage"
        >
            <form @submit.prevent="createPersonage" class="space-y-4">
                <div>
                    <label class="form-label">{{ t('personages.name') }}</label>
                    <input v-model="form.naam" type="text" required class="form-input">
                </div>
                <div>
                    <label class="form-label">{{ t('personages.role') }}</label>
                    <input v-model="form.rol" type="text" required class="form-input">
                </div>
                <div>
                    <label class="form-label">{{ t('personages.description') }}</label>
                    <textarea v-model="form.beschrijving" required rows="3" class="form-input"></textarea>
                </div>
                <!-- Logic specific to Person (not vehicle) -->
                <div class="grid grid-cols-2 gap-4" v-if="props.type === 'persoon'">
                     <div>
                        <label class="form-label">{{ t('personages.human_status') }}</label>
                        <input v-model="form.menselijke_status" type="text" class="form-input">
                    </div>
                     <div>
                        <label class="form-label">{{ t('personages.replicant_status') }}</label>
                        <input v-model="form.replicant_status" type="text" class="form-input">
                    </div>
                </div>
                 <div>
                    <label class="form-label">{{ t('personages.motive') }}</label>
                    <textarea v-model="form.motief" rows="2" class="form-input"></textarea>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2" v-if="props.type === 'persoon'">
                        <input v-model="form.is_replicant" type="checkbox" id="is_replicant" class="rounded bg-noir-darker border-noir-dark text-noir-accent focus:ring-noir-accent">
                        <label for="is_replicant" class="text-white text-sm">{{ t('personages.is_replicant') }}</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="form.is_playable" type="checkbox" id="is_playable" class="rounded bg-noir-darker border-noir-dark text-noir-warning focus:ring-noir-warning">
                        <label for="is_playable" class="text-white text-sm">{{ props.type === 'voertuig' ? t('personages.playable_vehicle') : t('personages.playable_character') }}</label>
                    </div>
                </div>
            </form>
        </Modal>
    </div>
</template>
