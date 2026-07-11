import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

const LANGUAGES = ['es', 'pt', 'en'];

// Mapping language codes to native names for display
const LANGUAGE_NAMES = {
    es: 'Español',
    pt: 'Português (Brasil)',
    en: 'English',
};

export default function TranslationToggle({ className = '' }) {
    const { i18n } = useTranslation();

    const [langIndex, setLangIndex] = useState(
        LANGUAGES.indexOf(i18n.language),
    );

    const toggleLanguage = () => {
        const nextIndex = (langIndex + 1) % LANGUAGES.length;
        setLangIndex(nextIndex);
        i18n.changeLanguage(LANGUAGES[nextIndex]);
    };

    // If no supported language is set, default to Spanish
    useEffect(() => {
        if (!LANGUAGES.includes(i18n.language)) {
            i18n.changeLanguage('es');
        }
    }, [i18n]);

    useEffect(() => {
        const currentIndex = LANGUAGES.indexOf(i18n.language);
        if (currentIndex !== langIndex) {
            setLangIndex(currentIndex);
        }
    }, [i18n.language, langIndex]);

    return (
        <button
            onClick={toggleLanguage}
            className={`${className} btn btn-ghost`}
        >
            {LANGUAGE_NAMES[LANGUAGES[langIndex]]}
        </button>
    );
}
