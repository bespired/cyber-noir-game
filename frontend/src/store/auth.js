import axios from '../axios';

export default {
    namespaced: true,
    state: {
        user: null,
        authError: null,
    },
    getters: {
        user: state => state.user,
        isAuthenticated: state => !!state.user,
        authError: state => state.authError,
    },
    mutations: {
        SET_USER(state, user) {
            state.user = user;
        },
        SET_ERROR(state, error) {
            state.authError = error;
        }
    },
    actions: {
        async getCsrfToken() {
            await axios.get('/sanctum/csrf-cookie');
        },
        async getUser({ commit }) {
            try {
                const response = await axios.get('/api/user');
                commit('SET_USER', response.data);
            } catch (error) {
                commit('SET_USER', null);
            }
        },
        async login({ commit, dispatch }, credentials) {
            await dispatch('getCsrfToken');
            try {
                await axios.post('/login', credentials);
                await dispatch('getUser');
                commit('SET_ERROR', null);
            } catch (error) {
                commit('SET_ERROR', error.response.data.message);
                throw error;
            }
        },
        async register({ commit, dispatch }, credentials) {
            await dispatch('getCsrfToken');
            try {
                await axios.post('/register', credentials);
                await dispatch('getUser');
                commit('SET_ERROR', null);
            } catch (error) {
                commit('SET_ERROR', error.response.data.message);
                throw error;
            }
        },
        async logout({ commit }) {
            await axios.post('/logout');
            commit('SET_USER', null);
        },
        async forgotPassword({ commit, dispatch }, email) {
            await dispatch('getCsrfToken');
            try {
                const response = await axios.post('/forgot-password', { email });
                commit('SET_ERROR', null);
                return response.data;
            } catch (error) {
                commit('SET_ERROR', error.response.data.message);
                throw error;
            }
        },
        async resetPassword({ commit, dispatch }, payload) {
            await dispatch('getCsrfToken');
            try {
                const response = await axios.post('/reset-password', payload);
                commit('SET_ERROR', null);
                return response.data;
            } catch (error) {
                commit('SET_ERROR', error.response.data.message);
                throw error;
            }
        }
    }
};
