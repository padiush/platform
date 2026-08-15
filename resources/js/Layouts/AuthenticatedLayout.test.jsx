import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import AuthenticatedLayout from './AuthenticatedLayout';

// Ziggy's global helper is used two ways: route('name') for a URL, and a bare
// route() for the .current() check that marks the active nav item.
global.route = (name) => (name ? `/${name}` : { current: () => false });

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ href, children, ...rest }) => (
        <a href={href} {...rest}>
            {children}
        </a>
    ),
    usePage: () => ({
        props: {
            auth: {
                user: { id: 1, name: 'Investigadora' },
                capabilities: {},
            },
        },
    }),
}));

vi.mock('@/Hooks/useFlashMessage', () => ({
    useFlashMessage: () => ({ FlashAlert: () => null, flashShown: false }),
}));

// Chrome that carries its own browser-API dependencies and is not under test.
vi.mock('@/Components/ThemeToggle', () => ({ default: () => null }));
vi.mock('@/Components/TranslationToggle', () => ({ default: () => null }));

describe('AuthenticatedLayout', () => {
    /**
     * Section 13 of the AGPL requires that people using Padiush over a network
     * can obtain its source. Every signed-in page therefore has to carry the
     * offer — losing this link would be a licence violation, not a cosmetic
     * regression, so it is asserted rather than left to inspection.
     */
    it('offers the source of the running software on every signed-in page', () => {
        render(
            <AuthenticatedLayout title="Proyectos">
                contenido
            </AuthenticatedLayout>,
        );

        const link = screen.getByRole('link', { name: 'software.title' });

        expect(link).toBeInTheDocument();
        expect(link).toHaveAttribute('href', '/software.notice');
    });
});
