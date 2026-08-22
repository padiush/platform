import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

/**
 * The two faces of a project's catalog: the taxa, and the physical collections.
 *
 * They are peers rather than one nested in the other — a specimen is recorded
 * before anything is known about its taxonomy, so reaching collections must not
 * require going through a taxon first.
 * See docs/decisions/0008-specimens-and-determinations.md.
 */
export default function CatalogTabs({ project, active, speciesCount = null }) {
    const { t } = useTranslation();

    // catalogs.show bounces back to the index when no species exist, so
    // offering the tab there would be a link to nowhere — which is most
    // visible on exactly the project that has specimens and no taxa yet.
    const speciesReachable = speciesCount === null || speciesCount > 0;

    const tabs = [
        {
            key: 'species',
            label: t('catalogs.species_list'),
            href: route('catalogs.show', { project: project.id }),
        },
        {
            key: 'specimens',
            label: t('catalogs.specimens.title'),
            href: route('catalogs.specimens.index', { project: project.id }),
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
                tab.key === 'species' && !speciesReachable ? (
                    <span
                        key={tab.key}
                        role="tab"
                        aria-disabled="true"
                        className="tab tab-disabled opacity-50"
                        title={t('catalogs.specimens.no_species_yet')}
                    >
                        {tab.label}
                    </span>
                ) : tab.key === active ? (
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
