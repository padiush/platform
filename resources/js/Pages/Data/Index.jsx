import Card from '@/Components/Card';
import EmptyState from '@/Components/EmptyState';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function DataProcessing({ projects }) {
    const { t } = useTranslation();

    return (
        <AuthenticatedLayout title={t('data.data_processing')}>
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {projects.length === 0 && (
                        <EmptyState
                            title={t('hubs.empty.title_data')}
                            hint={t('hubs.empty.hint_data')}
                            ctaHref={route('projects.index')}
                            ctaLabel={t('hubs.empty.cta')}
                        />
                    )}
                    {projects.length > 0 && (
                        <div className="grid w-full grid-cols-1 gap-4">
                            {projects.map((project) => {
                                const capabilities = project.capabilities || {};
                                const canManageData = capabilities.manage_data;
                                const canGenerateReports =
                                    capabilities.generate_reports;
                                const unlinkedCount =
                                    project.unlinked_count ?? 0;
                                const linkedCount = project.linked_count ?? 0;

                                return (
                                    <Card key={project.id} title={project.name}>
                                        <div className="stats stats-vertical lg:stats-horizontal bg-base-300">
                                            <div className="stat">
                                                <div className="stat-value">
                                                    {unlinkedCount}
                                                </div>
                                                <div className="stat-title">
                                                    {t('data.unlinked_reports')}
                                                </div>
                                            </div>
                                            <div className="stat">
                                                <div className="stat-value">
                                                    {linkedCount}
                                                </div>
                                                <div className="stat-title">
                                                    {t('data.linked_reports')}
                                                </div>
                                            </div>
                                        </div>

                                        <div className="mt-4 grid w-full grid-cols-1 gap-4 lg:grid-cols-3">
                                            {canManageData ? (
                                                <Link
                                                    href={route('data.link', {
                                                        project: project.id,
                                                    })}
                                                    className="btn btn-primary w-full"
                                                >
                                                    {t('data.link_species')}
                                                </Link>
                                            ) : (
                                                <span
                                                    className="btn btn-primary btn-disabled w-full"
                                                    aria-disabled="true"
                                                >
                                                    {t('data.link_species')}
                                                </span>
                                            )}

                                            {canGenerateReports ? (
                                                <Link
                                                    href={route(
                                                        'data.ethnobotanyR',
                                                        {
                                                            project: project.id,
                                                        },
                                                    )}
                                                    className="btn btn-primary w-full"
                                                >
                                                    {t(
                                                        'data.generate_ethnobotanyr',
                                                    )}
                                                </Link>
                                            ) : (
                                                <span
                                                    className="btn btn-primary btn-disabled w-full"
                                                    aria-disabled="true"
                                                >
                                                    {t(
                                                        'data.generate_ethnobotanyr',
                                                    )}
                                                </span>
                                            )}

                                            {canGenerateReports ? (
                                                <Link
                                                    href={route('data.custom', {
                                                        project: project.id,
                                                    })}
                                                    className="btn btn-primary w-full"
                                                >
                                                    {t('data.custom_export')}
                                                </Link>
                                            ) : (
                                                <span
                                                    className="btn btn-primary btn-disabled w-full"
                                                    aria-disabled="true"
                                                >
                                                    {t('data.custom_export')}
                                                </span>
                                            )}
                                        </div>
                                    </Card>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
