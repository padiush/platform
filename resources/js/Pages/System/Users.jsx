import Card from '@/Components/Card';
import { formatBytes } from '@/utils/bytes';
import { formatRelativeTime } from '@/utils/datetime';
import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import DeleteUserModal from './Partials/DeleteUserModal';
import SystemPage from './Partials/SystemPage';
import UsageBar from './Partials/UsageBar';

const INACTIVE_DAYS = 90;

/** Folded for searching: case and accents do not matter. */
const fold = (text) =>
    (text ?? '')
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .toLowerCase();

/**
 * Everyone with an account: what they own and belong to, how much their
 * projects store, when they were last seen, and the way to remove them —
 * after seeing what goes with them. Then the invitations still open.
 */
export default function SystemUsers({ users, registration_invites }) {
    const { t, i18n } = useTranslation();
    const [query, setQuery] = useState('');
    const [filter, setFilter] = useState('all');
    const [sort, setSort] = useState('storage');
    const [deleting, setDeleting] = useState(null);

    const size = (bytes) => formatBytes(bytes, i18n.language);
    const largest = Math.max(0, ...users.map((user) => user.storage));

    const shown = useMemo(() => {
        const needle = fold(query.trim());
        const cutoff = Date.now() - INACTIVE_DAYS * 86_400_000;

        return users
            .filter(
                (user) =>
                    !needle ||
                    fold(user.name).includes(needle) ||
                    fold(user.email).includes(needle),
            )
            .filter((user) => {
                if (filter === 'admins') return user.system_admin;
                if (filter === 'inactive') {
                    return (
                        !user.last_active_at ||
                        new Date(user.last_active_at).getTime() < cutoff
                    );
                }
                return true;
            })
            .sort((a, b) => {
                if (sort === 'activity') {
                    return (b.last_active_at ?? '').localeCompare(
                        a.last_active_at ?? '',
                    );
                }
                if (sort === 'name') {
                    return a.name.localeCompare(b.name, i18n.language);
                }
                return b.storage - a.storage;
            });
    }, [users, query, filter, sort, i18n.language]);

    return (
        <SystemPage tab="users">
            <Card
                title={t('system.users.heading', { count: users.length })}
                actions={
                    <div className="flex flex-wrap items-end gap-2">
                        <label className="flex flex-col gap-1 text-sm font-semibold">
                            {t('system.users.search')}
                            <input
                                type="search"
                                className="input input-sm w-56"
                                placeholder={t(
                                    'system.users.search_placeholder',
                                )}
                                value={query}
                                onChange={(event) =>
                                    setQuery(event.target.value)
                                }
                            />
                        </label>
                        <label className="flex flex-col gap-1 text-sm font-semibold">
                            {t('system.users.show')}
                            <select
                                className="select select-sm"
                                value={filter}
                                onChange={(event) =>
                                    setFilter(event.target.value)
                                }
                            >
                                <option value="all">
                                    {t('system.users.filter_all')}
                                </option>
                                <option value="admins">
                                    {t('system.users.filter_admins')}
                                </option>
                                <option value="inactive">
                                    {t('system.users.filter_inactive')}
                                </option>
                            </select>
                        </label>
                        <label className="flex flex-col gap-1 text-sm font-semibold">
                            {t('system.users.sort')}
                            <select
                                className="select select-sm"
                                value={sort}
                                onChange={(event) =>
                                    setSort(event.target.value)
                                }
                            >
                                <option value="storage">
                                    {t('system.users.sort_storage')}
                                </option>
                                <option value="activity">
                                    {t('system.users.sort_activity')}
                                </option>
                                <option value="name">
                                    {t('system.users.sort_name')}
                                </option>
                            </select>
                        </label>
                    </div>
                }
            >
                {shown.length === 0 ? (
                    <p className="text-base-content/70 text-sm">
                        {t('system.users.no_match')}
                    </p>
                ) : (
                    <>
                        {/* Narrow screens: one stacked row per person, the action in reach. */}
                        <ul className="divide-base-300 divide-y lg:hidden">
                            {shown.map((user) => (
                                <li
                                    key={user.id}
                                    className="flex flex-wrap items-start gap-x-3 gap-y-2 py-3"
                                >
                                    <div className="min-w-0 flex-1 basis-56">
                                        <Person user={user} t={t} />
                                    </div>
                                    <Remove
                                        user={user}
                                        t={t}
                                        onRemove={setDeleting}
                                    />
                                    <dl className="text-base-content/80 grid w-full grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm">
                                        <dt className="text-base-content/70">
                                            {t('system.users.col_projects')}
                                        </dt>
                                        <dd>{projectsLine(user, t)}</dd>
                                        <dt className="text-base-content/70">
                                            {t('system.users.col_storage')}
                                        </dt>
                                        <dd>
                                            <Storage
                                                user={user}
                                                largest={largest}
                                                size={size}
                                                t={t}
                                            />
                                        </dd>
                                        <dt className="text-base-content/70">
                                            {t('system.users.col_activity')}
                                        </dt>
                                        <dd>{seen(user, t, i18n.language)}</dd>
                                    </dl>
                                </li>
                            ))}
                        </ul>

                        <div className="hidden overflow-x-auto lg:block">
                            <table className="table">
                                <thead>
                                    <tr>
                                        <th scope="col">
                                            {t('system.users.col_person')}
                                        </th>
                                        <th scope="col">
                                            {t('system.users.col_projects')}
                                        </th>
                                        <th scope="col">
                                            {t('system.users.col_storage')}
                                        </th>
                                        <th scope="col">
                                            {t('system.users.col_activity')}
                                        </th>
                                        <th scope="col">
                                            <span className="sr-only">
                                                {t('system.users.col_actions')}
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {shown.map((user) => (
                                        <tr key={user.id}>
                                            <td>
                                                <Person user={user} t={t} />
                                            </td>
                                            <td className="text-sm">
                                                {projectsLine(user, t)}
                                            </td>
                                            <td>
                                                <Storage
                                                    user={user}
                                                    largest={largest}
                                                    size={size}
                                                    t={t}
                                                />
                                            </td>
                                            <td className="text-sm whitespace-nowrap">
                                                {seen(user, t, i18n.language)}
                                            </td>
                                            <td className="text-right">
                                                <Remove
                                                    user={user}
                                                    t={t}
                                                    onRemove={setDeleting}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}
                <p className="text-base-content/70 text-sm">
                    {t('system.users.note')}
                </p>
            </Card>

            <Card title={t('system.invites.title')}>
                {registration_invites.length === 0 ? (
                    <p className="text-base-content/70 text-sm">
                        {t('system.invites.none')}
                    </p>
                ) : (
                    <ul className="divide-base-300 divide-y">
                        {registration_invites.map((invite) => (
                            <li
                                key={invite.id}
                                className="flex flex-wrap items-center gap-x-3 gap-y-1 py-2"
                            >
                                <span className="min-w-0 flex-1 text-sm break-words">
                                    <span className="font-semibold">
                                        {invite.invited_name}
                                    </span>
                                    {' · '}
                                    {invite.invited_email}
                                </span>
                                <span className="text-base-content/70 text-sm">
                                    {t('system.invites.expires', {
                                        when: formatRelativeTime(
                                            invite.expires_at,
                                            i18n.language,
                                        ),
                                    })}
                                </span>
                                <button
                                    type="button"
                                    className="btn btn-ghost btn-sm"
                                    onClick={() =>
                                        router.post(
                                            route(
                                                'system.registration-invites.resend',
                                                invite.id,
                                            ),
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    {t('system.invites.resend')}
                                </button>
                                <button
                                    type="button"
                                    className="btn btn-ghost btn-sm text-error"
                                    onClick={() =>
                                        router.delete(
                                            route(
                                                'system.registration-invites.destroy',
                                                invite.id,
                                            ),
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    {t('system.invites.withdraw')}
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>

            <DeleteUserModal
                user={deleting}
                people={users}
                onClose={() => setDeleting(null)}
            />
        </SystemPage>
    );
}

function Person({ user, t }) {
    return (
        <>
            <div className="font-semibold break-words">
                {user.name}
                {user.system_admin && (
                    <span className="badge badge-primary badge-soft badge-sm ml-2 align-middle">
                        {t('system.users.admin_badge')}
                    </span>
                )}
            </div>
            <div className="text-base-content/70 text-sm [overflow-wrap:anywhere]">
                {user.email}
            </div>
        </>
    );
}

function Storage({ user, largest, size, t }) {
    if (user.storage === 0) {
        return (
            <span className="text-base-content/70 text-sm">
                {t('system.users.nothing_stored')}
            </span>
        );
    }

    return (
        <div className="flex items-center gap-2">
            <UsageBar value={user.storage} max={largest} className="w-24" />
            <span className="text-sm font-bold whitespace-nowrap">
                {size(user.storage)}
            </span>
        </div>
    );
}

function Remove({ user, t, onRemove }) {
    if (user.is_self) {
        return (
            <span className="text-base-content/70 text-sm">
                {t('system.users.you')}
            </span>
        );
    }

    return (
        <button
            type="button"
            className="btn btn-ghost btn-sm text-error"
            onClick={() => onRemove(user)}
        >
            {t('system.users.delete')}
            <span className="sr-only">{user.name}</span>
        </button>
    );
}

function seen(user, t, locale) {
    return user.last_active_at
        ? formatRelativeTime(user.last_active_at, locale)
        : t('system.users.never');
}

function projectsLine(user, t) {
    const parts = [];
    if (user.owned_projects > 0) {
        parts.push(t('system.users.owned', { count: user.owned_projects }));
    }
    if (user.member_of > 0) {
        parts.push(t('system.users.member_of', { count: user.member_of }));
    }
    return parts.length ? parts.join(' · ') : t('system.users.no_projects');
}
