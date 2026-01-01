<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
    title:       { type: String, default: 'CYBER NOIR' },
    subtitle:    { type: String, default: 'Press Start' },
    nextSceneId: { type: [String, Number], default: null }
});

const emit = defineEmits(['scene-complete']);

const start = () => {
    // E.g. play a sound, then emit
    console.log("Emitting next scene:", props.nextSceneId);
    emit('scene-complete', { targetSceneId: props.nextSceneId });
};
</script>
<style>
    .titles {
        font-family: "Wallace";
        text-transform: uppercase;
        letter-spacing: 2px;
        line-height: 2;
        opacity: .8;
        padding: 0 10%;
    }
    .titles .red {
        color: red;
    }
    .titles h1 {
        display: inline-flex;
    }
    .titles p {
        margin-bottom: 2em;
    }
    .titles button {
        border: 1px solid red;
        color: red;
    }
    .titles button:hover {
        border: 1px solid red;
        background-color: red;
        color: black;
        cursor: pointer;
    }

    .titles .line1, .titles .line2, .titles .line3, .titles .line4,
    .titles .line5, .titles .line6, .titles .line7, .titles .line8 {
        opacity: 0;
        transform-origin: 0;
        animation: fadeInScale 2s ease-out forwards;
    }

    .titles .line1 { animation-delay: 0.5s;  }
    .titles .line2 { animation-delay: 4.5s;  }
    .titles .line3 { animation-delay: 8.5s;  }
    .titles .line4 { animation-delay: 12.5s;  }
    .titles .line5 { animation-delay: 16.5s; }
    .titles .line6 { animation-delay: 20.5s; }
    .titles .line7 { animation-delay: 24.5s; }
    .titles .line8 { animation-delay: 28.5s; }

    @keyframes fadeInScale {
        0% {
            opacity: 0;
            transform: scale(0.95);
        }
        100% {
            opacity: 1;
            transform: scale(1.0);
        }
    }
</style>
<template>
    <div class="titles bg-black text-white p-10 select-none">

        <h1 class="line8 text-6xl font-bold tracking-widest red mb-4 animate-pulse">{{ title }}</h1>
        <p class="line1"><span class="red">Replicants</span> zijn bio-geëngineerde mensen, ontworpen door de Tyrell Corporation voor gebruik buiten de aarde. Hun verbeterde kracht maakte hen tot ideale slavenarbeiders.</p>

        <p class="line2">Na een reeks gewelddadige opstanden werd de productie verboden en ging de Tyrell Corporation failliet.</p>
        <p class="line3">De instorting van ecosystemen in het midden van de jaren 2020 leidde tot de opkomst van industrieel Niander Mallace, wiens beheersing van synthetische landbouw een wereldwijde hongersnood voorkwam.</p>
        <p class="line4">Mallace verwierf de restanten van Tyrell Corp en creëerde een nieuwe generatie replicants — gehoorzaam en controleerbaar.</p>
        <p class="line5">Veel oudere modellen — Nexus-8 replicants met een onbeperkte levensduur — overleefden. Zij worden opgejaagd en “gepensioneerd”.</p>
        <p class="line6">Degenen die op hen jagen, dragen nog steeds de naam…</p>
        <p class="line7 red">Nexus Runners</p>

        <!-- <p class="text-xl text-noir-muted mb-12 font-mono ml-1">{{ subtitle }}</p> -->

        <button @click="start" class="line8 px-8 py-3 border transition-colors font-bold text-lg">
            INITIALIZE_SEQUENCE
        </button>
    </div>
</template>