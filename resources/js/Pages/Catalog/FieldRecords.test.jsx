import { fireEvent, render, screen, within } from '@testing-library/react';
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

import FieldRecords from './FieldRecords';

const project = { id: 1, name: 'A study' };

const vouchered = {
    id: 1,
    accession_number: 'MML-0001',
    collection_number: '042',
    collector: 'M. Menéndez',
    collected_on: '2026-03-14',
    repository: 'Community herbarium',
    is_vouchered: true,
    species: { id: 9, genus: 'Justicia', name: 'carthagenensis' },
    determiner: 'A. Botanist',
    qualifier: null,
};

const unidentified = {
    id: 2,
    accession_number: null,
    collection_number: '043',
    collector: 'M. Menéndez',
    collected_on: null,
    repository: null,
    is_vouchered: false,
    species: null,
    determiner: null,
    qualifier: null,
};

function renderPage(props = {}) {
    return render(
        <FieldRecords
            project={project}
            fieldRecords={[vouchered, unidentified]}
            summary={{ total: 2, vouchered: 1, unidentified: 0 }}
            catalog={[{ id: 9, genus: 'Justicia', name: 'carthagenensis' }]}
            nextAccessionNumber="MML-0002"
            {...props}
        />,
    );
}

describe('FieldRecords page', () => {
    it('lists collections that have never been identified', () => {
        renderPage();

        // The whole point of the page: unidentified material is reachable,
        // where a taxon-scoped view could never show it.
        expect(screen.getByText('043')).toBeInTheDocument();
        expect(
            screen.getByText('catalogs.fieldRecords.undetermined'),
        ).toBeInTheDocument();
    });

    it('narrows to what is still unidentified', () => {
        renderPage();

        fireEvent.click(
            screen.getByText('catalogs.fieldRecords.filter_undetermined'),
        );

        expect(screen.getByText('043')).toBeInTheDocument();
        expect(screen.queryByText('042')).not.toBeInTheDocument();
    });

    it('narrows to what is still unvouchered', () => {
        renderPage();

        fireEvent.click(
            screen.getByText('catalogs.fieldRecords.filter_unvouchered'),
        );

        expect(screen.queryByText('MML-0001')).not.toBeInTheDocument();
        expect(screen.getByText('043')).toBeInTheDocument();
    });

    it('offers both formats whenever there is something to export', () => {
        renderPage({ canEdit: false });

        // Reading the list is enough to export it, so no capability gate here.
        expect(
            screen.getByText('catalogs.fieldRecords.export'),
        ).toBeInTheDocument();

        for (const format of ['xlsx', 'csv']) {
            expect(
                screen.getByText(`catalogs.fieldRecords.format_${format}`),
            ).toHaveAttribute('href', '/catalogs.fieldRecords.export');
        }
    });

    it('offers no export when nothing has been collected', () => {
        renderPage({
            fieldRecords: [],
            summary: { total: 0, vouchered: 0, unidentified: 0 },
        });

        expect(
            screen.queryByText('catalogs.fieldRecords.export'),
        ).not.toBeInTheDocument();
    });

    it('offers no way to add a collection without the capability', () => {
        renderPage({ canEdit: false });

        expect(
            screen.queryByRole('button', { name: 'catalogs.fieldRecords.add' }),
        ).not.toBeInTheDocument();
    });

    it('opens a modal to record a collection, asking nothing about taxonomy', () => {
        renderPage({ canEdit: true });

        // The modal's own title reuses this key, so ask for the button.
        fireEvent.click(
            screen.getByRole('button', { name: 'catalogs.fieldRecords.add' }),
        );

        const form = within(screen.getByTestId('collection-form'));

        // A voucher number and a repository do not exist yet at collection
        // time, and identification usually happens later. Scoped to the form —
        // the table behind the modal has its own accession column.
        expect(
            form.getByText('catalogs.fieldRecords.collector'),
        ).toBeInTheDocument();
        expect(
            form.queryByText('catalogs.fieldRecords.accession_number'),
        ).not.toBeInTheDocument();
        expect(
            form.queryByText('catalogs.fieldRecords.repository'),
        ).not.toBeInTheDocument();
        expect(
            form.queryByText('catalogs.fieldRecords.taxon'),
        ).not.toBeInTheDocument();
    });

    it('identifies through its own modal', () => {
        renderPage({ canEdit: true });

        fireEvent.click(
            screen.getAllByText('catalogs.fieldRecords.identify')[1],
        );

        expect(screen.getByTestId('determine-form')).toBeInTheDocument();
        expect(
            screen.getByText('catalogs.fieldRecords.supersedes_note'),
        ).toBeInTheDocument();
    });

    it('deposits through its own modal', () => {
        renderPage({ canEdit: true });

        fireEvent.click(
            screen.getAllByText('catalogs.fieldRecords.deposit')[1],
        );

        expect(screen.getByTestId('deposit-form')).toBeInTheDocument();
        expect(
            screen.getByText('catalogs.fieldRecords.mint'),
        ).toBeInTheDocument();
    });

    it('does not offer to mint for an already vouchered fieldRecord', () => {
        renderPage({ canEdit: true });

        fireEvent.click(
            screen.getAllByText('catalogs.fieldRecords.deposit')[0],
        );

        expect(
            screen.queryByText('catalogs.fieldRecords.mint'),
        ).not.toBeInTheDocument();
        expect(
            screen.getByText('catalogs.fieldRecords.already_vouchered_note'),
        ).toBeInTheDocument();
    });
});
