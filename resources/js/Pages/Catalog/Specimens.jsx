import Card from '@/Components/Card';
import ConfirmModal from '@/Components/ConfirmModal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import CatalogTabs from '@/Pages/Catalog/Partials/CatalogTabs';
import {
    CollectionModal,
    DepositModal,
    DetermineModal,
} from '@/Pages/Catalog/Partials/SpecimenModals';
import SpecimenTable from '@/Pages/Catalog/Partials/SpecimenTable';
import { faArrowLeft, faDownload } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';

const FILTERS = ['all', 'undetermined', 'unvouchered'];

function Summary({ summary }) {
    const { t } = useTranslation();

    return (
        <div className="stats stats-vertical sm:stats-horizontal bg-base-200/40 w-full">
            <div className="stat">
                <div className="stat-title">
                    {t('catalogs.specimens.stat_total')}
                </div>
                <div className="stat-value text-2xl">{summary.total}</div>
            </div>
            <div className="stat">
                <div className="stat-title">
                    {t('catalogs.specimens.stat_vouchered')}
                </div>
                <div className="stat-value text-2xl">{summary.vouchered}</div>
                <div className="stat-desc">
                    {t('catalogs.specimens.coverage', {
                        vouchered: summary.vouchered,
                        total: summary.total,
                    })}
                </div>
            </div>
            <div className="stat">
                <div className="stat-title">
                    {t('catalogs.specimens.stat_unidentified')}
                </div>
                <div className="stat-value text-2xl">
                    {summary.unidentified}
                </div>
                <div className="stat-desc">
                    {t('catalogs.specimens.stat_unidentified_hint')}
                </div>
            </div>
        </div>
    );
}

/**
 * Every collection the project has made, identified or not.
 *
 * This is the primary way in: fieldwork records a specimen long before anyone
 * names it, so the list is not scoped to a taxon and creating one asks nothing
 * about taxonomy. See docs/decisions/0008-specimens-and-determinations.md.
 */
export default function Specimens({
    project,
    specimens = [],
    summary,
    catalog = [],
    canEdit = false,
    nextAccessionNumber = null,
    speciesCount = null,
}) {
    const { t } = useTranslation();
    const [filter, setFilter] = useState('all');
    const [collecting, setCollecting] = useState(false);
    const [editing, setEditing] = useState(null);
    const [determining, setDetermining] = useState(null);
    const [depositing, setDepositing] = useState(null);
    const [pendingDelete, setPendingDelete] = useState(null);

    const shown = useMemo(() => {
        if (filter === 'undetermined') {
            return specimens.filter((s) => !s.species);
        }
        if (filter === 'unvouchered') {
            return specimens.filter((s) => !s.is_vouchered);
        }
        return specimens;
    }, [specimens, filter]);

    function doDelete() {
        router.delete(
            route('catalogs.specimens.destroy', {
                project: project.id,
                specimen: pendingDelete.id,
            }),
            { preserveScroll: true, onFinish: () => setPendingDelete(null) },
        );
    }

    return (
        <AuthenticatedLayout
            title={t('catalogs.specimens.title')}
            breadcrumbs={[
                {
                    label: t('navigation.catalogs'),
                    href: route('catalogs.index'),
                },
                {
                    label: project.name,
                    // Same reason the species tab is disabled when the catalog
                    // is empty: catalogs.show bounces straight back.
                    href:
                        speciesCount === null || speciesCount > 0
                            ? route('catalogs.show', { project: project.id })
                            : undefined,
                },
                { label: t('catalogs.specimens.title') },
            ]}
            subtitle={project.name}
            action={
                <Link
                    href={route('catalogs.show', { project: project.id })}
                    className="btn btn-ghost btn-circle"
                    aria-label={t('navigation.back')}
                >
                    <FontAwesomeIcon icon={faArrowLeft} />
                </Link>
            }
        >
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    <CatalogTabs
                        project={project}
                        active="specimens"
                        speciesCount={speciesCount}
                    />

                    <Summary summary={summary} />

                    <Card title={t('catalogs.specimens.all_collections')}>
                        <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div role="tablist" className="tabs tabs-box">
                                {FILTERS.map((key) => (
                                    <button
                                        key={key}
                                        type="button"
                                        role="tab"
                                        className={`tab ${filter === key ? 'tab-active' : ''}`}
                                        onClick={() => setFilter(key)}
                                    >
                                        {t(`catalogs.specimens.filter_${key}`)}
                                    </button>
                                ))}
                            </div>

                            <div className="flex items-center gap-2">
                                {specimens.length > 0 && (
                                    <div className="dropdown dropdown-end">
                                        <div
                                            tabIndex={0}
                                            role="button"
                                            className="btn btn-outline btn-sm"
                                        >
                                            <FontAwesomeIcon
                                                icon={faDownload}
                                            />
                                            {t('catalogs.specimens.export')}
                                        </div>
                                        <ul
                                            tabIndex={0}
                                            className="dropdown-content menu bg-base-200 rounded-box z-10 w-52 p-2 shadow"
                                        >
                                            {['xlsx', 'csv'].map((format) => (
                                                <li key={format}>
                                                    <a
                                                        href={route(
                                                            'catalogs.specimens.export',
                                                            {
                                                                project:
                                                                    project.id,
                                                                format,
                                                            },
                                                        )}
                                                    >
                                                        {t(
                                                            `catalogs.specimens.format_${format}`,
                                                        )}
                                                    </a>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                                {canEdit && (
                                    <button
                                        type="button"
                                        className="btn btn-primary btn-sm"
                                        onClick={() => setCollecting(true)}
                                    >
                                        {t('catalogs.specimens.add')}
                                    </button>
                                )}
                            </div>
                        </div>

                        <SpecimenTable
                            specimens={shown}
                            canEdit={canEdit}
                            onEdit={setEditing}
                            onDetermine={setDetermining}
                            onDeposit={setDepositing}
                            onDelete={setPendingDelete}
                            emptyTitle={t('catalogs.specimens.none_title')}
                            emptyHint={t('catalogs.specimens.none_hint')}
                        />
                    </Card>
                </div>
            </div>

            <CollectionModal
                open={collecting || editing !== null}
                onClose={() => {
                    setCollecting(false);
                    setEditing(null);
                }}
                project={project}
                specimen={editing}
                key={editing?.id ?? 'new'}
            />

            {determining && (
                <DetermineModal
                    open
                    onClose={() => setDetermining(null)}
                    project={project}
                    specimen={determining}
                    catalog={catalog}
                />
            )}

            {depositing && (
                <DepositModal
                    open
                    onClose={() => setDepositing(null)}
                    project={project}
                    specimen={depositing}
                    nextAccessionNumber={nextAccessionNumber}
                />
            )}

            <ConfirmModal
                open={pendingDelete !== null}
                title={t('catalogs.specimens.confirm_delete_title')}
                message={t('catalogs.specimens.confirm_delete')}
                onConfirm={doDelete}
                onClose={() => setPendingDelete(null)}
            />
        </AuthenticatedLayout>
    );
}
