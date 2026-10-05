import { render, screen, within } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    // `method` and `as` are Inertia's; kept as data so the test can read them.
    Link: ({ children, method, as, ...props }) => (
        <a data-method={method} data-as={as} {...props}>
            {children}
        </a>
    ),
}));

vi.mock('@/Layouts/AuthenticatedLayout', () => ({
    default: ({ children }) => <div>{children}</div>,
}));

// The create form and the deletion dialog are not under test.
vi.mock('./Partials/ProjectForm', () => ({ default: () => null }));
vi.mock('@/Components/DeletionModal', () => ({ default: () => null }));

globalThis.route = (name) => `/${name}`;

import Index from './Index';

const study = {
    id: 1,
    name: 'Plantas útiles',
    is_example: false,
    can_manage: true,
    created_at: '2026-10-01T12:00:00Z',
    user: { name: 'Investigadora' },
};

const example = {
    ...study,
    id: 2,
    name: 'Ejemplo: plantas útiles de la cordillera',
    is_example: true,
};

const page = (projects) => render(<Index projects={projects} invites={[]} />);

describe('the list of projects', () => {
    it('offers the example while the user has none', () => {
        page([study]);

        const offer = screen.getByText('example.cta').closest('a');
        expect(offer).toHaveAttribute('href', '/projects.example.store');
        expect(offer).toHaveAttribute('data-method', 'post');
    });

    it('offers the example to someone with no projects yet', () => {
        page([]);

        expect(screen.getByText('example.cta')).toBeInTheDocument();
    });

    it('stops offering the example once it is listed, and marks it', () => {
        page([study, example]);

        expect(screen.queryByText('example.cta')).not.toBeInTheDocument();

        const heading = screen.getByRole('heading', { name: /cordillera/ });
        expect(within(heading).getByText('example.badge')).toBeInTheDocument();
        expect(
            within(
                screen.getByRole('heading', { name: 'Plantas útiles' }),
            ).queryByText('example.badge'),
        ).not.toBeInTheDocument();
    });
});
