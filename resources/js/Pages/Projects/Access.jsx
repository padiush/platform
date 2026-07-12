import Alert from '@/Components/Alert';
import Card from '@/Components/Card';
import Input from '@/Components/Input';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { faArrowLeft, faTrash } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, router, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Accesses({
    project,
    users,
    invites,
    capabilities,
    auth,
}) {
    const { t } = useTranslation();

    const { data, setData, post, processing } = useForm({
        name: '',
        email: '',
        capability_id: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('projects.accesses.invite', { project: project.id }));
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
                                <div className="indicator w-full">
                                    <span className="indicator-item badge badge-secondary">
                                        {invites.length}
                                    </span>
                                    <a
                                        href={route(
                                            'projects.accesses.invites',
                                            {
                                                project: project.id,
                                            },
                                        )}
                                        className="btn btn-primary w-full"
                                    >
                                        {t('projects.view_pending_invites')}
                                    </a>
                                </div>
                            )}

                            <Card title={t('projects.invite_user')}>
                                <form onSubmit={handleSubmit}>
                                    <Input
                                        name="name"
                                        label={t('projects.access_form.name')}
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                    />
                                    <Input
                                        name="email"
                                        type="email"
                                        label={t('projects.access_form.email')}
                                        value={data.email}
                                        onChange={(e) =>
                                            setData('email', e.target.value)
                                        }
                                    />
                                    <div className="form-control w-full">
                                        <fieldset className="fieldset w-full">
                                            <legend className="fieldset-legend">
                                                {t(
                                                    'projects.access_form.permission',
                                                )}
                                            </legend>
                                            <select
                                                name="capability_id"
                                                value={data.capability_id}
                                                onChange={(e) =>
                                                    setData(
                                                        'capability_id',
                                                        e.target.value,
                                                    )
                                                }
                                                className="select select-bordered w-full"
                                                required
                                            >
                                                <option value="" disabled>
                                                    {t(
                                                        'projects.access_form.select_permission',
                                                    )}
                                                </option>
                                                {capabilities.map(
                                                    (capability) => (
                                                        <option
                                                            key={capability.id}
                                                            value={
                                                                capability.id
                                                            }
                                                        >
                                                            {capability.name}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                        </fieldset>
                                    </div>
                                    <div className="mt-4 w-full">
                                        <button
                                            type="submit"
                                            className="btn btn-primary w-full"
                                            disabled={processing}
                                        >
                                            {t('actions.invite')}
                                        </button>
                                    </div>
                                </form>
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
                                            <td>
                                                {t('projects.access_form.name')}
                                            </td>
                                            <td className="hidden lg:table-cell">
                                                {t(
                                                    'projects.access_form.email',
                                                )}
                                            </td>
                                            <td>
                                                {t(
                                                    'projects.access_form.permission',
                                                )}
                                            </td>
                                            <td>
                                                {t(
                                                    'projects.access_form.actions',
                                                )}
                                            </td>
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
                                                        <button
                                                            type="button"
                                                            className="btn btn-error btn-xs"
                                                            disabled={
                                                                user.id ===
                                                                auth.user.id
                                                            }
                                                            onClick={() => {
                                                                if (
                                                                    confirm(
                                                                        t(
                                                                            'projects.access_form.revoke_confirmation',
                                                                        ),
                                                                    )
                                                                ) {
                                                                    router.delete(
                                                                        route(
                                                                            'projects.accesses.revoke',
                                                                            {
                                                                                project:
                                                                                    project.id,
                                                                                user: user.id,
                                                                            },
                                                                        ),
                                                                        {
                                                                            preserveScroll: true,
                                                                        },
                                                                    );
                                                                }
                                                            }}
                                                        >
                                                            <FontAwesomeIcon
                                                                icon={faTrash}
                                                                className="lg:mr-2"
                                                            />
                                                            <span className="hidden lg:inline">
                                                                {t(
                                                                    'actions.revoke',
                                                                )}
                                                            </span>
                                                        </button>
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
        </AuthenticatedLayout>
    );
}
