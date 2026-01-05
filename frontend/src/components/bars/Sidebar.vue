<script setup>
import { RouterLink } from 'vue-router';
import { useStore } from 'vuex';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const store = useStore();
const { t } = useI18n();
const isAuthenticated = computed(() => store.getters['auth/isAuthenticated']);

const navItems = computed(() => [
    { path: '/about',        svg: 'about',     icon: 'i',  label: t('common.sidebar.about'),        title: t('common.sidebar.about')        },
    { path: '/',             svg: 'dashboard', icon: '📊', label: t('common.sidebar.dashboard'),    title: t('common.sidebar.dashboard')    },
    { path: '/map',          svg: 'map',       icon: '🗺️', label: t('common.sidebar.map'),          title: t('common.sidebar.map')          },
    { path: '/scenes',       svg: 'scene',     icon: '🎬', label: t('common.sidebar.scenes'),       title: t('common.sidebar.scenes')       },
    { path: '/gedrag',       svg: 'behavior',  icon: '🧠', label: t('common.sidebar.behavior'),     title: t('common.sidebar.behavior')     },
    { path: '/dialogen',     svg: 'dialogue',  icon: '💬', label: t('common.sidebar.dialogues'),    title: t('common.sidebar.dialogues')    },
    { path: '/locaties',     svg: 'location',  icon: '📍', label: t('common.sidebar.locations'),    title: t('common.sidebar.locations')    },
    { path: '/personages',   svg: 'personage', icon: '👤', label: t('common.sidebar.characters'),   title: t('common.sidebar.characters')   },
    { path: '/voertuigen',   svg: 'vehicle',   icon: '🚗', label: t('common.sidebar.vehicles'),     title: t('common.sidebar.vehicles')     },
    { path: '/aanwijzingen', svg: 'clue',      icon: '🔍', label: t('common.sidebar.clues'),        title: t('common.sidebar.clues')        },
    { path: '/notities',     svg: 'note',      icon: '📝', label: t('common.sidebar.notes'),        title: t('common.sidebar.notes')        },
    { path: '/help',         svg: 'help',      icon: '?',  label: t('common.sidebar.help'),         title: t('common.sidebar.help')         },
]);
</script>

<template>
    <aside
        v-if="isAuthenticated" class="fixed left-0 top-0 h-screen w-16 bg-noir-dark border-r border-noir-panel flex flex-col items-center py-2 z-50">
        <!-- Navigation Items -->
        <nav class="flex-grow flex flex-col space-y-2" >
            <RouterLink
                v-for="item in navItems"
                :key="item.path"
                :to="item.path"
                :title="item.title"
                :class="[{ 'mt-auto': item.svg === 'help' }, {'mb-6':  item.svg === 'about'}]"
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
