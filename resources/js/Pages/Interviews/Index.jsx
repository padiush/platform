import Card from '@/Components/Card';
import EmptyState from '@/Components/EmptyState';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { faCircleInfo } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function InterviewOverview({ projects }) {
    const { t } = useTranslation();

    return (
        <AuthenticatedLayout title={t('interviews.title')}>
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {projects.length === 0 && (
                        <EmptyState
                            title={t('hubs.empty.title_interviews')}
                            hint={t('hubs.empty.hint_interviews')}
                            ctaHref={route('projects.index')}
                            ctaLabel={t('hubs.empty.cta')}
                        />
                    )}
                    {projects.length > 0 && (
                        <div className="grid w-full grid-cols-1 gap-4">
                            {projects.map((project) => (
                                <Card key={project.id} title={project.name}>
                                    {project.active_interview_forms.length >
                                    0 ? (
                                        <table className="table-compact table w-full table-fixed">
                                            <thead>
                                                <tr>
                                                    <th className="hidden lg:table-cell">
                                                        {t(
                                                            'designer.index.form_name',
                                                        )}
                                                    </th>
                                                    <th className="table-cell lg:hidden">
                                                        {t(
                                                            'projects.access_form.name',
                                                        )}
                                                    </th>
                                                    <th className="hidden lg:table-cell">
                                                        {t('interviews.title')}
                                                    </th>
                                                    <th>
                                                        {t('projects.actions')}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {project.active_interview_forms.map(
                                                    (form) => (
                                                        <tr key={form.id}>
                                                            <td className="text-wrap">
                                                                {form.name}
                                                            </td>
                                                            <td className="hidden lg:table-cell">
                                                                {
                                                                    form
                                                                        .instances
                                                                        .length
                                                                }
                                                            </td>
                                                            <td className="flex flex-wrap gap-2">
                                                                <Link
                                                                    href={route(
                                                                        'interviews.create',
                                                                        form.id,
                                                                    )}
                                                                    className="btn btn-sm btn-primary"
                                                                >
                                                                    {t(
                                                                        'interviews.new_interview',
                                                                    )}
                                                                </Link>
                                                                <Link
                                                                    href={route(
                                                                        'interviews.instances',
                                                                        form.id,
                                                                    )}
                                                                    className="btn btn-sm btn-ghost"
                                                                >
                                                                    {t(
                                                                        'interviews.view_existing',
                                                                    )}
                                                                </Link>
                                                            </td>
                                                        </tr>
                                                    ),
                                                )}
                                            </tbody>
                                        </table>
                                    ) : (
                                        <div className="alert shadow-lg">
                                            <div>
                                                <FontAwesomeIcon
                                                    icon={faCircleInfo}
                                                />
                                                <span>
                                                    {t(
                                                        'interviews.no_interviews',
                                                    )}
                                                </span>
                                            </div>
                                        </div>
                                    )}
                                </Card>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
