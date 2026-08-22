import Card from '@/Components/Card';
import EmptyState from '@/Components/EmptyState';
import FormModal from '@/Components/FormModal';
import MetricCard from '@/Components/MetricCard';
import useQueryModal from '@/Hooks/useQueryModal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import SpeciesForm from './Partials/SpeciesForm';

export default function CatalogOverview({ projects }) {
    const { t } = useTranslation();

    const [createParam, setCreate] = useQueryModal('create');
    const registering = createParam
        ? projects.find((p) => p.id === Number(createParam))
        : null;

    return (
        <AuthenticatedLayout title={t('catalogs.title')}>
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {projects.length === 0 && (
                        <EmptyState
                            title={t('hubs.empty.title_catalogs')}
                            hint={t('hubs.empty.hint_catalogs')}
                            ctaHref={route('projects.index')}
                            ctaLabel={t('hubs.empty.cta')}
                        />
                    )}
                    {projects.length > 0 && (
                        <div className="grid w-full grid-cols-1 gap-4">
                            {projects.map((project) => (
                                <Card key={project.id} title={project.name}>
                                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                        <MetricCard
                                            label={t('catalogs.total_species')}
                                            value={
                                                project.catalog_species_count
                                            }
                                            tone="primary"
                                        />
                                        <MetricCard
                                            label={t(
                                                'catalogs.reported_species',
                                            )}
                                            value={project.linked_species_count}
                                        />
                                        <MetricCard
                                            label={t(
                                                'catalogs.reported_families',
                                            )}
                                            value={
                                                project.linked_families_count
                                            }
                                        />
                                    </div>

                                    <div className="mt-4 flex flex-wrap justify-end gap-2">
                                        {project.can_edit_catalog ? (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setCreate(project.id)
                                                }
                                                className="btn btn-outline btn-primary btn-sm"
                                            >
                                                {t('catalogs.register_species')}
                                            </button>
                                        ) : (
                                            <span
                                                className="btn btn-outline btn-sm btn-disabled"
                                                aria-disabled="true"
                                            >
                                                {t('catalogs.register_species')}
                                            </span>
                                        )}

                                        <Link
                                            href={route(
                                                'catalogs.specimens.index',
                                                { project: project.id },
                                            )}
                                            className="btn btn-outline btn-sm"
                                        >
                                            {t('catalogs.specimens.title')}
                                            {project.specimen_count > 0
                                                ? ` (${project.specimen_count})`
                                                : ''}
                                        </Link>

                                        {project.can_view_catalog &&
                                        project.catalog_species_count > 0 ? (
                                            <Link
                                                href={route('catalogs.show', {
                                                    project: project.id,
                                                })}
                                                className="btn btn-primary btn-sm"
                                            >
                                                {t('catalogs.view_catalog')}
                                            </Link>
                                        ) : (
                                            <span
                                                className="btn btn-sm btn-disabled"
                                                aria-disabled="true"
                                            >
                                                {t('catalogs.view_catalog')}
                                            </span>
                                        )}
                                    </div>
                                </Card>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            <FormModal
                open={!!registering}
                onClose={() => setCreate(null)}
                title={
                    registering
                        ? `${t('catalogs.register_species')} — ${registering.name}`
                        : t('catalogs.register_species')
                }
            >
                {registering && (
                    <SpeciesForm
                        key={createParam}
                        project={registering}
                        onClose={() => setCreate(null)}
                    />
                )}
            </FormModal>
        </AuthenticatedLayout>
    );
}
