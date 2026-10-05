import { fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
    router: { get: vi.fn() },
}));

vi.mock('@/Layouts/AuthenticatedLayout', () => ({
    default: ({ children }) => <div>{children}</div>,
}));

// The form itself has its own tests; here it only has to be the one opened.
vi.mock('@/Pages/Catalog/Partials/SpeciesForm', () => ({
    default: () => <form aria-label="species-form" />,
}));

globalThis.route = (name) => `/${name}`;

import SpeciesIndex from './SpeciesIndex';

const project = { id: 1, name: 'A study' };
const emptyPage = { data: [], links: [] };
const onePage = {
    data: [
        {
            id: 3,
            family: 'Urticaceae',
            genus: 'Cecropia',
            name: 'obtusifolia',
            authority: 'Bertol.',
            answers: { length: 2 },
        },
    ],
    links: [],
};

function catalog(props = {}) {
    return render(
        <SpeciesIndex
            project={project}
            species={onePage}
            counts={{ species: 1, linked_species: 1, linked_families: 1 }}
            {...props}
        />,
    );
}

afterEach(() => window.history.replaceState(null, '', '/'));

describe('SpeciesIndex', () => {
    it('opens an empty catalog on how to start it, not on a search', () => {
        catalog({
            species: emptyPage,
            counts: { species: 0, linked_species: 0, linked_families: 0 },
            canEdit: true,
        });

        expect(screen.getByText('catalogs.empty.title')).toBeInTheDocument();
        expect(
            screen.getByText('catalogs.empty.hint_edit'),
        ).toBeInTheDocument();
        expect(
            screen.queryByLabelText('catalogs.search_placeholder'),
        ).not.toBeInTheDocument();
    });

    it('tells a reader who will fill an empty catalog', () => {
        catalog({
            species: emptyPage,
            counts: { species: 0, linked_species: 0, linked_families: 0 },
        });

        expect(
            screen.getByText('catalogs.empty.hint_read'),
        ).toBeInTheDocument();
    });

    it('lets an editor register a species from the catalog', () => {
        catalog({ canEdit: true });

        fireEvent.click(
            screen.getByRole('button', { name: 'catalogs.register_species' }),
        );

        expect(screen.getByLabelText('species-form')).toBeInTheDocument();
        expect(window.location.search).toBe('?create=1');
    });

    it('opens the register form from a deep link', () => {
        window.history.replaceState(null, '', '/?create=1');
        catalog({ canEdit: true });

        expect(screen.getByLabelText('species-form')).toBeInTheDocument();
    });

    it('offers no registering to a reader', () => {
        window.history.replaceState(null, '', '/?create=1');
        catalog();

        expect(
            screen.queryByRole('button', { name: 'catalogs.register_species' }),
        ).not.toBeInTheDocument();
        expect(screen.queryByLabelText('species-form')).not.toBeInTheDocument();
    });

    it('lists the species, each opening its page', () => {
        catalog();

        expect(
            screen.getByRole('link', { name: /Cecropia obtusifolia/ }),
        ).toHaveAttribute('href', '/catalogs.species.show');
    });
});
