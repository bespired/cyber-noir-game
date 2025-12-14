<script setup>
import { RouterLink } from 'vue-router';
import { useStore } from 'vuex';
import { computed } from 'vue';

const store = useStore();
const isAuthenticated = computed(() => store.getters['auth/isAuthenticated']);

const navItems = [
    { path: '/',             svg: 'dashboard', icon: '📊', label: 'Dashboard',    title: 'DASHBOARD'    },
    { path: '/personages',   svg: 'personage', icon: '👤', label: 'Personages',   title: 'PERSONAGES'   },
    { path: '/locaties',     svg: 'location',  icon: '📍', label: 'Locaties',     title: 'LOCATIES'     },
    { path: '/aanwijzingen', svg: 'clue',      icon: '🔍', label: 'Aanwijzingen', title: 'AANWIJZINGEN' },
    { path: '/scenes',       svg: 'scene',     icon: '🎬', label: 'Scenes',       title: 'SCENES'       },
    { path: '/map',          svg: 'map',       icon: '🗺️', label: 'Map',          title: 'SECTOR MAP'   },
];
</script>

<template>
    <aside v-if="isAuthenticated" class="fixed left-0 top-0 h-screen w-16 bg-noir-dark border-r border-noir-panel flex flex-col items-center py-4 z-50">
        <!-- Logo/Brand -->
        <RouterLink to="/" class="mb-8 text-2xl hover:text-noir-accent transition-colors" title="CYBER_NOIR">
            <span class="text-noir-accent">⚡</span>
        </RouterLink>

        <!-- Navigation Items -->
        <nav class="flex-grow flex flex-col space-y-2">
            <RouterLink
                v-for="item in navItems"
                :key="item.path"
                :to="item.path"
                :title="item.title"
                class="w-12 h-12 flex items-center justify-center text-2xl text-noir-muted hover:text-white hover:bg-noir-panel rounded transition-all duration-200 relative group p-1"
                active-class="text-noir-accent bg-noir-panel"
            >
                <!-- <span>{{ item.icon }}</span> -->
                <img :src="`/icons/${item.svg}.svg`" />
                <!-- Tooltip -->
                <div class="absolute left-full ml-2 px-3 py-1 bg-noir-panel border border-noir-dark rounded text-xs text-white whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-200 font-bold tracking-wider">
                    {{ item.title }}
                </div>
            </RouterLink>
        </nav>

        <!-- Settings/About at bottom -->
        <div class="mt-auto space-y-2">
            <RouterLink
                to="/about"
                title="ABOUT"
                class="w-12 h-12 flex items-center justify-center text-xl text-noir-muted hover:text-white hover:bg-noir-panel rounded transition-all duration-200 relative group"
            >
                <span>ℹ️</span>
                <div class="absolute left-full ml-2 px-3 py-1 bg-noir-panel border border-noir-dark rounded text-xs text-white whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-200 font-bold tracking-wider">
                    ABOUT
                </div>
            </RouterLink>
        </div>
    </aside>
</template>
