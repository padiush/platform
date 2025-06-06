import Card from '@/Components/Card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { faArrowLeft } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function InterviewInstances({ project, form, instances }) {
    const { t } = useTranslation();

    return (
        <AuthenticatedLayout
            title={t('interviews.title')}
            subtitle={t('interviews.form_on_project', {
                form: form.name,
                project: project.name,
            })}
            action={
                <Link
                    href={route('interviews.index')}
                    className="btn btn-ghost"
                >
                    <FontAwesomeIcon icon={faArrowLeft} />
                </Link>
            }
        >
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <Card>
                        <table className="table-compact table w-full table-fixed">
                            <thead>
                                <tr>
                                    <th>{t('interviews.timestamp')}</th>
                                    <th>{t('interviews.identifier')}</th>
                                    <th>{t('interviews.created_by')}</th>
                                    <th>{t('projects.actions')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {instances.data.map((instance) => (
                                    <tr key={instance.id}>
                                        <td>{instance.created_at}</td>
                                        <td>{instance.id}</td>
                                        <td>{instance.user.name}</td>
                                        <td>
                                            <Link
                                                href={route(
                                                    'interviews.show',
                                                    instance.id,
                                                )}
                                                className="btn btn-xs btn-primary"
                                            >
                                                {t('common.actions.view')}
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* Pagination */}
                        <div className="mt-4 flex justify-center">
                            {instances.links && (
                                <div className="join">
                                    {instances.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`join-item btn btn-sm ${
                                                link.active
                                                    ? 'btn-ghost'
                                                    : 'btn-primary'
                                            }`}
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
