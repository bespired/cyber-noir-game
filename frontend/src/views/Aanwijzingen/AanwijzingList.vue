<script setup>
import { ref, onMounted } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';
import Modal from '../../components/Modal.vue';
import ClickButton from '../../components/inputs/ClickButton.vue';
import AanwijzingThumb from '../../components/thumbs/AanwijzingThumb.vue';
import { useI18n } from 'vue-i18n';
import HeaderBar from '../../components/bars/HeaderBar.vue';

const { t } = useI18n();
const aanwijzingen = ref([]);
const loading = ref(true);
const showModal = ref(false);
const form = ref({
    titel: '',
    beschrijving: '',
    type: 'image',
    data: {},
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
        type: 'image',
        data: {},
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
        <header-bar :label="t('clues.title')">
            <template #actions>
                <click-button :label="t('clues.add_clue')" icon="+" buttonType="add" @click="openModal" />
            </template>
        </header-bar>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            {{ t('clues.decrypting') }}
        </div>

        <div v-else class="panel overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-noir-dark text-noir-muted uppercase text-xs tracking-wider border-b border-noir-panel">
                        <th class="p-4 font-bold">{{ t('clues.visual') }}</th>
                        <th class="p-4 font-bold">{{ t('clues.status') }}</th>
                        <th class="p-4 font-bold">{{ t('clues.title') }}</th>
                        <th class="p-4 font-bold">{{ t('clues.linked_person') }}</th>
                        <th class="p-4 font-bold">{{ t('clues.location') }}</th>
                        <th class="p-4 font-bold text-right">{{ t('clues.action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-noir-dark">
                    <AanwijzingThumb v-for="clue in aanwijzingen" :key="clue.id" :clue="clue" />
                </tbody>
            </table>
        </div>

        <!-- Create Modal -->
        <Modal 
            :isOpen="showModal" 
            :title="t('clues.log_new')" 
            :okLabel="t('clues.log')"
            okButtonType="red"
            @close="showModal = false"
            @ok="createAanwijzing"
        >
            <form @submit.prevent="createAanwijzing" class="space-y-4">
                <div>
                    <label class="form-label">{{ t('clues.title') }}</label>
                    <input v-model="form.titel" type="text" required class="form-input">
                </div>
                <div>
                    <label class="form-label">{{ t('clues.type') }}</label>
                    <select v-model="form.type" required class="form-input">
                        <option value="image">{{ t('clues.type_image') }}</option>
                        <option value="object">{{ t('clues.type_object') }}</option>
                        <option value="gamestate">{{ t('clues.type_gamestate') }}</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">{{ t('clues.description') }}</label>
                    <textarea v-model="form.beschrijving" required rows="3" class="form-input"></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input v-model="form.is_kritisch" type="checkbox" id="is_kritisch" class="rounded bg-noir-darker/50 border-noir-dark text-noir-danger focus:ring-noir-danger">
                    <label for="is_kritisch" class="text-white text-sm uppercase">{{ t('clues.critical_question') }}</label>
                </div>
            </form>
        </Modal>
    </div>
</template>
