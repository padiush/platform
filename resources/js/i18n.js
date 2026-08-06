import i18n from 'i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import Backend from 'i18next-http-backend';
import { initReactI18next } from 'react-i18next';

i18n.use(initReactI18next).use(LanguageDetector).use(Backend);

// Keep the document language in sync for assistive tech; fires on init too.
i18n.on('languageChanged', (lng) => {
    document.documentElement.lang = lng;
});

i18n.init({
    debug: import.meta.env.DEV,
    fallbackLng: 'es',
    load: 'languageOnly',
    interpolation: {
        escapeValue: false,
    },
    backend: {
        // The default namespace stays one file per language, loaded on every
        // page. Any other namespace lives in its own directory and is fetched
        // only by the pages that ask for it — that keeps long documents (the
        // privacy policy, the terms) out of the payload every visitor pays for.
        loadPath: (lngs, namespaces) =>
            namespaces[0] === 'translation'
                ? '/locales/{{lng}}.json'
                : '/locales/{{ns}}/{{lng}}.json',
        queryStringParams: { v: Date.now() }, // bust cache
    },
    detection: {
        order: [
            'querystring',
            'cookie',
            'localStorage',
            'navigator',
            'htmlTag',
            'path',
            'subdomain',
        ],
        caches: ['cookie'],
    },
});

export default i18n;
