import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import PublicLayout from './PublicLayout';

const page = { props: { auth: null, publicSiteUrl: null } };

// Inertia's <Link> is marked so a test can tell it from a plain anchor: a link
// to a page outside the application must be the latter.
vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ href, children, ...props }) => (
        <a href={href} data-inertia-link="" {...props}>
            {children}
        </a>
    ),
    usePage: () => page,
}));

vi.mock('@/Hooks/useFlashMessage', () => ({
    useFlashMessage: () => ({ FlashAlert: () => null, flashShown: false }),
}));

vi.mock('@/Components/ApplicationFullLogo', () => ({
    default: (props) => <svg aria-label="Padiush" {...props} />,
}));

vi.mock('@/Components/ThemeToggle', () => ({
    default: () => null,
}));

vi.mock('@/Components/TranslationToggle', () => ({
    default: () => null,
}));

describe('PublicLayout', () => {
    beforeEach(() => {
        page.props = { auth: null, publicSiteUrl: null };
        const current = vi.fn(() => false);
        const route = vi.fn((name) =>
            name === undefined ? { current } : `/${name}`,
        );
        window.route = route;
    });

    it('exposes the mobile navigation trigger as a named button', () => {
        render(<PublicLayout title="Welcome">Content</PublicLayout>);

        expect(
            screen.getByRole('button', { name: 'navigation.open_menu' }),
        ).toHaveAttribute('aria-controls', 'public-mobile-navigation');
    });

    it('links to the site with plain anchors, never Inertia links', () => {
        // Regression: an Inertia link fetched the static landing page over
        // XHR and opened it in a modal over the contact page.
        page.props.publicSiteUrl = 'https://example.org';

        const { container } = render(
            <PublicLayout title="Contact">Content</PublicLayout>,
        );

        const links = container.querySelectorAll(
            'a[href="https://example.org/"]',
        );
        expect(links.length).toBeGreaterThan(0);
        links.forEach((link) =>
            expect(link).not.toHaveAttribute('data-inertia-link'),
        );
    });

    it('offers no home link when the deployment has no site', () => {
        render(<PublicLayout title="Contact">Content</PublicLayout>);

        expect(screen.queryByText('public.home')).not.toBeInTheDocument();
        // The logo still leads somewhere: into the application.
        expect(screen.getByRole('link', { name: 'Padiush' })).toHaveAttribute(
            'href',
            '/login',
        );
    });

    it('sends a signed-in visitor from the logo to the dashboard', () => {
        page.props.auth = { user: { id: 1 } };

        render(<PublicLayout title="Contact">Content</PublicLayout>);

        expect(screen.getByRole('link', { name: 'Padiush' })).toHaveAttribute(
            'href',
            '/dashboard',
        );
    });
});
