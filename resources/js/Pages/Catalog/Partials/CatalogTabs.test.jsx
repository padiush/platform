import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
}));

globalThis.route = (name, params) =>
    `/${name}?project=${params?.project ?? ''}`;

import CatalogTabs from './CatalogTabs';

const project = { id: 7, name: 'A study' };

describe('CatalogTabs', () => {
    it('reaches specimens without going through a taxon', () => {
        render(<CatalogTabs project={project} active="species" />);

        // The whole point: collections are peers of taxa, not nested under one.
        expect(
            screen.getByRole('tab', { name: 'catalogs.specimens.title' }),
        ).toHaveAttribute('href', '/catalogs.specimens.index?project=7');
    });

    it('reaches the species list back from specimens', () => {
        render(<CatalogTabs project={project} active="specimens" />);

        expect(
            screen.getByRole('tab', { name: 'catalogs.species_list' }),
        ).toHaveAttribute('href', '/catalogs.show?project=7');
    });

    it('does not offer the species tab when there are no species', () => {
        render(
            <CatalogTabs
                project={project}
                active="specimens"
                speciesCount={0}
            />,
        );

        // catalogs.show redirects away from an empty catalog, so a link there
        // would bounce — most visibly on the project that has specimens first.
        const species = screen.getByRole('tab', {
            name: 'catalogs.species_list',
        });

        expect(species).toHaveAttribute('aria-disabled', 'true');
        expect(species).not.toHaveAttribute('href');
    });

    it('offers it once a species exists', () => {
        render(
            <CatalogTabs
                project={project}
                active="specimens"
                speciesCount={1}
            />,
        );

        expect(
            screen.getByRole('tab', { name: 'catalogs.species_list' }),
        ).toHaveAttribute('href', '/catalogs.show?project=7');
    });

    it('marks the current tab and does not link it to itself', () => {
        render(<CatalogTabs project={project} active="specimens" />);

        const current = screen.getByRole('tab', {
            name: 'catalogs.specimens.title',
        });

        expect(current).toHaveAttribute('aria-current', 'page');
        expect(current).not.toHaveAttribute('href');
    });
});
