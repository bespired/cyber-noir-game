<script setup>
import { useStore } from 'vuex'
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

const store = useStore()
const count = computed(() => store.state.count)
const doubleCount = computed(() => store.getters.doubleCount)
const isAuthenticated = computed(() => store.getters['auth/isAuthenticated'])

const increment = () => {
  store.dispatch('increment')
}
</script>

<template>
  <main>
    <h1>Home</h1>
    <p>Count: {{ count }}</p>
    <p>Double Count: {{ doubleCount }}</p>
    <button @click="increment">Increment</button>

    <div v-if="!isAuthenticated" class="mt-4">
      <RouterLink :to="{ name: 'login' }" class="login-btn">
        Login
      </RouterLink>
    </div>
  </main>
</template>

<style scoped>
.mt-4 {
  margin-top: 1rem;
}

.login-btn {
  display: inline-block;
  padding: 0.5rem 1rem;
  background-color: #007bff;
  color: white;
  text-decoration: none;
  border-radius: 4px;
}

.login-btn:hover {
  background-color: #0056b3;
}
</style>
