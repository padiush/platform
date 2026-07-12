import Card from '@/Components/Card';
import EmptyState from '@/Components/EmptyState';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function CatalogOverview({ projects }) {
    const { t } = useTranslation();

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
                                    <div className="stats stats-vertical lg:stats-horizontal bg-base-300">
                                        <div className="stat">
                                            <div className="stat-value">
                                                {project.catalog_species_count}
                                            </div>
                                            <div className="stat-title">
                                                {t('catalogs.total_species')}
                                            </div>
                                        </div>
                                        <div className="stat">
                                            <div className="stat-value">
                                                {project.linked_species_count}
                                            </div>
                                            <div className="stat-title">
                                                {t('catalogs.reported_species')}
                                            </div>
                                        </div>
                                        <div className="stat">
                                            <div className="stat-value">
                                                {project.linked_families_count}
                                            </div>
                                            <div className="stat-title">
                                                {t(
                                                    'catalogs.reported_families',
                                                )}
                                            </div>
                                        </div>
                                    </div>

                                    <div className="mt-4 flex flex-wrap justify-end gap-2">
                                        {project.can_edit_catalog ? (
                                            <Link
                                                href={route(
                                                    'catalogs.species.register',
                                                    { project: project.id },
                                                )}
                                                className="btn btn-outline btn-primary btn-sm"
                                            >
                                                {t('catalogs.register_species')}
                                            </Link>
                                        ) : (
                                            <span
                                                className="btn btn-outline btn-sm btn-disabled"
                                                aria-disabled="true"
                                            >
                                                {t('catalogs.register_species')}
                                            </span>
                                        )}

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
        </AuthenticatedLayout>
    );
}
