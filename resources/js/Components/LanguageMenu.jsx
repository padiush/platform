import { faLanguage } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useTranslation } from 'react-i18next';

/** The interface languages, in the order the public site lists them. */
const LANGUAGES = ['es', 'en', 'pt'];

/** Each language's name, written in that language. */
const LANGUAGE_NAMES = {
    es: 'Español',
    en: 'English',
    pt: 'Português (Brasil)',
};

/**
 * The language menu of the public pages' header: the current language, and
 * the three to choose from. It looks and behaves like the one on the public
 * site, so moving between the two does not change the header. Elsewhere the
 * application keeps the compact TranslationToggle.
 */
export default function LanguageMenu() {
    const { t, i18n } = useTranslation();
    const current = LANGUAGES.includes(i18n.language) ? i18n.language : 'es';

    const choose = (language) => {
        i18n.changeLanguage(language);
        // A daisyUI dropdown stays open while it holds focus.
        document.activeElement?.blur();
    };

    return (
        <div className="dropdown dropdown-end">
            <button
                type="button"
                className="btn btn-ghost gap-2 px-3"
                aria-label={t('navigation.language')}
                aria-haspopup="menu"
            >
                <FontAwesomeIcon icon={faLanguage} className="text-xl" />
                <span className="hidden sm:inline">
                    {LANGUAGE_NAMES[current]}
                </span>
                <span className="uppercase sm:hidden">{current}</span>
            </button>
            <ul
                tabIndex={0}
                className="menu dropdown-content bg-base-200 text-base-content rounded-box z-50 mt-3 w-52 p-2 shadow-sm"
            >
                {LANGUAGES.map((language) => (
                    <li key={language}>
                        <button
                            type="button"
                            lang={language}
                            aria-current={
                                language === current ? 'true' : undefined
                            }
                            className={
                                language === current ? 'menu-active' : ''
                            }
                            onClick={() => choose(language)}
                        >
                            {LANGUAGE_NAMES[language]}
                        </button>
                    </li>
                ))}
            </ul>
        </div>
    );
}
