import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import AuthLayout from './AuthLayout';

const page = { props: { publicSiteUrl: null } };

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
    default: (props) => <svg {...props} />,
}));

vi.mock('@/Components/ThemeToggle', () => ({
    default: () => null,
}));

vi.mock('@/Components/LanguageMenu', () => ({
    default: () => null,
}));

describe('AuthLayout', () => {
    beforeEach(() => {
        page.props = { publicSiteUrl: null };
    });

    it('links back to the site with plain anchors, never Inertia links', () => {
        // Regression: an Inertia link fetched the static landing page over
        // XHR and opened it in a modal over the sign-in page.
        page.props.publicSiteUrl = 'https://example.org';

        const { container } = render(
            <AuthLayout title="Sign in" heading="Sign in">
                Form
            </AuthLayout>,
        );

        const links = container.querySelectorAll(
            'a[href="https://example.org/"]',
        );
        // The logo and the "back to the site" link, once per layout width.
        expect(links).toHaveLength(3);
        links.forEach((link) =>
            expect(link).not.toHaveAttribute('data-inertia-link'),
        );
    });

    it('offers no way back when the deployment has no site', () => {
        const { container } = render(
            <AuthLayout title="Sign in" heading="Sign in">
                Form
            </AuthLayout>,
        );

        expect(container.querySelectorAll('a')).toHaveLength(0);
        expect(screen.queryByText('auth.back_to_site')).not.toBeInTheDocument();
    });
});
