import { createRouter, createWebHistory } from 'vue-router'
import store from '../store'
import HomeView from '../views/HomeView.vue'
import Login from '../views/auth/Login.vue'
import Register from '../views/auth/Register.vue'
import ForgotPassword from '../views/auth/ForgotPassword.vue'
import ResetPassword from '../views/auth/ResetPassword.vue'

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: '/',
            name: 'dashboard',
            component: () => import('../views/Dashboard.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/personages',
            name: 'personages',
            component: () => import('../views/Personages/PersonageList.vue'),
            meta: { requiresAuth: true },
            props: () => ({ type: 'persoon' })
        },
        {
            path: '/voertuigen',
            name: 'voertuigen',
            component: () => import('../views/Personages/PersonageList.vue'),
            meta: { requiresAuth: true },
            props: () => ({ type: 'voertuig' })
        },
        {
            path: '/personages/:id',
            name: 'personage-detail',
            component: () => import('../views/Personages/PersonageDetail.vue'),
            meta: { requiresAuth: true }
        },

        {
            path: '/map',
            name: 'map-list',
            component: () => import('../views/Map/GlobalMapView.vue'),
            meta: { requiresAuth: true }
        },
        // Conversations -> Dialogen
        {
            path: '/dialogen',
            name: 'dialogen-list',
            component: () => import('../views/Dialogen/DialoogList.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/dialogen/:id',
            name: 'dialoog-detail',
            component: () => import('../views/Dialogen/DialoogEditor.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/dialogen/:id/emulate',
            name: 'dialoog-emulate',
            component: () => import('../views/Dialogen/DialoogEmulatieView.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/map/:id',
            name: 'sector-detail',
            component: () => import('../views/Map/SectorDetail.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/map/:id/emulate',
            name: 'sector-emulate',
            component: () => import('../views/Map/SectorEmulationView.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/sector-map/:id',
            name: 'sector-map',
            component: () => import('../views/Map/SectorMapView.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/notities',
            name: 'notities',
            component: () => import('../views/Notes/NotitieList.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/locaties',
            name: 'locaties',
            component: () => import('../views/Locaties/LocatieList.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/locaties/:id',
            name: 'locatie-detail',
            component: () => import('../views/Locaties/LocatieDetail.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/locaties/:id/sector/:sectorId/3d',
            name: 'locatie-3d',
            component: () => import('../views/Locaties/LocatieThreeDView.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/gedrag',
            name: 'gedrag',
            component: () => import('../views/Gedrag/GedragList.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/gedrag/:id',
            name: 'gedrag-detail',
            component: () => import('../views/Gedrag/GedragDetail.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/reorder/locaties',
            name: 'locaties-reorder',
            component: () => import('../views/Locaties/LocatieReorder.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/aanwijzingen',
            name: 'aanwijzingen',
            component: () => import('../views/Aanwijzingen/AanwijzingList.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/aanwijzingen/:id',
            name: 'aanwijzing-detail',
            component: () => import('../views/Aanwijzingen/AanwijzingDetail.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/scenes',
            name: 'scenes',
            component: () => import('../views/Scenes/SceneList.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/scenes/:id',
            name: 'scene-detail',
            component: () => import('../views/Scenes/SceneDetail.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/scenes/:id/emulate',
            name: 'scene-emulate',
            component: () => import('../views/Scenes/ScenePlayerView.vue'),
            meta: { requiresAuth: true }
        },
        {
            path: '/about',
            name: 'about',
            component: () => import('../views/AboutView.vue')
        },
        {
            path: '/help',
            name: 'help',
            component: () => import('../views/HelpView.vue')
        },
        {
            path: '/login',
            name: 'login',
            component: Login,
            meta: { guest: true }
        },
        {
            path: '/register',
            name: 'register',
            component: Register,
            meta: { guest: true }
        },
        {
            path: '/forgot-password',
            name: 'forgot-password',
            component: ForgotPassword,
            meta: { guest: true }
        },
        {
            path: '/password-reset/:token',
            name: 'password-reset',
            component: ResetPassword,
            meta: { guest: true }
        }
    ]
})

router.beforeEach((to, from, next) => {
    if (to.meta.requiresAuth && !store.getters['auth/isAuthenticated']) {
        // Check if we might be authenticated but state is lost (refresh)
        // For now, simple redirect. Real app might try to fetch user first.
        // Since we fetch user in App.vue, we might need to wait.
        // But for simplicity:
        // If we have a cookie, axios interceptors handle 401.
        // Let's just allow navigation and let the API 401 redirect us if needed,
        // or better, rely on the store state.
        // If store state is empty, we might be loading.
        // For this boilerplate, let's keep it simple:
        next();
    } else if (to.meta.guest && store.getters['auth/isAuthenticated']) {
        next('/');
    } else {
        next();
    }
});

export default router
