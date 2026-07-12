import Card from '@/Components/Card';
import DeletionModal from '@/Components/DeletionModal';
import IconButton from '@/Components/IconButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatLongDate, formatRelativeTime } from '@/utils/datetime';
import {
    faCircleInfo,
    faPenToSquare,
    faPlus,
    faTrashCan,
    faUsers,
} from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function Index({ projects, invites }) {
    const { t, i18n } = useTranslation();

    const deletionModalRef = useRef();
    const [deletionModalOptions, setDeletionModalOptions] = useState({
        url: '',
        name: '',
    });

    const handleDelete = (project) => {
        setDeletionModalOptions({
            name: project.name,
            url: route('projects.delete', {
                project: project.id,
            }),
        });

        deletionModalRef.current.showModal();
    };

    return (
        <AuthenticatedLayout title={t('titles.my_projects')}>
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Invitaciones pendientes */}
                    {invites.length > 0 && (
                        <div className="grid grid-cols-1 gap-4 pb-4">
                            <Card title={t('projects.pending_invites')}>
                                <p>{t('projects.invited_to')}</p>
                                <table className="table-compact table w-full">
                                    <thead>
                                        <tr>
                                            <td>{t('projects.invited_by')}</td>
                                            <td>{t('projects.project')}</td>
                                            <td>{t('projects.expires')}</td>
                                            <td>{t('projects.actions')}</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {invites.map((invite) => (
                                            <tr key={invite.id}>
                                                <td>
                                                    {invite.inviting_user.name}
                                                </td>
                                                <td>{invite.project.name}</td>
                                                <td>
                                                    {formatRelativeTime(
                                                        invite.expires_at,
                                                        i18n.language,
                                                    )}
                                                </td>
                                                <td>
                                                    <div className="flex gap-2">
                                                        <a
                                                            className="btn btn-primary btn-sm"
                                                            href={route(
                                                                'projects.invites.accept',
                                                                invite.id,
                                                            )}
                                                        >
                                                            {t(
                                                                'actions.accept',
                                                            )}
                                                        </a>
                                                        <a
                                                            className="btn btn-ghost btn-sm text-error"
                                                            href={route(
                                                                'projects.invites.decline',
                                                                invite.id,
                                                            )}
                                                        >
                                                            {t(
                                                                'actions.decline',
                                                            )}
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </Card>
                        </div>
                    )}

                    {/* Proyectos disponibles */}
                    {projects.length > 0 ? (
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                            {projects.map((project) => (
                                <Card key={project.id} title={project.name}>
                                    {project.author && (
                                        <p>
                                            {t('projects.created_on_by', {
                                                date: formatLongDate(
                                                    project.created_at,
                                                    i18n.language,
                                                ),
                                                user: project.user.name,
                                            })}
                                        </p>
                                    )}

                                    {project.can_manage && (
                                        <div className="mt-2 flex flex-wrap items-center gap-2">
                                            <Link
                                                className="btn btn-outline btn-sm"
                                                href={route('projects.edit', {
                                                    project: project.id,
                                                })}
                                            >
                                                <FontAwesomeIcon
                                                    icon={faPenToSquare}
                                                />
                                                {t('actions.edit_details')}
                                            </Link>
                                            <Link
                                                className="btn btn-outline btn-sm"
                                                href={route(
                                                    'projects.accesses',
                                                    {
                                                        project: project.id,
                                                    },
                                                )}
                                            >
                                                <FontAwesomeIcon
                                                    icon={faUsers}
                                                />
                                                {t('actions.manage_access')}
                                            </Link>
                                            <IconButton
                                                icon={faTrashCan}
                                                label={t('actions.delete')}
                                                className="text-error"
                                                onClick={() =>
                                                    handleDelete(project)
                                                }
                                            />
                                        </div>
                                    )}
                                </Card>
                            ))}
                        </div>
                    ) : (
                        <div className="alert shadow-lg">
                            <div>
                                <FontAwesomeIcon
                                    icon={faCircleInfo}
                                    className="mr-2"
                                />
                                <span>{t('projects.no_projects')}</span>
                            </div>
                        </div>
                    )}

                    <div className="mt-6 flex justify-end">
                        <Link
                            className="btn btn-primary"
                            href={route('projects.create')}
                        >
                            <FontAwesomeIcon icon={faPlus} />
                            {t('projects.create')}
                        </Link>
                    </div>
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
