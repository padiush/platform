import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
}));

globalThis.route = (name, params) =>
    `/${name}?project=${params?.project ?? ''}`;

import RecordTabs from './RecordTabs';

const project = { id: 7, name: 'A study' };

describe('RecordTabs', () => {
    it('reaches the permits from the records', () => {
        render(<RecordTabs project={project} active="records" />);

        expect(
            screen.getByRole('tab', { name: 'catalogs.permits.title' }),
        ).toHaveAttribute('href', '/catalogs.permits.index?project=7');
    });

    it('reaches the records back from the permits', () => {
        render(<RecordTabs project={project} active="permits" />);

        expect(
            screen.getByRole('tab', { name: 'records.tab_records' }),
        ).toHaveAttribute('href', '/catalogs.fieldRecords.index?project=7');
    });

    it('leaves the species to the catalog', () => {
        render(<RecordTabs project={project} active="records" />);

        expect(screen.getAllByRole('tab')).toHaveLength(2);
        expect(
            screen.queryByRole('tab', { name: 'catalogs.species_list' }),
        ).not.toBeInTheDocument();
    });

    it('marks the current tab and does not link it to itself', () => {
        render(<RecordTabs project={project} active="records" />);

        const current = screen.getByRole('tab', {
            name: 'records.tab_records',
        });

        expect(current).toHaveAttribute('aria-current', 'page');
        expect(current).not.toHaveAttribute('href');
    });
});
