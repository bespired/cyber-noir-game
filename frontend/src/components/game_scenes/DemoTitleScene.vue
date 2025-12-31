<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
    title: { type: String, default: 'CYBER NOIR' },
    subtitle: { type: String, default: 'Press Start' },
    nextSceneId: { type: [String, Number], default: null }
});

const emit = defineEmits(['scene-complete']);

const start = () => {
    // E.g. play a sound, then emit
    console.log("Emitting next scene:", props.nextSceneId);
    emit('scene-complete', { targetSceneId: props.nextSceneId });
};
</script>
<template>
    <div class="w-full h-full flex flex-col items-center justify-center bg-black text-white p-10 select-none">
        <h1 class="text-6xl font-bold tracking-widest text-noir-accent mb-4 animate-pulse">{{ title }}</h1>
        <p class="text-xl text-noir-muted mb-12 font-mono ml-1">{{ subtitle }}</p>
        <p>Replicants zijn bio-geëngineerde mensen, ontworpen door de Tyrell Corporation voor gebruik buiten de aarde. Hun verbeterde kracht maakte hen tot ideale slavenarbeiders.</p>
        <p>Na een reeks gewelddadige opstanden werd de productie verboden en ging de Tyrell Corporation failliet.</p>
        <p>De instorting van ecosystemen in het midden van de jaren 2020 leidde tot de opkomst van industrieel Niander Mallace, wiens beheersing van synthetische landbouw een wereldwijde hongersnood voorkwam.</p>
        <p>Mallace verwierf de restanten van Tyrell Corp en creëerde een nieuwe generatie replicants — gehoorzaam en controleerbaar.</p>
        <p>Veel oudere modellen — Nexus-8 replicants met een onbeperkte levensduur — overleefden. Zij worden opgejaagd en “gepensioneerd”.</p>
        <p>Degenen die op hen jagen, dragen nog steeds de naam…</p>
        <p>Nexus Runners</p>
        <button @click="start" class="px-8 py-3 border border-noir-accent text-noir-accent hover:bg-noir-accent hover:text-black transition-colors font-bold tracking-wider uppercase text-lg">
            INITIALIZE_SEQUENCE
        </button>
    </div>
</template>