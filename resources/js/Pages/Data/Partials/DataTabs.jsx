import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

/**
 * The data section: the interviews' answers, linking them to species, the
 * indices computed from them, and exports. Each tab is offered only to the
 * roles that use it, which the server works out as `tabs`.
 */
export default function DataTabs({ project, active, tabs = {} }) {
    const { t } = useTranslation();

    const all = [
        { key: 'table', route: 'data.view', label: 'data.tabs.table' },
        { key: 'link', route: 'data.link', label: 'data.tabs.link' },
        { key: 'reports', route: 'data.reports', label: 'data.tabs.reports' },
        { key: 'export', route: 'data.export', label: 'data.tabs.export' },
    ].filter(({ key }) => key === 'table' || tabs[key]);

    return (
        // Wraps on a phone rather than scrolling a tab out of sight.
        <div
            role="tablist"
            className="tabs tabs-box mb-4 w-fit flex-wrap"
            data-tour="data-tabs"
        >
            {all.map((tab) =>
                tab.key === active ? (
                    <span
                        key={tab.key}
                        role="tab"
                        data-tour={`data-tab-${tab.key}`}
                        aria-current="page"
                        className="tab tab-active whitespace-nowrap"
                    >
                        {t(tab.label)}
                    </span>
                ) : (
                    <Link
                        key={tab.key}
                        role="tab"
                        data-tour={`data-tab-${tab.key}`}
                        href={route(tab.route, { project: project.id })}
                        className="tab whitespace-nowrap"
                    >
                        {t(tab.label)}
                    </Link>
                ),
            )}
        </div>
    );
}
