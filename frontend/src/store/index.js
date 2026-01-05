import { createStore } from 'vuex'
import auth from './auth'

const store = createStore({
    modules: {
        auth
    },
    state: {
        count: 0
    },
    mutations: {
        increment(state) {
            state.count++
        }
    },
    actions: {
        increment(context) {
            context.commit('increment')
        }
    },
    getters: {
        doubleCount(state) {
            return state.count * 2
        }
    }
})

export default store
