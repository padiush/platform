import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import Breadcrumbs from './Breadcrumbs';

vi.mock('@inertiajs/react', () => ({
    Link: ({ href, children, ...props }) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
}));

describe('Breadcrumbs', () => {
    it('links ancestors and marks the current page', () => {
        render(
            <Breadcrumbs
                items={[
                    { label: 'Catalogs', href: '/catalogs' },
                    { label: 'Verify Project', href: '/catalogs/1' },
                    { label: 'Inga edulis' },
                ]}
            />,
        );

        const nav = screen.getByRole('navigation');
        expect(nav).toHaveAccessibleName('navigation.breadcrumb');

        expect(screen.getByRole('link', { name: 'Catalogs' })).toHaveAttribute(
            'href',
            '/catalogs',
        );
        expect(
            screen.getByRole('link', { name: 'Verify Project' }),
        ).toHaveAttribute('href', '/catalogs/1');

        const current = screen.getByText('Inga edulis');
        expect(current).toHaveAttribute('aria-current', 'page');
        expect(current.closest('a')).toBeNull();
    });
});
