<script setup>
import { ref, computed } from 'vue';
import { useStore } from 'vuex';
import { useRouter } from 'vue-router';

const store = useStore();
const router = useRouter();

const name = ref('');
const email = ref('');
const password = ref('');
const password_confirmation = ref('');
const error = computed(() => store.getters['auth/authError']);

const handleRegister = async () => {
    try {
        await store.dispatch('auth/register', {
            name: name.value,
            email: email.value,
            password: password.value,
            password_confirmation: password_confirmation.value
        });
        router.push('/');
    } catch (e) {
        // Error handled in store
    }
};
</script>

<template>
    <div class="auth-container">
        <h2>Register</h2>
        <form @submit.prevent="handleRegister">
            <div class="form-group">
                <label class="form-label">Name:</label>
                <input type="text" v-model="name" required class="form-input" />
            </div>
            <div class="form-group">
                <label class="form-label">Email:</label>
                <input type="email" v-model="email" required class="form-input" />
            </div>
            <div class="form-group">
                <label class="form-label">Password:</label>
                <input type="password" v-model="password" required class="form-input" />
            </div>
            <div class="form-group">
                <label class="form-label">Confirm Password:</label>
                <input type="password" v-model="password_confirmation" required class="form-input" />
            </div>
            <div v-if="error" class="error">{{ error }}</div>
            <button type="submit" class="btn btn--primary w-full">
                Register
            </button>
        </form>
        <p>
            <router-link to="/login">Already have an account?</router-link>
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
