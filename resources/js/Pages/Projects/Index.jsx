import Card from '@/Components/Card';
import DeletionModal from '@/Components/DeletionModal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { faCircleInfo } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import moment from 'moment/min/moment-with-locales';
import { useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function Index({ projects, invites }) {
    const { t } = useTranslation();
    moment.locale(t('lang'));

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
                                                    {invite.expires_at_human}
                                                </td>
                                                <td>
                                                    <div className="btn-group">
                                                        <a
                                                            className="btn btn-success btn-xs"
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
                                                            className="btn btn-error btn-xs"
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
                                                date: moment(
                                                    project.created_at,
                                                ).format('LL'),
                                                user: project.user.name,
                                            })}
                                        </p>
                                    )}

                                    {project.can_manage && (
                                        <div className="mt-2 flex flex-col gap-2">
                                            <Link
                                                className="btn btn-primary btn-sm"
                                                href={route('projects.edit', {
                                                    project: project.id,
                                                })}
                                            >
                                                {t('actions.edit_details')}
                                            </Link>
                                            <Link
                                                className="btn btn-primary btn-sm"
                                                href={route(
                                                    'projects.accesses',
                                                    {
                                                        project: project.id,
                                                    },
                                                )}
                                            >
                                                {t('actions.manage_access')}
                                            </Link>
                                            <div
                                                className="btn btn-error btn-sm"
                                                onClick={() =>
                                                    handleDelete(project)
                                                }
                                            >
                                                {t('actions.delete')}
                                            </div>
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

                    {/* Botón crear proyecto */}
                    <Card className="mt-4">
                        <Link
                            className="btn btn-primary"
                            href={route('projects.create')}
                        >
                            {t('projects.create')}
                        </Link>
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
