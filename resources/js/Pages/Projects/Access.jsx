import Alert from '@/Components/Alert';
import Card from '@/Components/Card';
import ConfirmModal from '@/Components/ConfirmModal';
import FormModal from '@/Components/FormModal';
import useQueryModal from '@/Hooks/useQueryModal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    faArrowLeft,
    faTrash,
    faUserPlus,
} from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import InviteForm from './Partials/InviteForm';

export default function Accesses({
    project,
    users,
    invites,
    capabilities,
    auth,
}) {
    const { t } = useTranslation();

    const [inviteParam, setInvite] = useQueryModal('invite');
    const [revoking, setRevoking] = useState(null);

    const confirmRevoke = () => {
        router.delete(
            route('projects.accesses.revoke', {
                project: project.id,
                user: revoking.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => setRevoking(null),
            },
        );
    };

    return (
        <AuthenticatedLayout
            title={t('projects.access')}
            breadcrumbs={[
                {
                    label: t('navigation.projects'),
                    href: route('projects.index'),
                },
                { label: project.name },
            ]}
            subtitle={project.name}
            action={
                <Link
                    href={route('projects.index')}
                    className="btn btn-ghost btn-circle"
                    aria-label={t('navigation.back')}
                >
                    <FontAwesomeIcon icon={faArrowLeft} />
                </Link>
            }
        >
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="grid w-full grid-cols-1 gap-4 lg:grid-cols-3">
                        {/* Invitation section */}
                        <div className="grid grid-cols-1 gap-4">
                            {invites.length > 0 && (
                                <div>
                                    <a
                                        href={route(
                                            'projects.accesses.invites',
                                            {
                                                project: project.id,
                                            },
                                        )}
                                        className="btn btn-primary"
                                    >
                                        {t('projects.view_pending_invites')}
                                        <span className="badge badge-secondary badge-sm">
                                            {invites.length}
                                        </span>
                                    </a>
                                </div>
                            )}

                            <Card title={t('projects.invite_user')}>
                                <p className="text-sm opacity-80">
                                    {t('projects.invite_user_hint')}
                                </p>
                                <div className="mt-4">
                                    <button
                                        type="button"
                                        className="btn btn-primary self-start"
                                        onClick={() => setInvite('1')}
                                    >
                                        <FontAwesomeIcon icon={faUserPlus} />
                                        {t('actions.invite')}
                                    </button>
                                </div>
                            </Card>
                        </div>

                        {/* User list section */}
                        <Card
                            className="lg:col-span-2"
                            title={t('projects.users_with_access')}
                        >
                            {users.length > 1 ? (
                                <table className="table-compact table w-full">
                                    <thead>
                                        <tr>
                                            <th>
                                                {t('projects.access_form.name')}
                                            </th>
                                            <th className="hidden lg:table-cell">
                                                {t(
                                                    'projects.access_form.email',
                                                )}
                                            </th>
                                            <th>
                                                {t(
                                                    'projects.access_form.permission',
                                                )}
                                            </th>
                                            <th>
                                                {t(
                                                    'projects.access_form.actions',
                                                )}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {users.map((user) => {
                                            const access =
                                                project.accesses.find(
                                                    (a) =>
                                                        a.user_id === user.id,
                                                );
                                            return (
                                                <tr key={user.id}>
                                                    <td className="text-wrap">
                                                        {user.name}
                                                    </td>
                                                    <td className="hidden text-wrap lg:table-cell">
                                                        {user.email}
                                                    </td>
                                                    <td className="text-wrap">
                                                        {
                                                            access?.capability
                                                                ?.name
                                                        }
                                                    </td>
                                                    <td>
                                                        {user.id ===
                                                        auth.user.id ? (
                                                            <span className="badge badge-ghost badge-sm">
                                                                {t(
                                                                    'projects.you',
                                                                )}
                                                            </span>
                                                        ) : (
                                                            <button
                                                                type="button"
                                                                className="btn btn-ghost btn-sm text-error"
                                                                onClick={() =>
                                                                    setRevoking(
                                                                        user,
                                                                    )
                                                                }
                                                            >
                                                                <FontAwesomeIcon
                                                                    icon={
                                                                        faTrash
                                                                    }
                                                                    className="lg:mr-2"
                                                                />
                                                                <span className="hidden lg:inline">
                                                                    {t(
                                                                        'actions.revoke',
                                                                    )}
                                                                </span>
                                                            </button>
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            ) : (
                                <Alert
                                    type="info"
                                    message={t('projects.no_other_users')}
                                />
                            )}
                        </Card>
                    </div>
                </div>
            </div>

            <FormModal
                open={inviteParam != null}
                onClose={() => setInvite(null)}
                title={t('projects.invite_user')}
            >
                <InviteForm
                    project={project}
                    capabilities={capabilities}
                    onClose={() => setInvite(null)}
                />
            </FormModal>

            <ConfirmModal
                open={revoking != null}
                title={t('projects.revoke_access_title')}
                message={
                    revoking
                        ? t('projects.access_form.revoke_confirmation_named', {
                              name: revoking.name,
                          })
                        : ''
                }
                confirmLabel={t('actions.revoke')}
                onConfirm={confirmRevoke}
                onClose={() => setRevoking(null)}
            />
        </AuthenticatedLayout>
    );
}
