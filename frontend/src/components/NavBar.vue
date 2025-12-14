<script setup>
import { RouterLink } from 'vue-router';
import { useStore } from 'vuex';
import { computed } from 'vue';

const store = useStore();
const isAuthenticated = computed(() => store.getters['auth/isAuthenticated']);
const user = computed(() => store.getters['auth/user']);

const handleLogout = () => {
    store.dispatch('auth/logout');
};
</script>

<template>
    <nav class="bg-noir-dark border-b border-noir-panel p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-8">
                <RouterLink to="/" class="text-2xl font-bold text-noir-accent tracking-tighter hover:text-white transition-colors">
                    CYBER_NOIR
                </RouterLink>

                <div v-if="isAuthenticated" class="hidden md:flex space-x-6">
                    <RouterLink to="/" class="text-noir-muted hover:text-white transition-colors py-1 uppercase tracking-wider text-sm font-semibold" active-class="text-noir-accent border-b-2 border-noir-accent">
                        DASHBOARD
                    </RouterLink>
                    <RouterLink to="/personages" class="text-noir-muted hover:text-white transition-colors py-1 uppercase tracking-wider text-sm font-semibold" active-class="text-noir-accent border-b-2 border-noir-accent">
                        PERSONAGES
                    </RouterLink>
                    <RouterLink to="/locaties" class="text-noir-muted hover:text-white transition-colors py-1 uppercase tracking-wider text-sm font-semibold" active-class="text-noir-accent border-b-2 border-noir-accent">
                        LOCATIES
                    </RouterLink>
                    <RouterLink to="/aanwijzingen" class="text-noir-muted hover:text-white transition-colors py-1 uppercase tracking-wider text-sm font-semibold" active-class="text-noir-accent border-b-2 border-noir-accent">
                        AANWIJZINGEN
                    </RouterLink>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <template v-if="isAuthenticated">
                    <span class="text-sm text-noir-muted hidden sm:inline">
                        OP: {{ user?.name }}
                    </span>
                    <button @click="handleLogout" class="bg-red-900/20 text-red-500 border border-red-900 px-3 py-1 rounded hover:bg-red-600 hover:text-white hover:shadow-[0_0_10px_rgba(220,38,38,0.5)] transition-all duration-300 uppercase tracking-wider text-sm cursor-pointer">
                        LOGOUT
                    </button>
                </template>
                <template v-else>
                    <RouterLink to="/login" class="text-noir-muted hover:text-white">
                        LOGIN
                    </RouterLink>
                </template>
            </div>
        </div>
    </nav>
</template>

<style scoped>
/* Scoped styles removed in favor of inline Tailwind classes */
</style>
