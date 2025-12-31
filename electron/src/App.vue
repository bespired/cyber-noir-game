<script setup>
import { ref, onMounted } from 'vue'
import { gameDataService } from '@/services/GameDataService'

const mode = import.meta.env.VITE_APP_MODE || 'unknown'
const message = ref('Cyber Noir Engine')
const sectors = ref([])
const loading = ref(false)

const loadGameData = async () => {
    loading.value = true;
    sectors.value = await gameDataService.loadSectors();
    loading.value = false;
}

onMounted(() => {
    console.log(`Running in ${mode} mode`)
})
</script>

<template>
  <div class="container">
    <h1>{{ message }}</h1>
    <p>Mode: {{ mode }}</p>
    
    <div class="card">
      <button @click="loadGameData" :disabled="loading">
        {{ loading ? 'Loading...' : 'Load Game Data' }}
      </button>
      <button>Settings</button>
    </div>

    <div v-if="sectors && sectors.length > 0" class="data-preview">
        <h3>Loaded Sectors:</h3>
        <ul>
            <li v-for="sector in sectors" :key="sector.id">
                {{ sector.naam }} ({{ sector.scenes?.length || 0 }} scenes)
            </li>
        </ul>
    </div>
  </div>
</template>

<style scoped>
.container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  color: #fff;
  background-color: #1a1a1a;
  font-family: 'Courier New', Courier, monospace;
}

.card {
  margin-top: 2rem;
  display: flex;
  gap: 1rem;
}

button {
  background: #00ffcc;
  color: #000;
  border: none;
  padding: 10px 20px;
  cursor: pointer;
  font-weight: bold;
}

button:hover {
  background: #00ccaa;
}
</style>
