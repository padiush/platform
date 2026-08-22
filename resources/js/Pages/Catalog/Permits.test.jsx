import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
    router: { delete: vi.fn() },
    useForm: (initial = {}) => ({
        data: initial,
        setData: vi.fn(),
        post: vi.fn(),
        patch: vi.fn(),
        delete: vi.fn(),
        reset: vi.fn(),
        processing: false,
        errors: {},
    }),
}));

vi.mock('@/Layouts/AuthenticatedLayout', () => ({
    default: ({ children }) => <div>{children}</div>,
}));

globalThis.route = (name) => `/${name}`;

import Permits from './Permits';

const project = { id: 1, name: 'A study' };

const current = {
    id: 1,
    authority: 'MARN',
    reference: 'RES-042-2026',
    issued_on: '2026-01-15',
    expires_on: '2027-01-14',
    has_expired: false,
    specimens_count: 3,
};

const lapsed = {
    ...current,
    id: 2,
    reference: 'RES-001-2024',
    expires_on: '2025-01-14',
    has_expired: true,
    specimens_count: 0,
};

function renderPage(props = {}) {
    return render(
        <Permits project={project} permits={[current, lapsed]} {...props} />,
    );
}

describe('Permits page', () => {
    it('says plainly that nothing here is verified', () => {
        renderPage();

        expect(screen.getByText('catalogs.permits.intro')).toBeInTheDocument();
    });

    it('shows an empty state before any permit is recorded', () => {
        renderPage({ permits: [] });

        expect(
            screen.getByText('catalogs.permits.none_title'),
        ).toBeInTheDocument();
    });

    it('marks a permit whose date has passed', () => {
        renderPage();

        // A note taken off the permit, not a ruling about a collection.
        expect(
            screen.getByText(/catalogs\.permits\.expired/),
        ).toBeInTheDocument();
    });

    it('reports how many collections each permit covers', () => {
        renderPage();

        expect(screen.getByText('3')).toBeInTheDocument();
    });

    it('offers no editing controls without the capability', () => {
        renderPage({ canEdit: false });

        expect(
            screen.queryByRole('button', { name: 'catalogs.permits.add' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByText('catalogs.permits.edit'),
        ).not.toBeInTheDocument();
    });

    it('opens the form for an editor', () => {
        renderPage({ canEdit: true });

        // The modal's own title reuses this key, so ask for the button.
        fireEvent.click(
            screen.getByRole('button', { name: 'catalogs.permits.add' }),
        );

        expect(screen.getByTestId('permit-form')).toBeInTheDocument();
    });

    it('warns that deleting one leaves its collections behind', () => {
        renderPage({ canEdit: true });

        fireEvent.click(screen.getAllByText('catalogs.permits.delete')[0]);

        expect(
            screen.getByText(
                /catalogs\.permits\.confirm_delete_with_specimens/,
            ),
        ).toBeInTheDocument();
    });
});
