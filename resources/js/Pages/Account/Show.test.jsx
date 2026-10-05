import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

const routerDelete = vi.fn();

vi.mock('@inertiajs/react', () => ({
    router: { delete: (...args) => routerDelete(...args) },
    useForm: (initial) => ({
        data: initial,
        setData: vi.fn(),
        delete: vi.fn(),
        reset: vi.fn(),
        processing: false,
        errors: {},
    }),
}));

vi.mock('@/Layouts/AuthenticatedLayout', () => ({
    default: ({ children }) => <div>{children}</div>,
}));

globalThis.route = (name, params) =>
    params ? `/${name}/${Object.values(params).join('/')}` : `/${name}`;

import Show from './Show';

const account = { name: 'Equipo Padiush', email: 'demo@padiush.test' };
const thisOne = {
    key: 'k1',
    browser: 'Safari',
    platform: 'macOS',
    ip_address: '203.0.113.7',
    last_active: new Date().toISOString(),
    current: true,
};
const phone = {
    ...thisOne,
    key: 'k2',
    browser: 'Chrome',
    platform: 'Android',
    current: false,
};

describe('Account/Show', () => {
    it('marks this browser and offers to sign out only the others', () => {
        render(<Show account={account} sessions={[thisOne, phone]} />);

        expect(
            screen.getByText('account.sessions.this_browser'),
        ).toBeInTheDocument();
        expect(
            screen.getAllByRole('button', {
                name: 'account.sessions.sign_out',
            }),
        ).toHaveLength(1);
        expect(
            screen.getByRole('button', {
                name: 'account.sessions.sign_out_others',
            }),
        ).toBeInTheDocument();
    });

    it('signs one browser out by its key, after confirming', () => {
        render(<Show account={account} sessions={[thisOne, phone]} />);

        fireEvent.click(
            screen.getByRole('button', { name: 'account.sessions.sign_out' }),
        );
        // The confirmation's own button.
        fireEvent.click(
            screen.getAllByRole('button', {
                name: 'account.sessions.sign_out',
                hidden: true,
            })[1],
        );

        expect(routerDelete).toHaveBeenCalledWith(
            '/account.sessions.destroy/k2',
            expect.any(Object),
        );
    });

    it('offers nothing to sign out when this is the only browser', () => {
        render(<Show account={account} sessions={[thisOne]} />);

        expect(
            screen.queryByRole('button', {
                name: 'account.sessions.sign_out_others',
            }),
        ).not.toBeInTheDocument();
    });

    it('says when sessions cannot be listed', () => {
        render(<Show account={account} sessions={null} />);

        expect(
            screen.getByText('account.sessions.unavailable'),
        ).toBeInTheDocument();
    });

    it('lists the field app devices to revoke', () => {
        render(
            <Show
                account={account}
                sessions={[thisOne]}
                devices={[
                    {
                        id: 9,
                        name: 'Galaxy Z Fold 5',
                        created_at: '2026-10-01T10:00:00Z',
                        last_used_at: null,
                    },
                ]}
            />,
        );

        expect(screen.getByText('Galaxy Z Fold 5')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'account.devices.revoke' }),
        ).toBeInTheDocument();
    });

    it('says when no device has signed in', () => {
        render(<Show account={account} sessions={[thisOne]} devices={[]} />);

        expect(
            screen.getByText('account.devices.none_title'),
        ).toBeInTheDocument();
    });
});
