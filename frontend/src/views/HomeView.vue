<script setup>
import { useStore } from 'vuex'
import { computed } from 'vue'

const store = useStore()
const count = computed(() => store.state.count)
const doubleCount = computed(() => store.getters.doubleCount)

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
  </main>
</template>
