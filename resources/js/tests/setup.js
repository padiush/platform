import '@testing-library/jest-dom/vitest';
import { vi } from 'vitest';

// jsdom ships <dialog> methods that throw "Not implemented"; stub them so
// components that call showModal()/close() in tests don't error.
if (typeof HTMLDialogElement !== 'undefined') {
    HTMLDialogElement.prototype.showModal = function () {
        this.open = true;
    };
    HTMLDialogElement.prototype.close = function () {
        this.open = false;
    };
}

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
