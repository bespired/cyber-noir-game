<script setup>
import { useToast } from '../composables/useToast';
import Toast from './Toast.vue';

const { toasts, removeToast } = useToast();
</script>

<template>
    <div class="toast-container">
        <div class="w-full pointer-events-auto">
            <TransitionGroup name="list">
                <Toast
                    v-for="toast in toasts"
                    :key="toast.id"
                    :toast="toast"
                    @remove="removeToast"
                />
            </TransitionGroup>
        </div>
    </div>
</template>

<style scoped>

.list-enter-active,
.list-leave-active,
.list-move {
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.list-enter-from {
  opacity: 0;
  transform: translateX(150%) scale(0.8);
}
.list-leave-to {
  opacity: 0;
  transform: translateX(150%) scale(0.9);
}
.list-leave-active {
  position: absolute;
}

.toast-container {
    position: fixed;
    pointer-events: none;
    display: flex;
    align-items: flex-end;
    flex-direction: column;
    z-index: 9999;
    right: calc(var(--spacing) * 6);
    top: calc(var(--spacing) * 20);
    unicode-bidi: isolate;
}
</style>
