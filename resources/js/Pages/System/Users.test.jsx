import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const mockPost = vi.fn();
const mockDelete = vi.fn();

vi.mock('@inertiajs/react', () => ({
    router: {
        post: (...args) => mockPost(...args),
        delete: (...args) => mockDelete(...args),
    },
}));
vi.mock('./Partials/SystemPage', () => ({
    default: ({ children }) => <div>{children}</div>,
}));
vi.mock('./Partials/DeleteUserModal', () => ({
    default: ({ user }) => (user ? <div role="dialog">{user.name}</div> : null),
}));

globalThis.route = (name, id) => `/${name}/${id}`;

import Users from './Users';

const people = [
    {
        id: 1,
        name: 'Rodrigo',
        email: 'rodrigo@example.org',
        system_admin: true,
        is_self: true,
        owned_projects: 1,
        member_of: 4,
        storage: 600,
        last_active_at: new Date().toISOString(),
    },
    {
        id: 2,
        name: 'Ána López',
        email: 'ana@example.org',
        system_admin: false,
        is_self: false,
        owned_projects: 3,
        member_of: 0,
        storage: 9800,
        last_active_at: new Date().toISOString(),
    },
    {
        id: 3,
        name: 'Julio',
        email: 'julio@example.org',
        system_admin: false,
        is_self: false,
        owned_projects: 0,
        member_of: 1,
        storage: 0,
        last_active_at: null,
    },
];

const invites = [
    {
        id: 9,
        invited_name: 'Carla',
        invited_email: 'carla@example.org',
        expires_at: new Date(Date.now() + 86_400_000).toISOString(),
    },
];

const rows = () => screen.getAllByRole('row').slice(1);

beforeEach(() => {
    mockPost.mockReset();
    mockDelete.mockReset();
});

describe('the users of the system', () => {
    it('lists everyone by the space they use', () => {
        render(<Users users={people} registration_invites={invites} />);

        expect(
            rows().map(
                (row) => within(row).getAllByRole('cell')[0].textContent,
            ),
        ).toEqual([
            'Ána Lópezana@example.org',
            'Rodrigosystem.users.admin_badgerodrigo@example.org',
            'Juliojulio@example.org',
        ]);
        expect(
            within(rows()[2]).getByText('system.users.never'),
        ).toBeInTheDocument();
        expect(
            within(rows()[2]).getByText('system.users.nothing_stored'),
        ).toBeInTheDocument();
    });

    it('finds people whatever the accents or case', () => {
        render(<Users users={people} registration_invites={invites} />);

        fireEvent.change(screen.getByRole('searchbox'), {
            target: { value: 'ana lopez' },
        });

        expect(rows()).toHaveLength(1);
        expect(rows()[0]).toHaveTextContent('Ána López');
    });

    it('narrows to the administrators, or to whoever has gone quiet', () => {
        render(<Users users={people} registration_invites={invites} />);
        const [show] = screen.getAllByRole('combobox');

        fireEvent.change(show, { target: { value: 'admins' } });
        expect(rows()).toHaveLength(1);
        expect(rows()[0]).toHaveTextContent('Rodrigo');

        fireEvent.change(show, { target: { value: 'inactive' } });
        expect(rows()).toHaveLength(1);
        expect(rows()[0]).toHaveTextContent('Julio');
    });

    it('never offers to delete your own account', () => {
        render(<Users users={people} registration_invites={invites} />);

        expect(within(rows()[1]).queryByRole('button')).toBeNull();
        expect(
            within(rows()[1]).getByText('system.users.you'),
        ).toBeInTheDocument();
    });

    it('opens the deletion with what goes, rather than deleting', () => {
        render(<Users users={people} registration_invites={invites} />);

        fireEvent.click(within(rows()[0]).getByRole('button'));

        expect(screen.getByRole('dialog')).toHaveTextContent('Ána López');
        expect(mockDelete).not.toHaveBeenCalled();
    });

    it('sends an invitation again or withdraws it', () => {
        render(<Users users={people} registration_invites={invites} />);

        fireEvent.click(
            screen.getByRole('button', { name: 'system.invites.resend' }),
        );
        fireEvent.click(
            screen.getByRole('button', { name: 'system.invites.withdraw' }),
        );

        expect(mockPost).toHaveBeenCalledWith(
            '/system.registration-invites.resend/9',
            {},
            expect.any(Object),
        );
        expect(mockDelete).toHaveBeenCalledWith(
            '/system.registration-invites.destroy/9',
            expect.any(Object),
        );
    });
});
