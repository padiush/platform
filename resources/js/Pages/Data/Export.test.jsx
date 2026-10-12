import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const mockGet = vi.fn();
vi.mock('axios', () => ({
    default: { get: (...args) => mockGet(...args), isCancel: () => false },
}));
vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: { csrf_token: 'token' } }),
}));
vi.mock('@/Layouts/AuthenticatedLayout', () => ({
    default: ({ children }) => <div>{children}</div>,
}));
vi.mock('@/Pages/Data/Partials/DataTabs', () => ({ default: () => null }));

globalThis.route = (name) => `/${name}`;

import DataExport from './Export';

const forms = [
    {
        id: 1,
        name: 'Usos de plantas',
        sections: [
            {
                id: 10,
                name: 'Planta',
                repeatable: false,
                items: [{ id: 100, label: 'Nombre local', type: 'text' }],
            },
        ],
    },
];

const preview = {
    columns: ['Nombre local'],
    rows: [['Ruda']],
    instance_count: 3,
    record_count: 3,
};

/** The page opened on one section, so every field in it is chosen. */
const open = () =>
    render(
        <DataExport
            project={{ id: 7 }}
            forms={forms}
            initial={{ mode: 'custom', form: 1, section: 10 }}
        />,
    );

const generate = () =>
    screen.getByRole('button', { name: /data.export.generate/ });

describe('DataExport', () => {
    beforeEach(() => {
        mockGet.mockReset();
    });

    it('drops the preview as soon as the choice no longer makes an export', async () => {
        mockGet.mockResolvedValue({ data: preview });
        open();
        expect(await screen.findByText('Ruda')).toBeInTheDocument();
        expect(generate()).toBeEnabled();

        // The species table needs a category field, which nothing has chosen.
        fireEvent.click(
            screen.getByRole('button', {
                name: 'data.export.mode_ethnobotanyr',
            }),
        );

        expect(screen.queryByText('Ruda')).toBeNull();
        expect(screen.queryByText(/data.export.custom_counts/)).toBeNull();
        expect(generate()).toBeDisabled();
    });

    it('drops a refusal along with the choice it was about', async () => {
        mockGet.mockImplementation(() =>
            Promise.reject(
                Object.assign(new Error('Unprocessable'), {
                    response: { status: 422 },
                }),
            ),
        );
        open();
        expect(
            await screen.findByText('data.export.repeatable_locked'),
        ).toBeInTheDocument();

        fireEvent.click(
            screen.getByRole('button', {
                name: 'data.export.mode_ethnobotanyr',
            }),
        );

        expect(screen.queryByText('data.export.repeatable_locked')).toBeNull();
    });

    it('asks for no preview until there is something to export', async () => {
        render(
            <DataExport
                project={{ id: 7 }}
                forms={forms}
                initial={{ mode: 'custom', form: 1 }}
            />,
        );

        await new Promise((resolve) => setTimeout(resolve, 400));
        expect(mockGet).not.toHaveBeenCalled();
        await waitFor(() => expect(generate()).toBeDisabled());
    });
});
