import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import nl from './locales/nl.json';

const i18n = createI18n({
    legacy: false, // Use Composition API
    locale: localStorage.getItem('user-locale') || 'nl', // default locale with persistence
    fallbackLocale: 'en', // fallback locale
    messages: {
        en,
        nl,
    },
});

export default i18n;
