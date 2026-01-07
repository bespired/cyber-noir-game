<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useStore } from 'vuex';
import { RouterLink } from 'vue-router';
import axios from '../axios';
import Modal from '../components/Modal.vue';
import ClickButton from '../components/inputs/ClickButton.vue';
import { useToast } from '../composables/useToast.js';
import { useI18n } from 'vue-i18n';

const store = useStore();
const toast = useToast();
const { t, locale } = useI18n();
const isAuthenticated = computed(() => store.getters['auth/isAuthenticated']);

const stats = ref({
    personages: 0,
    locaties: 0,
    aanwijzingen: 0,
    scenes: 0,
    sectoren: 0,
    dialogen: 0,
    notities: 0,
    scene_personages: 0,
    aanwijzingen: 0,
    gedragingen: 0,
    instellingen: 0,
    afbeeldingen: 0
});

const loading = ref(true);
const exporting = ref(false);
const showExportModal = ref(false);

const openExportModal = () => {
    showExportModal.value = true;
};

const confirmExport = async () => {
    showExportModal.value = false;
    exporting.value = true;

    try {
        const response = await axios.post('/api/game/export');
        toast.success(response.data.message || t('dashboard.export_success'));
    } catch (e) {
        console.error("Export failed", e);
        toast.error(t('dashboard.export_failed'));
    } finally {
        exporting.value = false;
    }
};

const fetchStats = async () => {
    if (!isAuthenticated.value) return;

    try {
        const [pRes, lRes, aRes, sRes, secRes, dRes, nRes, spRes, gRes, iRes, afbRes] = await Promise.all([
            axios.get('/api/personages'),
            axios.get('/api/locaties'),
            axios.get('/api/aanwijzingen'),
            axios.get('/api/scenes'),
            axios.get('/api/sectoren'),
            axios.get('/api/dialogen'),
            axios.get('/api/notities'),
            axios.get('/api/scene-personages'),
            axios.get('/api/gedrag'),
            axios.get('/api/instellingen'),
            axios.get('/api/afbeeldingen')
        ]);
        stats.value = {
            personages: pRes.data.length,
            locaties: lRes.data.length,
            aanwijzingen: aRes.data.length,
            scenes: sRes.data.length,
            sectoren: secRes.data.length,
            dialogen: dRes.data.length,
            notities: nRes.data.length,
            scene_personages: spRes.data.length,
            gedragingen: gRes.data.length,
            instellingen: iRes.data.length,
            afbeeldingen: afbRes.data.length
        };
    } catch (e) {
        console.error("Failed to load stats", e);
        toast.error(t('dashboard.stats_failed'));
    } finally {
        loading.value = false;
    }
};

watch(isAuthenticated, (newVal) => {
    if (newVal) {
        fetchStats();
    }
}, { immediate: true });

watch(locale, (newLocale) => {
    localStorage.setItem('user-locale', newLocale);
});
</script>

