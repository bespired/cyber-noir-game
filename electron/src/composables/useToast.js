import { ref } from 'vue';

const toasts = ref([]);

export function useToast() {
    const addToast = (message, type = 'success', duration = 3000) => {
        const id = Date.now() + Math.random();
        const toast = {
            id,
            message,
            type,
            duration
        };
        toasts.value.push(toast);

        if (duration > 0) {
            setTimeout(() => {
                removeToast(id);
            }, duration);
        }
    };

    const removeToast = (id) => {
        const index = toasts.value.findIndex(t => t.id === id);
        if (index !== -1) {
            toasts.value.splice(index, 1);
        }
    };

    return {
        toasts,
        addToast,
        removeToast,
        success: (msg, dur) => addToast(msg, 'success', dur),
        error: (msg, dur)   => addToast(msg, 'error', dur),
        warning: (msg, dur) => addToast(msg, 'warning', dur),
        info: (msg, dur)    => addToast(msg, 'info', dur)
    };
}
