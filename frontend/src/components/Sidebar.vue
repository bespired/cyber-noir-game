<script setup>
import { RouterLink } from 'vue-router';
import { useStore } from 'vuex';
import { computed } from 'vue';

const store = useStore();
const isAuthenticated = computed(() => store.getters['auth/isAuthenticated']);

const navItems = [
    { path: '/',             svg: 'dashboard', icon: '📊', label: 'Dashboard',    title: 'DASHBOARD'    },
    { path: '/personages',   svg: 'personage', icon: '👤', label: 'Personages',   title: 'PERSONAGES'   },
    { path: '/voertuigen',   svg: 'vehicle',   icon: '🚗', label: 'Voertuigen',   title: 'VOERTUIGEN'   },
    { path: '/locaties',     svg: 'location',  icon: '📍', label: 'Locaties',     title: 'LOCATIES'     },
    { path: '/gedrag',       svg: 'behavior',  icon: '🧠', label: 'Gedrag',      title: 'GEDRAG'      },
    { path: '/aanwijzingen', svg: 'clue',      icon: '🔍', label: 'Aanwijzingen', title: 'AANWIJZINGEN' },
    { path: '/scenes',       svg: 'scene',     icon: '🎬', label: 'Scenes',       title: 'SCENES'       },
    { path: '/map',          svg: 'map',       icon: '🗺️', label: 'Map',          title: 'SECTOR MAP'   },
    { path: '/dialogen',     svg: 'dialogue',  icon: '💬', label: 'Dialogen',     title: 'DIALOGEN'     },
    { path: '/notities',     svg: 'note',      icon: '📝', label: 'Notities',     title: 'MYN NOTITIES' },
    { path: '/help',         svg: 'help',      icon: '?',  label: 'Help',         title: 'HELP'         },
    { path: '/about',        svg: 'about',     icon: 'i',  label: 'About',        title: 'ABOUT'        },
];
</script>

<template>
    <aside
        v-if="isAuthenticated" class="fixed left-0 top-0 h-screen w-16 bg-noir-dark border-r border-noir-panel flex flex-col items-center py-4 z-50">
        <!-- Logo/Brand -->
        <RouterLink to="/" class="mb-8 text-2xl hover:text-noir-accent transition-colors" title="CYBER_NOIR">
            <span class="text-noir-accent">⚡</span>
        </RouterLink>

        <!-- Navigation Items -->
        <nav class="flex-grow flex flex-col space-y-2" >
            <RouterLink
                v-for="item in navItems"
                :key="item.path"
                :to="item.path"
                :title="item.title"
                :class="{ 'mt-auto': item.title === 'HELP' }"
                class="w-12 h-12 p-1 flex items-center justify-center text-2xl text-noir-muted hover:text-white hover:bg-noir-panel rounded transition-all duration-200 relative group "
                active-class="text-noir-accent bg-noir-panel"
            >
                <img :src="`/icons/${item.svg}.svg`" />

                <!-- Tooltip -->
                <div class="absolute left-full ml-2 px-3 py-1 bg-noir-panel border border-noir-dark rounded text-xs text-white whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-200 font-bold tracking-wider">
                    {{ item.title }}
                </div>
            </RouterLink>
        </nav>

    </aside>
</template>
