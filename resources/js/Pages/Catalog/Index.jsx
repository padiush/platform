import Card from '@/Components/Card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function CatalogOverview({ projects, auth }) {
    const { t } = useTranslation();

    return (
        <AuthenticatedLayout title={t('catalogs.title')}>
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
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

                                    <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <Link
                                            href={route(
                                                'catalogs.species.register',
                                                { project: project.id },
                                            )}
                                            className="btn btn-primary w-full"
                                            disabled={!project.can_edit_catalog}
                                        >
                                            {t('catalogs.register_species')}
                                        </Link>

                                        <Link
                                            href={route('catalogs.show', {
                                                project: project.id,
                                            })}
                                            className="btn btn-primary w-full"
                                            disabled={
                                                !project.can_view_catalog ||
                                                project.catalog_species_count ===
                                                    0
                                            }
                                        >
                                            {t('catalogs.view_catalog')}
                                        </Link>
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
