import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

/**
 * The field-records section: what was recorded, and the permits it was
 * collected under. Permits sit here rather than in the catalog because they
 * are chosen when a record is made and say nothing about a taxon.
 * See docs/decisions/0009-collecting-permits.md.
 */
export default function RecordTabs({ project, active }) {
    const { t } = useTranslation();

    const tabs = [
        {
            key: 'records',
            label: t('records.tab_records'),
            href: route('catalogs.fieldRecords.index', { project: project.id }),
        },
        {
            key: 'permits',
            label: t('catalogs.permits.title'),
            href: route('catalogs.permits.index', { project: project.id }),
        },
    ];

    return (
        <div role="tablist" className="tabs tabs-box mb-4 w-fit">
            {tabs.map((tab) =>
                tab.key === active ? (
                    <span
                        key={tab.key}
                        role="tab"
                        aria-current="page"
                        className="tab tab-active"
                    >
                        {tab.label}
                    </span>
                ) : (
                    <Link
                        key={tab.key}
                        role="tab"
                        href={tab.href}
                        className="tab"
                    >
                        {tab.label}
                    </Link>
                ),
            )}
        </div>
    );
}
