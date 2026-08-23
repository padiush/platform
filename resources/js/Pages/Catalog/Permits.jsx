import Card from '@/Components/Card';
import ConfirmModal from '@/Components/ConfirmModal';
import EmptyState from '@/Components/EmptyState';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import CatalogTabs from '@/Pages/Catalog/Partials/CatalogTabs';
import PermitModal from '@/Pages/Catalog/Partials/PermitModal';
import { faArrowLeft } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

/**
 * An expiry that has passed is worth seeing, but it is a date written on the
 * permit rather than a ruling about whether a collection was lawful — so it
 * reads as a note, not an error.
 */
function Expiry({ permit }) {
    const { t } = useTranslation();

    if (!permit.expires_on) {
        return <span className="text-base-content/50">—</span>;
    }

    return (
        <span className={permit.has_expired ? 'text-warning' : undefined}>
            {permit.expires_on}
            {permit.has_expired && ` · ${t('catalogs.permits.expired')}`}
        </span>
    );
}

/**
 * The authorisations a project collects under.
 * See docs/decisions/0009-collecting-permits.md.
 */
export default function Permits({
    project,
    permits = [],
    canEdit = false,
    speciesCount = null,
}) {
    const { t } = useTranslation();
    const [adding, setAdding] = useState(false);
    const [editing, setEditing] = useState(null);
    const [pendingDelete, setPendingDelete] = useState(null);

    function doDelete() {
        router.delete(
            route('catalogs.permits.destroy', {
                project: project.id,
                permit: pendingDelete.id,
            }),
            { preserveScroll: true, onFinish: () => setPendingDelete(null) },
        );
    }

    return (
        <AuthenticatedLayout
            title={t('catalogs.permits.title')}
            breadcrumbs={[
                {
                    label: t('navigation.catalogs'),
                    href: route('catalogs.index'),
                },
                {
                    label: project.name,
                    href:
                        speciesCount === null || speciesCount > 0
                            ? route('catalogs.show', { project: project.id })
                            : undefined,
                },
                { label: t('catalogs.permits.title') },
            ]}
            subtitle={project.name}
            action={
                <Link
                    href={route('catalogs.fieldRecords.index', {
                        project: project.id,
                    })}
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
                        active="permits"
                        speciesCount={speciesCount}
                    />

                    <Card title={t('catalogs.permits.all_permits')}>
                        <p className="text-base-content/70 mb-4 text-sm">
                            {t('catalogs.permits.intro')}
                        </p>

                        {permits.length === 0 ? (
                            <EmptyState
                                title={t('catalogs.permits.none_title')}
                                hint={t('catalogs.permits.none_hint')}
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="table-sm table">
                                    <thead>
                                        <tr>
                                            <th>
                                                {t(
                                                    'catalogs.permits.authority',
                                                )}
                                            </th>
                                            <th>
                                                {t(
                                                    'catalogs.permits.reference',
                                                )}
                                            </th>
                                            <th>
                                                {t(
                                                    'catalogs.permits.issued_on',
                                                )}
                                            </th>
                                            <th>
                                                {t(
                                                    'catalogs.permits.expires_on',
                                                )}
                                            </th>
                                            <th>
                                                {t(
                                                    'catalogs.permits.fieldRecords',
                                                )}
                                            </th>
                                            {canEdit && <th />}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {permits.map((permit) => (
                                            <tr key={permit.id}>
                                                <td>{permit.authority}</td>
                                                <td className="font-mono">
                                                    {permit.reference}
                                                </td>
                                                <td>
                                                    {permit.issued_on ?? '—'}
                                                </td>
                                                <td>
                                                    <Expiry permit={permit} />
                                                </td>
                                                <td>
                                                    {permit.field_records_count}
                                                </td>
                                                {canEdit && (
                                                    <td className="text-right whitespace-nowrap">
                                                        <button
                                                            type="button"
                                                            className="btn btn-ghost btn-xs"
                                                            onClick={() =>
                                                                setEditing(
                                                                    permit,
                                                                )
                                                            }
                                                        >
                                                            {t(
                                                                'catalogs.permits.edit',
                                                            )}
                                                        </button>
                                                        <button
                                                            type="button"
                                                            className="btn btn-ghost btn-xs text-error"
                                                            onClick={() =>
                                                                setPendingDelete(
                                                                    permit,
                                                                )
                                                            }
                                                        >
                                                            {t(
                                                                'catalogs.permits.delete',
                                                            )}
                                                        </button>
                                                    </td>
                                                )}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {canEdit && (
                            <div className="mt-4">
                                <button
                                    type="button"
                                    className="btn btn-primary btn-sm"
                                    onClick={() => setAdding(true)}
                                >
                                    {t('catalogs.permits.add')}
                                </button>
                            </div>
                        )}
                    </Card>
                </div>
            </div>

            <PermitModal
                open={adding || editing !== null}
                onClose={() => {
                    setAdding(false);
                    setEditing(null);
                }}
                project={project}
                permit={editing}
                key={editing?.id ?? 'new'}
            />

            <ConfirmModal
                open={pendingDelete !== null}
                title={t('catalogs.permits.confirm_delete_title')}
                message={
                    pendingDelete?.field_records_count
                        ? t('catalogs.permits.confirm_delete_with_specimens', {
                              count: pendingDelete.field_records_count,
                          })
                        : t('catalogs.permits.confirm_delete')
                }
                onConfirm={doDelete}
                onClose={() => setPendingDelete(null)}
            />
        </AuthenticatedLayout>
    );
}
