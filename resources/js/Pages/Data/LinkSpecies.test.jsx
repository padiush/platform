import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('axios', () => ({
    default: { post: vi.fn(), get: vi.fn(), isCancel: vi.fn(() => false) },
}));

vi.mock('@inertiajs/react', () => ({
    router: { get: vi.fn(), reload: vi.fn() },
    usePage: () => ({ props: { csrf_token: 'test' } }),
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
}));

vi.mock('@/Layouts/AuthenticatedLayout', () => ({
    default: ({ children }) => <div>{children}</div>,
}));

vi.mock('@/Components/SpeciesPickerModal', () => ({ default: () => null }));

globalThis.route = (name) => `/${name}`;

import { router } from '@inertiajs/react';
import LinkSpecies from './LinkSpecies';

const member = (key, linked, species = null) => ({
    key,
    instance_id: `i-${key}`,
    section_id: 1,
    repeatable_index: null,
    reported_name: 'guarumo',
    section_name: 'Planta',
    interview: { recorded_at: '2026-07-11T00:00:00Z', recorder: 'Tester' },
    species,
    linked,
});

const groupedProps = {
    project: { id: 1, name: 'Verify Project' },
    filters: { q: '', status: 'all', group: true },
    totals: { linked: 1, unlinked: 1, total: 2 },
    rows: {
        data: [
            {
                key: 'g-guarumo',
                name: 'guarumo',
                total: 2,
                linked_count: 1,
                mixed: false,
                species: {
                    id: 5,
                    genus: 'Cecropia',
                    name: 'obtusifolia',
                    authority: 'Bertol.',
                },
                members: [member('m1', true), member('m2', false)],
            },
        ],
        links: [],
    },
};

describe('LinkSpecies', () => {
    it('renders grouped rows and stays grouped when toggled (props unchanged)', () => {
        render(<LinkSpecies {...groupedProps} />);

        expect(screen.getByText('guarumo')).toBeInTheDocument();
        // "Link all" only appears on a multi-member group.
        expect(screen.getByText('data.link_all')).toBeInTheDocument();

        // Toggling only issues a router.get; the props (and data shape) don't
        // change here. The view must NOT re-render flat rows against grouped
        // data — that regression blanked the page.
        fireEvent.click(screen.getByRole('checkbox'));

        expect(router.get).toHaveBeenCalled();
        expect(screen.getByText('data.link_all')).toBeInTheDocument();
    });

    it('renders flat rows when filters.group is false', () => {
        render(
            <LinkSpecies
                {...groupedProps}
                filters={{ q: '', status: 'all', group: false }}
                rows={{ data: [member('r1', false)], links: [] }}
            />,
        );

        expect(screen.getByText('guarumo')).toBeInTheDocument();
        // No group-only "link all" affordance in flat mode.
        expect(screen.queryByText('data.link_all')).not.toBeInTheDocument();
    });
});
