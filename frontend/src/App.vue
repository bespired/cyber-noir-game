<script setup>
import { RouterView, useRouter, useRoute } from 'vue-router';
import Sidebar from './components/bars/Sidebar.vue';
import Header from './components/bars/Header.vue';
import ToastContainer from './components/ToastContainer.vue';
import { useStore } from 'vuex';
import { onMounted, onUnmounted, computed } from 'vue';

const store = useStore();
const router = useRouter();
const route = useRoute();
const isAuthenticated = computed(() => store.getters['auth/isAuthenticated']);

const checkAuth = async () => {
  await store.dispatch('auth/getUser');
  if (!isAuthenticated.value && route.meta.requiresAuth) {
    router.push({ name: 'dashboard' });
  }
};

onMounted(() => {
  checkAuth();
  window.addEventListener('focus', checkAuth);
});

onUnmounted(() => {
  window.removeEventListener('focus', checkAuth);
});
</script>

<template>
  <div class="min-h-screen bg-noir-bg text-noir-text font-mono">
    <!-- Sidebar (fixed on left) -->
    <Sidebar />

    <!-- Main content area (offset by sidebar width when authenticated) -->
    <div :class="isAuthenticated ? 'ml-16' : ''">
      <!-- Header -->
      <Header />

      <!-- Main content -->
      <main class="main-content">
        <RouterView />
      </main>

      <!-- Toast Notifications Layer -->
      <ToastContainer />

      <!-- Footer -->
      <footer class="main-footer">
        CYBER_NOIR // SYSTEM_VERSION_3.5.0
      </footer>
    </div>
  </div>
</template>

<style>
/* Global transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
