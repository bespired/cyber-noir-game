<script setup>
import { onMounted } from 'vue';

const props = defineProps({
    toast: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['remove']);

// Optional: Auto-remove handled by composable, but we can emit if we want local control
</script>

<template>
    <div
        class="toast-item relative overflow-hidden mb-3 p-4 rounded border shadow-lg transition-all"
        :class="{
            'bg-noir-success/10 border-noir-success text-noir-success': props.toast.type === 'success',
            'bg-noir-danger/10 border-noir-danger text-noir-danger':    props.toast.type === 'error',
            'bg-noir-warning/10 border-noir-warning text-noir-warning': props.toast.type === 'warning',
            'bg-noir-accent/10 border-noir-accent text-noir-accent':    props.toast.type === 'info'
        }"
    >
        <!-- Hexagon Decorative Corner -->
        <div class="absolute -top-1 -right-1 w-4 h-4 rotate-45"
            :class="{
                'bg-noir-success': props.toast.type === 'success',
                'bg-noir-danger':  props.toast.type === 'error',
                'bg-noir-warning': props.toast.type === 'warning',
                'bg-noir-accent':  props.toast.type === 'info'
            }"
        ></div>

        <div class="flex items-start gap-3">
            <span class="text-lg">
                <template v-if="props.toast.type      === 'success'">✅</template>
                <template v-else-if="props.toast.type === 'error'"  >⚠️</template>
                <template v-else-if="props.toast.type === 'warning'">⚡</template>
                <template v-else>ℹ️</template>
            </span>
            <div class="flex-grow">
                <div class="text-[10px] uppercase tracking-widest font-bold mb-1 opacity-70">
                    {{ props.toast.type }}_KENNISGEVING
                </div>
                <div class="text-sm font-mono whitespace-pre-line leading-snug">
                    {{ props.toast.message }}
                </div>
            </div>
            <button @click="emit('remove', props.toast.id)" class="text-current opacity-50 hover:opacity-100 transition-opacity">
                ✕
            </button>
        </div>

        <!-- Scan line effect -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden opacity-10">
            <div class="w-full h-[1px] bg-white absolute scan-move"></div>
        </div>
    </div>
</template>

<style scoped>
.toast-item {
    min-width: 300px;
    max-width: 450px;
    backdrop-filter: blur(8px);
}

.scan-move {
    animation: scan 2s linear infinite;
}

@keyframes scan {
    0% { top: -10%; }
    100% { top: 110%; }
}
</style>
