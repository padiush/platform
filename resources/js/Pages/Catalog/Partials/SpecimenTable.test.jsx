import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import SpecimenTable from './SpecimenTable';

const determined = {
    id: 1,
    accession_number: 'MML-0001',
    collection_number: '042',
    collector: 'M. Menéndez',
    collected_on: '2026-03-14',
    repository: 'Community herbarium',
    is_vouchered: true,
    species: { id: 9, genus: 'Justicia', name: 'carthagenensis' },
    determiner: 'A. Botanist',
    qualifier: 'cf',
};

/** Someone examined it and could not name it. */
const indet = {
    id: 2,
    accession_number: null,
    collection_number: '043',
    collector: 'M. Menéndez',
    collected_on: null,
    repository: null,
    is_vouchered: false,
    species: null,
    determiner: 'A. Botanist',
    qualifier: null,
};

/** Nobody has looked at it yet. */
const untouched = {
    id: 3,
    accession_number: null,
    collection_number: '044',
    collector: 'M. Menéndez',
    collected_on: null,
    repository: null,
    is_vouchered: false,
    species: null,
    determiner: null,
    qualifier: null,
};

describe('SpecimenTable', () => {
    it('shows an empty state when nothing has been collected', () => {
        render(
            <SpecimenTable
                specimens={[]}
                emptyTitle="nothing yet"
                emptyHint="go collect"
            />,
        );

        expect(screen.getByText('nothing yet')).toBeInTheDocument();
    });

    it('labels an unvouchered collection rather than leaving it blank', () => {
        render(<SpecimenTable specimens={[determined, indet]} />);

        expect(screen.getByText('MML-0001')).toBeInTheDocument();
        expect(
            screen.getByText('catalogs.specimens.unvouchered'),
        ).toBeInTheDocument();
    });

    it('distinguishes examined-but-unnameable from nobody-has-looked', () => {
        render(<SpecimenTable specimens={[indet, untouched]} />);

        // Two different absences, and the table must not collapse them.
        expect(
            screen.getByText('catalogs.specimens.indet'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('catalogs.specimens.undetermined'),
        ).toBeInTheDocument();
    });

    it('renders the current determination with its qualifier', () => {
        render(<SpecimenTable specimens={[determined]} />);

        expect(
            screen.getByText(
                /catalogs\.specimens\.qualifier_cf Justicia carthagenensis/,
            ),
        ).toBeInTheDocument();
    });

    it('drops the determination column where it would repeat the page', () => {
        render(
            <SpecimenTable
                specimens={[determined]}
                showDetermination={false}
            />,
        );

        expect(
            screen.queryByText('catalogs.specimens.determination'),
        ).not.toBeInTheDocument();
    });

    it('offers no actions without the capability', () => {
        render(<SpecimenTable specimens={[determined]} canEdit={false} />);

        expect(
            screen.queryByText('catalogs.specimens.identify'),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByText('catalogs.specimens.deposit'),
        ).not.toBeInTheDocument();
    });

    it('routes each action to its own handler', () => {
        const onDetermine = vi.fn();
        const onDeposit = vi.fn();

        render(
            <SpecimenTable
                specimens={[determined]}
                canEdit
                onDetermine={onDetermine}
                onDeposit={onDeposit}
            />,
        );

        fireEvent.click(screen.getByText('catalogs.specimens.identify'));
        fireEvent.click(screen.getByText('catalogs.specimens.deposit'));

        expect(onDetermine).toHaveBeenCalledWith(determined);
        expect(onDeposit).toHaveBeenCalledWith(determined);
    });
});
