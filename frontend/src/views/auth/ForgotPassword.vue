<script setup>
import { ref, computed } from 'vue';
import { useStore } from 'vuex';

const store = useStore();

const email = ref('');
const message = ref('');
const error = computed(() => store.getters['auth/authError']);

const handleForgotPassword = async () => {
    try {
        const response = await store.dispatch('auth/forgotPassword', email.value);
        message.value = response.status;
    } catch (e) {
        // Error handled in store
    }
};
</script>

<template>
    <div class="auth-container">
        <h2>Forgot Password</h2>
        <form @submit.prevent="handleForgotPassword">
            <div class="form-group">
                <label>Email:</label>
                <input type="email" v-model="email" required />
            </div>
            <div v-if="message" class="success">{{ message }}</div>
            <div v-if="error" class="error">{{ error }}</div>
            <button type="submit" class="w-full bg-noir-accent text-white font-bold py-2 px-4 rounded hover:bg-blue-500 hover:shadow-[0_0_15px_rgba(59,130,246,0.5)] transition-all duration-300 uppercase tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                Send Reset Link
            </button>
        </form>
        <p>
            <router-link to="/login">Back to Login</router-link>
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
.success {
    color: green;
    margin-bottom: 1rem;
}
</style>
