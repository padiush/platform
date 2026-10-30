import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import StoreBadges, { hasStoreBadges } from './StoreBadges';

const i18n = { language: 'pt', resolvedLanguage: undefined };

vi.mock('react-i18next', () => ({
    useTranslation: () => ({ t: (key) => key, i18n }),
}));

const none = {
    app_store: null,
    play_store: null,
    play_store_coming_soon: false,
};

describe('StoreBadges', () => {
    beforeEach(() => {
        i18n.language = 'pt';
        i18n.resolvedLanguage = undefined;
    });

    it('renders nothing for a deployment without store listings', () => {
        const { container } = render(<StoreBadges stores={none} />);

        expect(container).toBeEmptyDOMElement();
        expect(hasStoreBadges(none)).toBe(false);
        expect(hasStoreBadges(null)).toBe(false);
    });

    it("links Apple's badge, in the reader's language", () => {
        render(
            <StoreBadges
                stores={{ ...none, app_store: 'https://apps.example/1' }}
            />,
        );

        const badge = screen.getByAltText('public.app_store');
        expect(badge).toHaveAttribute(
            'src',
            '/images/badges/app-store-pt-black.svg',
        );
        expect(badge.closest('a')).toHaveAttribute(
            'href',
            'https://apps.example/1',
        );
    });

    it('announces a Play release that is not public yet, without the badge', () => {
        render(
            <StoreBadges stores={{ ...none, play_store_coming_soon: true }} />,
        );

        expect(screen.getByText('public.play_soon')).toBeInTheDocument();
        expect(
            screen.queryByAltText('public.google_play'),
        ).not.toBeInTheDocument();
    });

    it("shows Google's badge once the listing is live, and drops the announcement", () => {
        render(
            <StoreBadges
                stores={{
                    ...none,
                    play_store: 'https://play.example/app',
                    play_store_coming_soon: true,
                }}
            />,
        );

        expect(
            screen.getByAltText('public.google_play').closest('a'),
        ).toHaveAttribute('href', 'https://play.example/app');
        expect(screen.queryByText('public.play_soon')).not.toBeInTheDocument();
    });

    it('falls back to Spanish artwork for a language it has none for', () => {
        i18n.language = 'de';

        render(
            <StoreBadges
                stores={{ ...none, app_store: 'https://apps.example/1' }}
            />,
        );

        expect(screen.getByAltText('public.app_store')).toHaveAttribute(
            'src',
            '/images/badges/app-store-es-black.svg',
        );
    });
    it('shows the artwork for the language a browser reports with its region', () => {
        i18n.language = 'en-US';

        render(
            <StoreBadges
                stores={{
                    ...none,
                    app_store: 'https://apps.example/1',
                    play_store: 'https://play.example/app',
                }}
            />,
        );

        expect(screen.getByAltText('public.app_store')).toHaveAttribute(
            'src',
            '/images/badges/app-store-en-black.svg',
        );
        expect(screen.getByAltText('public.google_play')).toHaveAttribute(
            'src',
            '/images/badges/google-play-en.png',
        );
    });
});
