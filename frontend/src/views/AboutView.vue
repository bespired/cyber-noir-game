<script setup>
import NexusFont from '../components/NexusFont.vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { useToast } from '../composables/useToast.js';
import Modal from '../components/Modal.vue';
import ClickButton from '../components/inputs/ClickButton.vue';

const { t } = useI18n();
const toast = useToast();

const processingSystem = ref(false); // New loading state for system actions
const showSystemModal = ref(false); // Modal for backup/install confirmation
const systemActionType = ref(null); // 'backup' or 'install'

const openSystemModal = (type) => {
    systemActionType.value = type;
    showSystemModal.value = true;
};

const confirmSystemAction = async () => {
    showSystemModal.value = false;
    processingSystem.value = true;

    const endpoint = systemActionType.value === 'backup'
        ? '/api/game/artwork-backup'
        : '/api/game/artwork-install';

    try {
        const response = await axios.post(endpoint);
        if (response.data.success) {
            toast.success(systemActionType.value === 'backup' ? t('dashboard.backup_success') : t('dashboard.install_success'));
        } else {
            toast.error(t('dashboard.action_failed'));
        }
    } catch (e) {
        console.error("System action failed", e);
        toast.error(t('dashboard.action_failed') + " " + (e.response?.data?.message || e.message));
    } finally {
        processingSystem.value = false;
        systemActionType.value = null;
    }
};
</script>

<template>

	<div class="about">
		<h1>CYBER_NOIR</h1>
		<h2>// INVESTIGATION_SYSTEM</h2>
		<p>
            {{ t('dashboard.trying') }}
			<!-- Trying to create an editor for a point and click game. -->
		</p>
		<br />
		<nexus-font type="slant" label="Nexus Runners" />

		<p>{{ t('dashboard.of_the') }}</p>
    	<nexus-font type="blocky" label="NEO TOKYO POLICE DEP" />

        <!-- System Maintenance Panel -->
        <div class="w-full max-w-4xl mt-12 px-6">
            <div class="bg-noir-panel border border-noir-dark p-6 rounded mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-white">{{ t('dashboard.system_maintenance') }}</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Backup -->
                    <div class="border border-noir-dark p-4 rounded bg-black/20">
                        <h3 class="text-lg font-semibold text-noir-success mb-2">{{ t('dashboard.backup_title') }}</h3>
                        <p class="text-sm text-noir-muted mb-4">{{ t('dashboard.backup_desc') }}</p>
                        <click-button
                            @click="openSystemModal('backup')"
                            :disabled="processingSystem"
                            buttonType="green"
                            :label="t('dashboard.btn_backup')"
                        />
                    </div>

                    <!-- Install -->
                    <div class="border border-noir-dark p-4 rounded bg-black/20">
                        <h3 class="text-lg font-semibold text-noir-warning mb-2">{{ t('dashboard.install_title') }}</h3>
                        <p class="text-sm text-noir-muted mb-4">{{ t('dashboard.install_desc') }}</p>
                        <click-button
                            @click="openSystemModal('install')"
                            :disabled="processingSystem"
                            buttonType="warning"
                            :label="t('dashboard.btn_install')"
                        />
                    </div>
                </div>

                <!-- Status Overlay for System Actions -->
                <div v-if="processingSystem" class="mt-4 p-4 bg-noir-accent/10 border border-noir-accent rounded flex items-center justify-center animate-pulse">
                        <span class="text-noir-accent font-bold">{{ t('dashboard.executing') }}</span>
                </div>
            </div>
        </div>


	</div>

    <!-- System Modal -->
    <Modal
        :isOpen="showSystemModal"
        :title="t('dashboard.action_confirm')"
        :okLabel="t('dashboard.confirm_export_btn')"
        :cancelLabel="t('dashboard.cancel_export')"
        @ok="confirmSystemAction"
        @close="showSystemModal = false"
    >
        <div class="space-y-4">
            <p>{{ t('dashboard.action_desc') }}</p>
        </div>
    </Modal>
</template>

<style>
	.about {
		min-height: 100vh;
		display: flex;
		align-items: center;
		flex-direction: column;
        padding-top: 4rem;
	}
.about h1 {
	font-size: 3em;
}
</style>
