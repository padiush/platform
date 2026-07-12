import Card from '@/Components/Card';
import EmptyState from '@/Components/EmptyState';
import MetricCard from '@/Components/MetricCard';
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
                                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            <MetricCard
                                                label={t(
                                                    'data.unlinked_reports',
                                                )}
                                                value={unlinkedCount}
                                                tone={
                                                    unlinkedCount > 0
                                                        ? 'warning'
                                                        : 'default'
                                                }
                                            />
                                            <MetricCard
                                                label={t('data.linked_reports')}
                                                value={linkedCount}
                                                tone="primary"
                                            />
                                        </div>

                                        <div className="mt-4 flex flex-wrap justify-end gap-2">
                                            <Link
                                                href={route('data.view', {
                                                    project: project.id,
                                                })}
                                                className="btn btn-primary btn-sm"
                                            >
                                                {t('data.view.title')}
                                            </Link>

                                            {canManageData ? (
                                                <Link
                                                    href={route('data.link', {
                                                        project: project.id,
                                                    })}
                                                    className="btn btn-outline btn-primary btn-sm"
                                                >
                                                    {t('data.link_species')}
                                                </Link>
                                            ) : (
                                                <span
                                                    className="btn btn-outline btn-sm btn-disabled"
                                                    aria-disabled="true"
                                                >
                                                    {t('data.link_species')}
                                                </span>
                                            )}

                                            {canGenerateReports ? (
                                                <Link
                                                    href={route(
                                                        'data.reports',
                                                        {
                                                            project: project.id,
                                                        },
                                                    )}
                                                    className="btn btn-outline btn-primary btn-sm"
                                                >
                                                    {t('data.reports.title')}
                                                </Link>
                                            ) : (
                                                <span
                                                    className="btn btn-outline btn-sm btn-disabled"
                                                    aria-disabled="true"
                                                >
                                                    {t('data.reports.title')}
                                                </span>
                                            )}

                                            {canGenerateReports ? (
                                                <Link
                                                    href={route('data.export', {
                                                        project: project.id,
                                                    })}
                                                    className="btn btn-outline btn-primary btn-sm"
                                                >
                                                    {t('data.export.title')}
                                                </Link>
                                            ) : (
                                                <span
                                                    className="btn btn-outline btn-sm btn-disabled"
                                                    aria-disabled="true"
                                                >
                                                    {t('data.export.title')}
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
