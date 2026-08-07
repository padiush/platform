import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('axios', () => ({
    default: { post: vi.fn() },
}));

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
    router: { patch: vi.fn() },
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
    beforeEach(() => {
        // Each test sets its own axios.post behaviour; isolate call history.
        axios.post.mockReset();
    });

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

    it('offers accept actions and previews the change when the user can edit', async () => {
        axios.post.mockImplementation((url) => {
            if (url.includes('wfo-preview')) {
                return Promise.resolve({
                    data: {
                        current: {
                            family: 'Acanthaceae',
                            genus: 'Justicia',
                            name: 'carthagenensis',
                            authority: 'Jacq.',
                        },
                        proposed: {
                            family: 'Acanthaceae',
                            genus: 'Justicia',
                            name: 'carthaginensis',
                            authority: 'Jacq.',
                        },
                    },
                });
            }
            return Promise.resolve({ data: spellingVariantResult });
        });

        render(
            <SpeciesShow species={species} project={project} canEdit={true} />,
        );

        const acceptButton = await screen.findByText(
            'catalogs.accept.use_this',
        );
        fireEvent.click(acceptButton);

        // The preview modal opens and shows the proposed corrected epithet.
        expect(
            await screen.findByText('catalogs.accept.title'),
        ).toBeInTheDocument();
        expect(await screen.findByText('carthaginensis')).toBeInTheDocument();
        expect(
            axios.post.mock.calls.some(([url]) => url.includes('wfo-preview')),
        ).toBe(true);
    });

    it('hides accept actions from a user who cannot edit', async () => {
        axios.post.mockResolvedValue({ data: spellingVariantResult });

        render(<SpeciesShow species={species} project={project} />);

        await screen.findByText('catalogs.wfo.spelling_variant');
        expect(
            screen.queryByText('catalogs.accept.use_this'),
        ).not.toBeInTheDocument();
    });

    it('renders a cached distribution without refetching it', async () => {
        axios.post.mockResolvedValue({ data: spellingVariantResult });

        render(
            <SpeciesShow
                species={species}
                project={project}
                distribution={{
                    matched: { name: 'Justicia carthaginensis Jacq.' },
                    native: [{ code: 'TDWG:BLZ', name: 'Belize' }],
                    introduced: [{ code: 'TDWG:HWI', name: 'Hawaii' }],
                    source: 'WCVP via GBIF',
                }}
            />,
        );

        expect(await screen.findByText('Belize')).toBeInTheDocument();
        expect(screen.getByText('Hawaii')).toBeInTheDocument();
        // Cached range is used as-is; no distribution request is made.
        expect(
            axios.post.mock.calls.every(
                ([url]) => !url.includes('distribution'),
            ),
        ).toBe(true);
    });

    it('fetches the distribution when none is cached', async () => {
        axios.post.mockImplementation((url) => {
            if (url.includes('distribution')) {
                return Promise.resolve({
                    data: {
                        matched: { name: 'Cecropia obtusifolia Bertol.' },
                        native: [
                            { code: 'TDWG:MXN', name: 'Mexico Northwest' },
                        ],
                        introduced: [],
                        source: 'WCVP via GBIF',
                    },
                });
            }
            return Promise.resolve({ data: spellingVariantResult });
        });

        render(<SpeciesShow species={species} project={project} />);

        expect(await screen.findByText('Mexico Northwest')).toBeInTheDocument();
        expect(
            axios.post.mock.calls.some(([url]) => url.includes('distribution')),
        ).toBe(true);
    });
});
