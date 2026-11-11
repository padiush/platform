import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
}));

vi.mock('@/Layouts/AuthenticatedLayout', () => ({
    default: ({ children }) => <div>{children}</div>,
}));

vi.mock('@/Components/DeletionModal', () => ({ default: () => null }));

vi.mock('@/Components/FormModal', () => ({
    default: ({ children }) => <div>{children}</div>,
}));

vi.mock('@/Pages/Data/Partials/InterviewMedia', () => ({
    default: ({ instanceId }) => <div>media of {instanceId}</div>,
}));

globalThis.route = (name) => `/${name}`;

import InterviewInstances from './Instances';

const instance = (id, count) => ({
    id,
    created_at: '2026-10-05T16:47:00Z',
    media_count: count,
    user: { id: 1, name: 'Ana' },
});

const renderList = () =>
    render(
        <InterviewInstances
            project={{ id: 3 }}
            form={{ id: 8, name: 'Usos' }}
            instances={{ data: [instance('a', 2), instance('b', 0)] }}
        />,
    );

describe('Interviews/Instances', () => {
    it('offers the files only on interviews that have them', () => {
        renderList();

        expect(
            screen.getAllByRole('button', { name: /data\.media\.title/ }),
        ).toHaveLength(1);
        expect(screen.queryByText(/media of/)).not.toBeInTheDocument();
    });

    it('opens the files of the interview chosen', () => {
        renderList();

        fireEvent.click(
            screen.getByRole('button', { name: /data\.media\.title/ }),
        );

        expect(screen.getByText('media of a')).toBeInTheDocument();
    });
});
