<script setup>
import { ref, computed } from 'vue';
import { useStore } from 'vuex';
import { useRouter } from 'vue-router';

const store = useStore();
const router = useRouter();

const email = ref('');
const password = ref('');
const error = computed(() => store.getters['auth/authError']);

const handleLogin = async () => {
    try {
        await store.dispatch('auth/login', {
            email: email.value,
            password: password.value
        });
        router.push('/');
    } catch (e) {
        // Error handled in store
    }
};
</script>

<template>
    <div class="auth-container">
        <h2>Login</h2>
        <form @submit.prevent="handleLogin">
            <div class="form-group">
                <label>Email:</label>
                <input type="email" v-model="email" required class="rounded bg-blue-300 text-black"/>
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="password" v-model="password" required class="rounded bg-blue-300 text-black"/>
            </div>
            <div v-if="error" class="error">{{ error }}</div>
            <button type="submit" class="w-full bg-noir-accent text-white font-bold py-2 px-4 rounded hover:bg-blue-500 hover:shadow-[0_0_15px_rgba(59,130,246,0.5)] transition-all duration-300 uppercase tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                Login
            </button>
        </form>
        <p>
            <router-link to="/register">Register</router-link> |
            <router-link to="/forgot-password">Forgot Password?</router-link>
        </p>
    </div>
</template>

<style scoped>
.auth-container {
    max-width: 400px;
    margin: 0 auto;
    padding: 2rem;
}
.form-group {
    margin-bottom: 1rem;
}
input {
    width: 100%;
    padding: 0.5rem;
}
.error {
    color: red;
    margin-bottom: 1rem;
}
</style>
