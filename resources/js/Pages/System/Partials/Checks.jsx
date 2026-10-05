import { formatBytes } from '@/utils/bytes';
import { formatRelativeTime } from '@/utils/datetime';
import { useTranslation } from 'react-i18next';
import StatusChip from './StatusChip';

/**
 * The upkeep the platform depends on, each with how it stands and the figure
 * that says why. `only` limits it to some checks.
 */
export default function Checks({ checks, only = null }) {
    const { t, i18n } = useTranslation();
    const when = (iso) => formatRelativeTime(iso, i18n.language);

    const rows = {
        scheduler: {
            title: t('system.checks.scheduler'),
            detail: checks.scheduler.ran_at
                ? t('system.checks.scheduler_ran', {
                      when: when(checks.scheduler.ran_at),
                  })
                : t('system.checks.scheduler_never'),
        },
        uploads: {
            title: t('system.checks.uploads'),
            detail:
                checks.uploads.count > 0
                    ? t('system.checks.uploads_some', {
                          count: checks.uploads.count,
                          when: when(checks.uploads.oldest),
                      })
                    : t('system.checks.uploads_none'),
        },
        waiting: {
            title: t('system.checks.waiting'),
            detail:
                checks.waiting.count > 0
                    ? t('system.checks.waiting_some', {
                          count: checks.waiting.count,
                      })
                    : t('system.checks.waiting_none'),
        },
        orphans: {
            title: t('system.checks.orphans'),
            detail: orphanDetail(checks.orphans, t, when, i18n.language),
            command: true,
        },
    };

    const keys = only ?? Object.keys(rows);

    return (
        <ul className="divide-base-300 divide-y">
            {keys.map((key) => (
                <li key={key} className="flex items-start gap-3 py-2.5">
                    <StatusChip status={checks[key].status} />
                    <div className="min-w-0">
                        <div className="text-sm font-semibold">
                            {rows[key].title}
                        </div>
                        <div className="text-base-content/70 text-sm break-words">
                            {rows[key].detail}
                        </div>
                    </div>
                </li>
            ))}
        </ul>
    );
}

function orphanDetail(orphans, t, when, locale) {
    if (!orphans.ran_at) {
        return t('system.checks.orphans_never');
    }

    if (orphans.found === 0) {
        return t('system.checks.orphans_clean', { when: when(orphans.ran_at) });
    }

    return t(
        orphans.deleted
            ? 'system.checks.orphans_deleted'
            : 'system.checks.orphans_found',
        {
            count: orphans.found,
            size: formatBytes(orphans.bytes, locale),
            when: when(orphans.ran_at),
        },
    );
}
