import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import LanguageMenu from './LanguageMenu';

const i18n = { language: 'es', changeLanguage: vi.fn() };

vi.mock('react-i18next', () => ({
    useTranslation: () => ({ t: (key) => key, i18n }),
}));

describe('LanguageMenu', () => {
    beforeEach(() => {
        i18n.language = 'es';
        i18n.changeLanguage.mockClear();
    });

    it('names the current language on its button', () => {
        render(<LanguageMenu />);

        expect(
            screen.getByRole('button', { name: 'navigation.language' }),
        ).toHaveTextContent('Español');
    });

    it('lists the three languages, marking the current one', () => {
        render(<LanguageMenu />);

        expect(
            screen.getByRole('button', { name: 'English' }),
        ).not.toHaveAttribute('aria-current');
        expect(screen.getByRole('button', { name: 'Español' })).toHaveAttribute(
            'aria-current',
            'true',
        );
        expect(
            screen.getByRole('button', { name: 'Português (Brasil)' }),
        ).toBeInTheDocument();
    });

    it('switches the interface to the language chosen', () => {
        render(<LanguageMenu />);

        fireEvent.click(screen.getByRole('button', { name: 'English' }));

        expect(i18n.changeLanguage).toHaveBeenCalledWith('en');
    });

    it('falls back to Spanish for a language it does not offer', () => {
        i18n.language = 'de';

        render(<LanguageMenu />);

        expect(
            screen.getByRole('button', { name: 'navigation.language' }),
        ).toHaveTextContent('Español');
    });
});
