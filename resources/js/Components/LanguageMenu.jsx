import { chooseLanguage } from '@/lib/accountLanguage';
import { LANGUAGES, currentLanguage } from '@/lib/interfaceLanguage';
import { faLanguage } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

/** Each language's name, written in that language. */
const LANGUAGE_NAMES = {
    es: 'Español',
    en: 'English',
    pt: 'Português (Brasil)',
};

/**
 * The language menu: the current language, and the three to choose from. It
 * is the same menu everywhere — the public pages' header, where it matches
 * the public site's, the sign-in screens and the sidebar. For a signed-in
 * user a choice is saved on the account (see lib/accountLanguage.js).
 *
 * `compact` shows the language's code instead of its name, for the folded
 * sidebar; `up` opens the menu upwards, for the foot of the sidebar.
 */
export default function LanguageMenu({
    compact = false,
    up = false,
    className = '',
    buttonClassName = '',
}) {
    const { t, i18n } = useTranslation();
    const signedIn = Boolean(usePage().props.auth?.user);
    const current = currentLanguage(i18n);

    const choose = (language) => {
        chooseLanguage(i18n, language, signedIn);
        // A daisyUI dropdown stays open while it holds focus.
        document.activeElement?.blur();
    };

    return (
        <div
            className={`dropdown ${up ? 'dropdown-top' : 'dropdown-end'} ${className}`}
        >
            <button
                type="button"
                className={`btn btn-ghost gap-2 px-3 ${buttonClassName}`}
                aria-label={t('navigation.language')}
                title={compact ? LANGUAGE_NAMES[current] : undefined}
                aria-haspopup="menu"
            >
                <FontAwesomeIcon icon={faLanguage} className="text-xl" />
                {compact ? (
                    <span className="uppercase">{current}</span>
                ) : (
                    <>
                        <span className="hidden sm:inline">
                            {LANGUAGE_NAMES[current]}
                        </span>
                        <span className="uppercase sm:hidden">{current}</span>
                    </>
                )}
            </button>
            {/* Folded, the sidebar is too narrow for the names, and its
                scrolling panel clips whatever overflows it, so the list
                shows the codes and keeps the names as their labels. */}
            <ul
                tabIndex={0}
                className={`menu dropdown-content bg-base-200 text-base-content rounded-box z-50 shadow-sm ${compact ? 'w-16 p-1' : 'w-52 p-2'} ${up ? 'mb-3' : 'mt-3'}`}
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
                            aria-label={
                                compact ? LANGUAGE_NAMES[language] : undefined
                            }
                            title={
                                compact ? LANGUAGE_NAMES[language] : undefined
                            }
                        >
                            {compact
                                ? language.toUpperCase()
                                : LANGUAGE_NAMES[language]}
                        </button>
                    </li>
                ))}
            </ul>
        </div>
    );
}
