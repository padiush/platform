import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import LanguageMenu from './LanguageMenu';

const i18n = { language: 'es', changeLanguage: vi.fn() };
const page = { props: { auth: null } };
const chooseLanguage = vi.fn();

vi.mock('react-i18next', () => ({
    useTranslation: () => ({ t: (key) => key, i18n }),
}));

vi.mock('@inertiajs/react', () => ({ usePage: () => page }));

vi.mock('@/lib/accountLanguage', () => ({
    chooseLanguage: (...args) => chooseLanguage(...args),
}));

describe('LanguageMenu', () => {
    beforeEach(() => {
        i18n.language = 'es';
        page.props = { auth: null };
        chooseLanguage.mockClear();
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

    it('switches a guest to the language chosen, without an account to save it on', () => {
        render(<LanguageMenu />);

        fireEvent.click(screen.getByRole('button', { name: 'English' }));

        expect(chooseLanguage).toHaveBeenCalledWith(i18n, 'en', false);
    });

    it("saves a signed-in user's choice as their preference", () => {
        page.props.auth = { user: { id: 1, locale: 'es' } };

        render(<LanguageMenu />);

        fireEvent.click(
            screen.getByRole('button', { name: 'Português (Brasil)' }),
        );

        expect(chooseLanguage).toHaveBeenCalledWith(i18n, 'pt', true);
    });

    it('shows the code alone where there is no room for the name', () => {
        render(<LanguageMenu compact />);

        const button = screen.getByRole('button', {
            name: 'navigation.language',
        });
        expect(button).toHaveTextContent(/^es$/i);
        expect(button).toHaveAttribute('title', 'Español');
    });

    it('falls back to Spanish for a language it does not offer', () => {
        i18n.language = 'de';

        render(<LanguageMenu />);

        expect(
            screen.getByRole('button', { name: 'navigation.language' }),
        ).toHaveTextContent('Español');
    });

    it('reads a region-qualified language as its language', () => {
        // i18next reports what the browser asked for, such as "en-US".
        i18n.language = 'en-US';

        render(<LanguageMenu />);

        expect(
            screen.getByRole('button', { name: 'navigation.language' }),
        ).toHaveTextContent('English');
    });

    it('lists the codes, named, where there is no room for the names', () => {
        render(<LanguageMenu compact />);

        const option = screen.getByRole('button', {
            name: 'Português (Brasil)',
        });
        expect(option).toHaveTextContent(/^PT$/);
    });
});
