import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    // eslint-disable-next-line no-unused-vars
    Link: ({ children, href, ...rest }) => (
        <a href={href} {...rest}>
            {children}
        </a>
    ),
}));
vi.mock('./Partials/SystemPage', () => ({
    default: ({ children }) => <div>{children}</div>,
}));

globalThis.route = (name) => `/${name}`;

import Index from './Index';

const props = {
    counts: {
        users: 42,
        active_users: 17,
        invites: 3,
        projects: 18,
        finished_projects: 5,
    },
    storage: { audio: 3000, photo: 500, files: 3, total: 3500, recent: 100 },
    version: '1.1.0',
    pending_migrations: 0,
    checks: {
        scheduler: { status: 'ok', ran_at: new Date().toISOString() },
        uploads: { status: 'ok', count: 0, oldest: null },
        waiting: { status: 'ok', count: 0 },
        orphans: {
            status: 'warn',
            ran_at: null,
            found: null,
            bytes: null,
            deleted: false,
        },
    },
    top_owners: [{ id: 2, name: 'Ana', total: 3500 }],
    log: [
        {
            id: 2,
            actor: null,
            action: 'admin.promoted',
            details: { name: 'Sara' },
            at: new Date().toISOString(),
        },
        {
            id: 1,
            actor: 'Rodrigo',
            action: 'invite.sent',
            details: { email: 'carla@example.org' },
            at: new Date().toISOString(),
        },
    ],
};

describe('the system summary', () => {
    it('gives the totals', () => {
        render(<Index {...props} />);

        expect(screen.getByText('42')).toBeInTheDocument();
        expect(screen.getByText('18')).toBeInTheDocument();
        // The total, and Ana's share of it.
        expect(screen.getAllByText('3.4 KB')).toHaveLength(2);
        expect(
            screen.getByText('system.stats.migrations_ok'),
        ).toBeInTheDocument();
    });

    it('says how the upkeep stands', () => {
        render(<Index {...props} />);

        // The scheduler and the uploads are fine; nobody has looked for orphans.
        expect(screen.getAllByText('system.checks.ok')).toHaveLength(2);
        expect(screen.getByText('system.checks.warn')).toBeInTheDocument();
        expect(
            screen.getByText('system.checks.orphans_never'),
        ).toBeInTheDocument();
    });

    it('words what the administrators did, and who did it', () => {
        render(<Index {...props} />);

        expect(
            screen.getByText(/system\.log\.actions\.admin\.promoted/),
        ).toHaveTextContent('"actor":"system.log.console"');
        expect(
            screen.getByText(/system\.log\.actions\.invite\.sent/),
        ).toHaveTextContent('"actor":"Rodrigo"');
    });
});
