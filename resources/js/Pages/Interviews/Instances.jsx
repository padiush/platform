import Card from '@/Components/Card';
import DeletionModal from '@/Components/DeletionModal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatDateTime } from '@/utils/datetime';
import { faArrowLeft } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function InterviewInstances({ project, form, instances }) {
    const { t, i18n } = useTranslation();
    const deletionModalRef = useRef();
    const [deletionModalOptions, setDeletionModalOptions] = useState({
        url: '',
        name: '',
    });

    const handleDelete = (instance) => {
        setDeletionModalOptions({
            name: t('interviews.instance.title', { id: instance.id }),
            url: route('interviews.destroy', instance.id),
        });

        deletionModalRef.current.showModal();
    };

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
                                        <td>
                                            {formatDateTime(
                                                instance.created_at,
                                                i18n.language,
                                            )}
                                        </td>
                                        <td>{instance.id}</td>
                                        <td>{instance.user.name}</td>
                                        <td>
                                            <div className="join join-vertical lg:join-horizontal">
                                                <Link
                                                    href={route(
                                                        'interviews.show',
                                                        instance.id,
                                                    )}
                                                    className="btn btn-xs btn-primary join-item"
                                                >
                                                    {t('common.actions.view')}
                                                </Link>
                                                <div
                                                    className="btn btn-error btn-xs join-item"
                                                    onClick={() =>
                                                        handleDelete(instance)
                                                    }
                                                >
                                                    {t('actions.delete')}
                                                </div>
                                            </div>
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
            <DeletionModal
                modalRef={deletionModalRef}
                name={deletionModalOptions.name}
                url={deletionModalOptions.url}
            />
        </AuthenticatedLayout>
    );
}
