import { formatRelativeTime } from '@/utils/datetime';
import { useTranslation } from 'react-i18next';

/** What the system administrators did, newest first, worded for the reader. */
export default function AdminLog({ entries }) {
    const { t, i18n } = useTranslation();

    if (entries.length === 0) {
        return (
            <p className="text-base-content/70 text-sm">
                {t('system.log.empty')}
            </p>
        );
    }

    return (
        <ul className="divide-base-300 divide-y">
            {entries.map((entry) => (
                <li
                    key={entry.id}
                    className="flex flex-wrap gap-x-3 py-2.5 text-sm"
                >
                    <span className="min-w-0 flex-1 break-words">
                        {t(`system.log.actions.${entry.action}`, {
                            ...entry.details,
                            actor: entry.actor ?? t('system.log.console'),
                        })}
                    </span>
                    <span className="text-base-content/70 whitespace-nowrap">
                        {formatRelativeTime(entry.at, i18n.language)}
                    </span>
                </li>
            ))}
        </ul>
    );
}
