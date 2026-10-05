import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

const LANGUAGES = ['es', 'pt', 'en'];

// Mapping language codes to native names for display
const LANGUAGE_NAMES = {
    es: 'Español',
    pt: 'Português (Brasil)',
    en: 'English',
};

/**
 * Cycles the interface language. `compact` shows the language's code instead
 * of its name, for the folded sidebar; the name stays its accessible label.
 */
export default function TranslationToggle({ className = '', compact = false }) {
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
            type="button"
            onClick={toggleLanguage}
            className={`${className} btn btn-ghost`}
            aria-label={
                compact ? LANGUAGE_NAMES[LANGUAGES[langIndex]] : undefined
            }
            title={compact ? LANGUAGE_NAMES[LANGUAGES[langIndex]] : undefined}
        >
            {compact
                ? LANGUAGES[langIndex]?.toUpperCase()
                : LANGUAGE_NAMES[LANGUAGES[langIndex]]}
        </button>
    );
}
