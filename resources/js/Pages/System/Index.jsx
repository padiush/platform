import Card from '@/Components/Card';
import { formatBytes } from '@/utils/bytes';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AdminLog from './Partials/AdminLog';
import Checks from './Partials/Checks';
import SystemPage from './Partials/SystemPage';
import UsageBar from './Partials/UsageBar';

/**
 * The system panel's summary: how many people and projects there are, how
 * much they store, whether the upkeep is running, who stores the most, and
 * what the administrators did.
 */
export default function SystemIndex({
    counts,
    storage,
    version,
    pending_migrations,
    checks,
    top_owners,
    log,
}) {
    const { t, i18n } = useTranslation();
    const size = (bytes) => formatBytes(bytes, i18n.language);
    const largest = top_owners[0]?.total ?? 0;

    return (
        <SystemPage tab="summary">
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Stat
                    label={t('system.stats.users')}
                    value={counts.users}
                    detail={t('system.stats.users_detail', {
                        active: counts.active_users,
                        invites: counts.invites,
                    })}
                />
                <Stat
                    label={t('system.stats.projects')}
                    value={counts.projects}
                    detail={t('system.stats.projects_detail', {
                        ongoing: counts.projects - counts.finished_projects,
                        finished: counts.finished_projects,
                    })}
                />
                <Stat
                    label={t('system.stats.storage')}
                    value={size(storage.total)}
                    detail={t('system.stats.storage_detail', {
                        audio: size(storage.audio),
                        photo: size(storage.photo),
                    })}
                />
                <Stat
                    label={t('system.stats.version')}
                    value={version}
                    detail={
                        pending_migrations > 0
                            ? t('system.stats.migrations_pending', {
                                  count: pending_migrations,
                              })
                            : t('system.stats.migrations_ok')
                    }
                />
            </div>

            <div className="grid grid-cols-1 items-start gap-4 lg:grid-cols-3">
                <Card title={t('system.checks.title')}>
                    <Checks
                        checks={checks}
                        only={['scheduler', 'uploads', 'orphans']}
                    />
                </Card>

                <Card
                    title={t('system.top.title')}
                    actions={
                        <Link
                            href={route('system.storage')}
                            className="link link-primary text-sm font-semibold"
                        >
                            {t('system.top.all')}
                        </Link>
                    }
                >
                    {top_owners.length === 0 ? (
                        <p className="text-base-content/70 text-sm">
                            {t('system.top.empty')}
                        </p>
                    ) : (
                        <ul className="divide-base-300 divide-y">
                            {top_owners.map((owner) => (
                                <li
                                    key={owner.id}
                                    className="flex items-center gap-3 py-2.5 text-sm"
                                >
                                    <span className="w-32 min-w-0 truncate font-semibold sm:w-40">
                                        {owner.name}
                                    </span>
                                    <UsageBar
                                        value={owner.total}
                                        max={largest}
                                        className="min-w-12 flex-1"
                                    />
                                    <span className="w-20 text-right font-bold whitespace-nowrap">
                                        {size(owner.total)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                    <p className="text-base-content/70 text-sm">
                        {t('system.top.note')}
                    </p>
                </Card>

                <Card title={t('system.log.title')}>
                    <AdminLog entries={log} />
                    <p className="text-base-content/70 text-sm">
                        {t('system.log.note')}
                    </p>
                </Card>
            </div>
        </SystemPage>
    );
}

function Stat({ label, value, detail }) {
    return (
        <Card sameHeight className="h-full">
            <p className="text-base-content/70 text-xs font-semibold tracking-[0.14em] uppercase">
                {label}
            </p>
            <p className="text-3xl font-extrabold break-words">{value}</p>
            <p className="text-base-content/70 text-sm">{detail}</p>
        </Card>
    );
}
