<script setup>
import { useStore } from 'vuex';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const store = useStore();
const { t } = useI18n();
const isAuthenticated = computed(() => store.getters['auth/isAuthenticated']);
const user = computed(() => store.getters['auth/user']);

const handleLogout = () => {
    store.dispatch('auth/logout');
};
</script>

<template>
    <header class="bg-noir-dark border-b border-noir-panel px-6 py-3 flex justify-between items-center">
        <!-- Left: Brand/Title -->
        <div class="flex items-center space-x-4">
            <h1 class="text-xl font-bold text-noir-accent tracking-tighter">
                CYBER_NOIR
            </h1>
            <span class="text-xs text-noir-muted hidden md:inline">
                // {{ t('common.header.investigation_system') }}
            </span>
        </div>

        <!-- Right: User Info -->
        <div v-if="isAuthenticated" class="flex items-center space-x-4">
            <div class="text-right hidden sm:block">
                <div class="text-xs text-noir-muted uppercase tracking-wider">{{ t('common.header.operator') }}</div>
                <div class="text-sm text-white font-semibold">{{ user?.name }}</div>
            </div>
            <button 
                @click="handleLogout"
                class="bg-red-900/20 text-red-500 border border-red-900 px-3 py-1.5 rounded hover:bg-red-600 hover:text-white hover:shadow-[0_0_10px_rgba(220,38,38,0.5)] transition-all duration-300 uppercase tracking-wider text-xs font-bold cursor-pointer"
            >
                {{ t('common.header.logout') }}
            </button>
        </div>
    </header>
</template>
