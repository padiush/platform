import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import PublicLayout from './PublicLayout';

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ href, children, ...props }) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
    usePage: () => ({ props: { auth: null } }),
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
});
