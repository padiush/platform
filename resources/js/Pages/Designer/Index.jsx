import Alert from '@/Components/Alert';
import Card from '@/Components/Card';
import DeletionModal from '@/Components/DeletionModal';
import EmptyState from '@/Components/EmptyState';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { faCheck, faTimes } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function DesignerIndex({ projects }) {
    const { t } = useTranslation();
    const deletionModalRef = useRef();
    const [deletionModalOptions, setDeletionModalOptions] = useState({
        url: '',
        name: '',
    });

    const handleDelete = (projectId, form) => {
        setDeletionModalOptions({
            name: form.name,
            url: route('designer.form.delete', {
                project: projectId,
                form: form.id,
            }),
        });

        deletionModalRef.current.showModal();
    };

    return (
        <AuthenticatedLayout title={t('designer.title')}>
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {projects.length === 0 && (
                        <EmptyState
                            title={t('hubs.empty.title_designer')}
                            hint={t('hubs.empty.hint_designer')}
                            ctaHref={route('projects.index')}
                            ctaLabel={t('hubs.empty.cta')}
                        />
                    )}
                    {projects.length > 0 && (
                        <div className="grid w-full grid-cols-1 gap-4">
                            {projects.map((project) => (
                                <Card key={project.id} title={project.name}>
                                    {project.interview_forms.length > 0 ? (
                                        <table className="table-compact table w-full table-fixed">
                                            <thead>
                                                <tr>
                                                    <th className="hidden lg:table-cell">
                                                        {t(
                                                            'designer.index.form_name',
                                                        )}
                                                    </th>
                                                    <th
                                                        className="text-center"
                                                        style={{
                                                            width: '125px',
                                                        }}
                                                    >
                                                        {t(
                                                            'designer.index.enabled',
                                                        )}
                                                    </th>
                                                    <th className="table-cell lg:hidden">
                                                        {t(
                                                            'designer.index.form_name',
                                                        )}
                                                    </th>
                                                    <th className="hidden text-center lg:table-cell">
                                                        {t(
                                                            'designer.index.interviews',
                                                        )}
                                                    </th>
                                                    <th className="text-center">
                                                        {t(
                                                            'designer.index.actions',
                                                        )}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {project.interview_forms.map(
                                                    (form) => (
                                                        <tr key={form.id}>
                                                            <td className="text-wrap">
                                                                {form.name}
                                                                {form.description && (
                                                                    <div className="text-xs">
                                                                        {
                                                                            form.description
                                                                        }
                                                                    </div>
                                                                )}
                                                            </td>
                                                            <td className="text-center">
                                                                <FontAwesomeIcon
                                                                    icon={
                                                                        form.is_active
                                                                            ? faCheck
                                                                            : faTimes
                                                                    }
                                                                />
                                                            </td>
                                                            <td className="hidden text-center lg:table-cell">
                                                                {
                                                                    form
                                                                        .instances
                                                                        .length
                                                                }
                                                            </td>
                                                            <td className="text-center">
                                                                <div className="mb-2">
                                                                    <Link
                                                                        className="btn btn-secondary btn-xs join-item"
                                                                        href={route(
                                                                            'designer.form.wizard',
                                                                            {
                                                                                project:
                                                                                    project.id,
                                                                                form: form.id,
                                                                            },
                                                                        )}
                                                                    >
                                                                        {t(
                                                                            'designer.index.wizard',
                                                                        )}
                                                                    </Link>
                                                                </div>

                                                                <div className="join join-vertical lg:join-horizontal">
                                                                    <Link
                                                                        className="btn btn-primary btn-xs join-item"
                                                                        href={route(
                                                                            'designer.form.edit',
                                                                            {
                                                                                project:
                                                                                    project.id,
                                                                                form: form.id,
                                                                            },
                                                                        )}
                                                                    >
                                                                        {t(
                                                                            'designer.index.edit_details',
                                                                        )}
                                                                    </Link>
                                                                    <Link
                                                                        className="btn btn-primary btn-xs join-item"
                                                                        href={route(
                                                                            'designer.form.toggle',
                                                                            {
                                                                                project:
                                                                                    project.id,
                                                                                form: form.id,
                                                                            },
                                                                        )}
                                                                        method="put"
                                                                    >
                                                                        {form.is_active
                                                                            ? t(
                                                                                  'designer.index.disable',
                                                                              )
                                                                            : t(
                                                                                  'designer.index.enable',
                                                                              )}
                                                                    </Link>
                                                                    <div
                                                                        className="btn btn-error btn-xs join-item"
                                                                        onClick={() =>
                                                                            handleDelete(
                                                                                project.id,
                                                                                form,
                                                                            )
                                                                        }
                                                                    >
                                                                        {t(
                                                                            'designer.index.delete',
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    ),
                                                )}
                                            </tbody>
                                        </table>
                                    ) : (
                                        <Alert
                                            type="info"
                                            message={t(
                                                'designer.index.no_forms_yet',
                                            )}
                                        />
                                    )}
                                    <Link
                                        href={route(
                                            'designer.create',
                                            project.id,
                                        )}
                                        className="btn btn-primary mt-4 w-full"
                                    >
                                        {t('designer.index.create')}
                                    </Link>
                                </Card>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            <DeletionModal
                modalRef={deletionModalRef}
                name={deletionModalOptions.name}
                url={deletionModalOptions.url}
            />
        </AuthenticatedLayout>
    );
}
