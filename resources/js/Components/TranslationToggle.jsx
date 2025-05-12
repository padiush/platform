import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function TranslationToggle({ className = '' }) {
    const { i18n } = useTranslation();
    const languages = ['es', 'pt', 'en'];

    const [langIndex, setLangIndex] = useState(
        languages.indexOf(i18n.language),
    );

    const toggleLanguage = () => {
        const nextIndex = (langIndex + 1) % languages.length;
        setLangIndex(nextIndex);
        i18n.changeLanguage(languages[nextIndex]);
    };

    // If no language is set, default to Spanish
    useEffect(() => {
        if (i18n.language !== 'es' && !languages.includes(i18n.language)) {
            i18n.changeLanguage('es');
        }
    }, []);

    useEffect(() => {
        const currentIndex = languages.indexOf(i18n.language);
        if (currentIndex !== langIndex) {
            setLangIndex(currentIndex);
        }
    }, [i18n.language]);

    // Mapping language codes to native names for display
    const languageNames = {
        es: 'Español',
        pt: 'Português (Brasil)',
        en: 'English',
    };

    return (
        <button
            onClick={toggleLanguage}
            className={`${className} btn btn-ghost`}
        >
            {languageNames[languages[langIndex]]}
        </button>
    );
}
