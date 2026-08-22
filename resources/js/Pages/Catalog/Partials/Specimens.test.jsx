import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const post = vi.fn();
const patch = vi.fn();
const destroy = vi.fn();
const setData = vi.fn();

vi.mock('@inertiajs/react', () => ({
    useForm: (initial = {}) => ({
        data: initial,
        setData,
        post,
        patch,
        delete: destroy,
        reset: vi.fn(),
        processing: false,
        errors: {},
    }),
}));

globalThis.route = (name) => `/${name}`;

import Specimens from './Specimens';

const project = { id: 1, name: 'A study' };
const species = { id: 5, genus: 'Justicia', name: 'carthagenensis' };

const vouchered = {
    id: 10,
    accession_number: 'MML-0001',
    collection_number: '042',
    collector: 'M. Menéndez',
    collected_on: '2026-03-14',
    repository: 'Community herbarium',
    is_vouchered: true,
    determiner: 'A determiner',
    determined_on: '2026-04-01',
    qualifier: 'cf',
};

const unvouchered = {
    id: 11,
    accession_number: null,
    collection_number: '043',
    collector: 'M. Menéndez',
    collected_on: null,
    repository: null,
    is_vouchered: false,
    determiner: null,
    determined_on: null,
    qualifier: null,
};

function renderList(props = {}) {
    return render(
        <Specimens
            project={project}
            species={species}
            specimens={[vouchered, unvouchered]}
            nextAccessionNumber="MML-0002"
            {...props}
        />,
    );
}

describe('Specimens', () => {
    it('shows an empty state when nothing has been collected', () => {
        renderList({ specimens: [] });

        expect(
            screen.getByText('catalogs.specimens.none_title'),
        ).toBeInTheDocument();
    });

    it('reports voucher coverage rather than hiding the gap', () => {
        renderList();

        // The absence is a data-quality figure, not a blank cell. The harness
        // renders interpolation options beside the key, so the counts
        // themselves are asserted here.
        expect(
            screen.getByText(
                'catalogs.specimens.coverage {"vouchered":1,"total":2}',
            ),
        ).toBeInTheDocument();
    });

    it('labels an unvouchered collection instead of leaving it blank', () => {
        renderList();

        expect(screen.getByText('MML-0001')).toBeInTheDocument();
        expect(
            screen.getByText('catalogs.specimens.unvouchered'),
        ).toBeInTheDocument();
    });

    it('flattens the current determination onto the row', () => {
        renderList();

        expect(
            screen.getByText(
                'catalogs.specimens.qualifier_cf · A determiner · 2026-04-01',
            ),
        ).toBeInTheDocument();
    });

    it('offers no editing controls without the capability', () => {
        renderList({ canEdit: false });

        expect(
            screen.queryByText('catalogs.specimens.add'),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByText('catalogs.specimens.edit'),
        ).not.toBeInTheDocument();
    });

    it('opens the form for an editor', () => {
        renderList({ canEdit: true });

        fireEvent.click(screen.getByText('catalogs.specimens.add'));

        expect(screen.getByTestId('specimen-form')).toBeInTheDocument();
    });

    it('does not offer to mint a number for an already vouchered specimen', () => {
        renderList({ canEdit: true });

        fireEvent.click(screen.getAllByText('catalogs.specimens.edit')[0]);

        // An accession number already written on a label is not ours to change.
        expect(
            screen.queryByText('catalogs.specimens.mint'),
        ).not.toBeInTheDocument();
    });

    it('offers to mint one for an unvouchered specimen', () => {
        renderList({ canEdit: true });

        fireEvent.click(screen.getAllByText('catalogs.specimens.edit')[1]);

        expect(screen.getByText('catalogs.specimens.mint')).toBeInTheDocument();
    });
});
