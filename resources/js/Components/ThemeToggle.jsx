import { faMoon, faSunBright } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

const STORAGE_KEY = 'theme';
const LIGHT = 'padiushlight';
const DARK = 'padiushdark';

function systemTheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches
        ? DARK
        : LIGHT;
}

/**
 * Light/dark switch. Persists the explicit choice in localStorage and stamps
 * data-theme on <html>; with no stored choice the OS preference applies (the
 * inline script in app.blade.php replays the stored choice before paint).
 */
export default function ThemeToggle({ className = '' }) {
    const { t } = useTranslation();

    const [theme, setTheme] = useState(
        () => localStorage.getItem(STORAGE_KEY) || systemTheme(),
    );

    useEffect(() => {
        document.documentElement.dataset.theme = theme;
    }, [theme]);

    const toggle = () => {
        const next = theme === DARK ? LIGHT : DARK;
        localStorage.setItem(STORAGE_KEY, next);
        setTheme(next);
    };

    const isDark = theme === DARK;

    return (
        <button
            onClick={toggle}
            className={`btn btn-ghost ${className}`}
            aria-label={
                isDark ? t('theme.switch_to_light') : t('theme.switch_to_dark')
            }
            title={
                isDark ? t('theme.switch_to_light') : t('theme.switch_to_dark')
            }
        >
            <FontAwesomeIcon icon={isDark ? faSunBright : faMoon} />
        </button>
    );
}