<template>
    <div class="container mx-auto p-6">
        <div v-if="isAuthenticated">
            <div class="flex justify-between items-center mb-8">
                <h1 class="page-header">
                    {{ t('dashboard.case_status') }}:
                    <span class="text-noir-success">
                        {{ t('player_selection.active') }}
                    </span>
                </h1>

                <div class="flex items-center">
                    <span class="text-noir-muted mr-2 text-xs">LANG:</span>
                    <select v-model="locale" class="bg-noir-panel text-white border border-noir-dark rounded px-2 py-1 text-sm focus:border-noir-accent outline-none">
                        <option value="en">EN</option>
                        <option value="nl">NL</option>
                    </select>
                </div>
            </div>

            <!-- Main Entities -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <!-- Stat Card 1 -->
                <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-noir-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="text-noir-muted uppercase text-sm font-semibold mb-2">{{ t('common.sidebar.characters') }}</h3>
                    <p class="text-4xl font-bold text-white">{{ stats.personages }}</p>
                    <div class="mt-4 h-1 w-full bg-noir-dark rounded-full overflow-hidden">
                        <div class="h-full bg-noir-accent w-2/3"></div>
                    </div>
                </div>

                <!-- Stat Card 2 -->
                <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-noir-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="text-noir-muted uppercase text-sm font-semibold mb-2">{{ t('common.sidebar.locations') }}</h3>
                    <p class="text-4xl font-bold text-white">{{ stats.locaties }}</p>
                    <div class="mt-4 h-1 w-full bg-noir-dark rounded-full overflow-hidden">
                        <div class="h-full bg-noir-warning w-1/2"></div>
                    </div>
                </div>

                <!-- Stat Card 3 -->
                <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-noir-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <h3 class="text-noir-muted uppercase text-sm font-semibold mb-2">{{ t('common.sidebar.clues') }}</h3>
                    <p class="text-4xl font-bold text-white">{{ stats.aanwijzingen }}</p>
                    <div class="mt-4 h-1 w-full bg-noir-dark rounded-full overflow-hidden">
                        <div class="h-full bg-noir-danger w-3/4"></div>
                    </div>
                </div>
            </div>

            <!-- Secondary Entities -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
                <!-- Scenes -->
                <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg relative overflow-hidden group">
                    <h3 class="text-noir-muted uppercase text-sm font-semibold mb-2">{{ t('common.sidebar.scenes') }}</h3>
                    <p class="text-3xl font-bold text-white">{{ stats.scenes }}</p>
                </div>

                <!-- Sectors -->
                <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg relative overflow-hidden group">
                    <h3 class="text-noir-muted uppercase text-sm font-semibold mb-2">{{ t('dashboard.sectors') }}</h3>
                    <p class="text-3xl font-bold text-white">{{ stats.sectoren }}</p>
                </div>

                <!-- Dialogen -->
                <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg relative overflow-hidden group">
                    <h3 class="text-noir-muted uppercase text-sm font-semibold mb-2">{{ t('common.sidebar.dialogues') }}</h3>
                    <p class="text-3xl font-bold text-white">{{ stats.dialogen }}</p>
                </div>

                <!-- Notities -->
                <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg relative overflow-hidden group">
                    <h3 class="text-noir-muted uppercase text-sm font-semibold mb-2">{{ t('common.sidebar.notes') }}</h3>
                    <p class="text-3xl font-bold text-white">{{ stats.notities }}</p>
                </div>
            </div>

            <!-- Ternary Entities (New additions) -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
                <!-- Scene Personages -->
                <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg relative overflow-hidden group">
                    <h3 class="text-noir-muted uppercase text-sm font-semibold mb-2">{{ t('dashboard.scene_personages') }}</h3>
                    <p class="text-3xl font-bold text-white text-opacity-80">{{ stats.scene_personages }}</p>
                </div>

                <!-- Behaviors -->
                <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg relative overflow-hidden group">
                    <h3 class="text-noir-muted uppercase text-sm font-semibold mb-2">{{ t('dashboard.behaviors') }}</h3>
                    <p class="text-3xl font-bold text-white text-opacity-80">{{ stats.gedragingen }}</p>
                </div>

                 <!-- Settings -->
                 <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg relative overflow-hidden group">
                    <h3 class="text-noir-muted uppercase text-sm font-semibold mb-2">{{ t('dashboard.settings') }}</h3>
                    <p class="text-3xl font-bold text-white text-opacity-80">{{ stats.instellingen }}</p>
                </div>
                
                 <!-- Artwork -->
                 <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg relative overflow-hidden group">
                    <h3 class="text-noir-muted uppercase text-sm font-semibold mb-2">{{ t('dashboard.artwork') }}</h3>
                    <p class="text-3xl font-bold text-white text-opacity-80">{{ stats.afbeeldingen }}</p>
                </div>
            </div>

            <div class="bg-noir-panel border border-noir-dark p-6 rounded mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold text-white mb-2 pb-2">{{ t('dashboard.game_goal') }}</h2>
                        <p class="text-noir-text text-lg">
                            {{ t('dashboard.game_goal_text') }}
                            <span class="animate-pulse inline-block w-2 h-4 bg-noir-accent ml-1 align-middle"></span>
                        </p>
                    </div>
                    <div>
                        <click-button
                            @click="openExportModal"
                            :disabled="exporting"
                            buttonType="blue"
                            :label="`${!exporting ? t('dashboard.export_to_electron') : t('dashboard.exporting')}`"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="flex flex-col items-center justify-center min-h-[60vh] text-center">
            <div class="bg-noir-panel border border-noir-dark p-8 rounded-lg shadow-2xl max-w-md w-full">
                <h1 class="text-3xl font-bold text-white mb-4">{{ t('dashboard.reference_title') }}</h1>
                <p class="text-noir-muted mb-8">
                    {{ t('dashboard.access_restricted') }}
                </p>
                <RouterLink :to="{ name: 'login' }" class="inline-block px-6 py-3 bg-noir-accent hover:bg-opacity-80 text-white font-bold rounded transition-colors duration-200">
                    {{ t('dashboard.login_terminal') }}
                </RouterLink>
            </div>
        </div>

        <!-- Export Modal -->
        <Modal 
            :isOpen="showExportModal" 
            :title="t('dashboard.confirm_export')" 
            :okLabel="t('dashboard.confirm_export_btn')"
            :cancelLabel="t('dashboard.cancel_export')"
            @ok="confirmExport"
            @close="showExportModal = false"
        >
            <div class="space-y-4">
                <div class="bg-noir-warning/10 border border-noir-warning p-4 rounded text-noir-warning flex items-start gap-4">
                    <span class="text-2xl">⚠️</span>
                    <div>
                        <p class="font-bold">{{ t('dashboard.warning_overwrite') }}</p>
                        <p class="text-sm mt-1">{{ t('dashboard.overwrite_desc') }}</p>
                        <p class="text-sm mt-2"><strong>{{ t('dashboard.target') }}:</strong> /electron/public/data</p>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>

