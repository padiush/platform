import '@testing-library/jest-dom/vitest';
import { vi } from 'vitest';

// Components consume translations through react-i18next; tests assert
// against the raw keys.
vi.mock('react-i18next', () => ({
    useTranslation: () => ({
        t: (key, options) =>
            options && typeof options === 'object'
                ? `${key} ${JSON.stringify(options)}`
                : key,
        i18n: {
            language: 'en',
            changeLanguage: () => Promise.resolve(),
        },
    }),
    initReactI18next: { type: '3rdParty', init: () => {} },
}));
