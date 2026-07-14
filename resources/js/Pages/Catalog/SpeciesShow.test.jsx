import { render, screen, waitFor } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('axios', () => ({
    default: { post: vi.fn() },
}));

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
}));

vi.mock('@/Layouts/AuthenticatedLayout', () => ({
    default: ({ children }) => <div>{children}</div>,
}));

vi.mock('@/Components/DeletionModal', () => ({ default: () => null }));

globalThis.route = (name) => `/${name}`;

import axios from 'axios';
import SpeciesShow from './SpeciesShow';

const species = {
    id: 5,
    family: 'Acanthaceae',
    genus: 'Justicia',
    name: 'carthagenensis',
    authority: 'Jacq.',
};
const project = { id: 1, name: 'Verify Project' };

const spellingVariantResult = {
    recorded: 'Justicia carthagenensis Jacq.',
    binomial: 'Justicia carthagenensis',
    match: null,
    candidates: [
        {
            wfo_id: 'wfo-0000354479',
            stable_uri: 'https://list.worldfloraonline.org/wfo-0000354479',
            full_name_plain: 'Justicia carthaginensis Jacq.',
            full_name_html: '<i>Justicia carthaginensis</i> Jacq.',
            is_accepted: true,
            accepted_name: null,
            is_spelling_variant: true,
        },
    ],
};

describe('SpeciesShow', () => {
    it('queries WFO with the authored name and flags an accepted spelling variant', async () => {
        axios.post.mockResolvedValue({ data: spellingVariantResult });

        render(<SpeciesShow species={species} project={project} />);

        expect(axios.post).toHaveBeenCalledWith('/wfo.query', {
            genus: 'Justicia',
            name: 'carthagenensis',
            authority: 'Jacq.',
        });

        expect(
            await screen.findByText('catalogs.wfo.spelling_variant'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('catalogs.wfo.is_accepted'),
        ).toBeInTheDocument();
        // With no exact match, the closest-names heading is shown.
        expect(
            screen.getByText('catalogs.wfo.closest_names'),
        ).toBeInTheDocument();
    });

    it('shows linked records to a data-capable viewer', async () => {
        axios.post.mockResolvedValue({ data: spellingVariantResult });

        render(
            <SpeciesShow
                species={species}
                project={project}
                canViewData={true}
                linkedCount={1}
                linkedRecords={{
                    data: [
                        {
                            id: 10,
                            recorded_name: 'guarumo',
                            recorded_at: null,
                            recorder: 'Tester',
                            form: { id: 2, name: 'Plantas' },
                            section: { id: 3, name: 'Especie' },
                        },
                    ],
                    links: [],
                }}
            />,
        );

        expect(await screen.findByText('guarumo')).toBeInTheDocument();
        expect(
            screen.getByText('catalogs.linked.view_in_data'),
        ).toBeInTheDocument();
    });

    it('gates the records but shows the count to a catalog-only viewer', async () => {
        axios.post.mockResolvedValue({ data: spellingVariantResult });

        render(
            <SpeciesShow
                species={species}
                project={project}
                canViewData={false}
                linkedCount={3}
                linkedRecords={null}
            />,
        );

        await waitFor(() => expect(axios.post).toHaveBeenCalled());

        // The gated note shares a paragraph with the count, so match a substring.
        expect(screen.getByText(/catalogs\.linked\.gated/)).toBeInTheDocument();
        expect(screen.queryByText('guarumo')).not.toBeInTheDocument();
    });
});
