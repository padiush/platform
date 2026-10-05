import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
}));

globalThis.route = (name, params) => `/${name}/${params.project}`;

import DataTabs from './DataTabs';

const project = { id: 4 };

describe('DataTabs', () => {
    it('offers each tab the role opens, in the project', () => {
        render(
            <DataTabs
                project={project}
                active="table"
                tabs={{ link: true, reports: true, export: true }}
            />,
        );

        expect(
            screen.getByRole('tab', { name: 'data.tabs.link' }),
        ).toHaveAttribute('href', '/data.link/4');
        expect(
            screen.getByRole('tab', { name: 'data.tabs.reports' }),
        ).toHaveAttribute('href', '/data.reports/4');
        expect(
            screen.getByRole('tab', { name: 'data.tabs.export' }),
        ).toHaveAttribute('href', '/data.export/4');
    });

    it('leaves out the tabs the role does not open', () => {
        render(
            <DataTabs
                project={project}
                active="table"
                tabs={{ link: false, reports: true, export: true }}
            />,
        );

        expect(
            screen.queryByRole('tab', { name: 'data.tabs.link' }),
        ).not.toBeInTheDocument();
    });

    it('marks the current tab and does not link it to itself', () => {
        render(<DataTabs project={project} active="table" tabs={{}} />);

        const current = screen.getByRole('tab', { name: 'data.tabs.table' });
        expect(current).toHaveAttribute('aria-current', 'page');
        expect(current).not.toHaveAttribute('href');
    });
});
