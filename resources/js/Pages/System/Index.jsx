import Card from '@/Components/Card';
import DeletionModal from '@/Components/DeletionModal';
import Input from '@/Components/Input';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function SystemIndex({
    users,
    registration_invites,
    user_count,
    project_count,
}) {
    const { t, i18n } = useTranslation();
    const deletionModalRef = useRef();
    const [deletionModalOptions, setDeletionModalOptions] = useState({
        url: '',
        name: '',
        data: null,
    });
    const [selectedUsers, setSelectedUsers] = useState([]);
    const inviteForm = useForm({ name: '', email: '' });

    const submitRegistrationInvite = (event) => {
        event.preventDefault();
        inviteForm.post(route('system.registration-invites.store'), {
            preserveScroll: true,
            onSuccess: () => inviteForm.reset(),
        });
    };

    const formatExpiration = (value) =>
        new Intl.DateTimeFormat(i18n.language, {
            dateStyle: 'medium',
        }).format(new Date(value));

    const handleDelete = (user) => {
        setDeletionModalOptions({
            name: user.name,
            url: route('system.users.delete', user.id),
            data: null,
        });
        deletionModalRef.current.showModal();
    };

    const toggleUser = (user) => {
        if (selectedUsers.some((u) => u.id === user.id)) {
            setSelectedUsers(selectedUsers.filter((u) => u.id !== user.id));
        } else {
            setSelectedUsers([...selectedUsers, user]);
        }
    };

    const toggleAll = () => {
        if (selectedUsers.length === users.length) {
            setSelectedUsers([]);
        } else {
            setSelectedUsers(users);
        }
    };

    const handleBulkDelete = () => {
        const names = selectedUsers.map((u) => u.name);
        const displayName =
            names.length > 3
                ? names.slice(0, 3).join(', ') + ', ...'
                : names.join(', ');
        setDeletionModalOptions({
            name: displayName,
            url: route('system.users.bulk-delete'),
            data: { ids: selectedUsers.map((u) => u.id) },
        });
        deletionModalRef.current.showModal();
    };

    return (
        <AuthenticatedLayout title={t('system.dashboard')}>
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    <Card title={t('system.summary')}>
                        <p>{t('system.total_users', { count: user_count })}</p>
                        <p>
                            {t('system.total_projects', {
                                count: project_count,
                            })}
                        </p>
                    </Card>
                    <Card title={t('system.registration_invites')}>
                        <p className="mb-4 text-sm opacity-80">
                            {t('system.registration_invites_description')}
                        </p>
                        <form
                            className="grid gap-3 md:grid-cols-2 lg:grid-cols-[1fr_1fr_auto] lg:items-end"
                            onSubmit={submitRegistrationInvite}
                        >
                            <Input
                                name="name"
                                label={t('auth.name')}
                                value={inviteForm.data.name}
                                onChange={(event) =>
                                    inviteForm.setData(
                                        'name',
                                        event.target.value,
                                    )
                                }
                                error={inviteForm.errors.name}
                                required
                            />
                            <Input
                                name="email"
                                type="email"
                                label={t('auth.email')}
                                value={inviteForm.data.email}
                                onChange={(event) =>
                                    inviteForm.setData(
                                        'email',
                                        event.target.value,
                                    )
                                }
                                error={inviteForm.errors.email}
                                required
                            />
                            <button
                                type="submit"
                                className="btn btn-primary lg:mb-0.5"
                                disabled={inviteForm.processing}
                            >
                                {t('system.send_registration_invite')}
                            </button>
                        </form>

                        <div className="mt-6">
                            <h3 className="mb-2 font-semibold">
                                {t('system.pending_registration_invites')}
                            </h3>
                            {registration_invites.length === 0 ? (
                                <p className="text-sm opacity-70">
                                    {t(
                                        'system.no_pending_registration_invites',
                                    )}
                                </p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="table-compact table w-full">
                                        <thead>
                                            <tr>
                                                <th>{t('auth.name')}</th>
                                                <th>{t('auth.email')}</th>
                                                <th>
                                                    {t(
                                                        'system.invitation_expires',
                                                    )}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {registration_invites.map(
                                                (invite) => (
                                                    <tr key={invite.id}>
                                                        <td>
                                                            {
                                                                invite.invited_name
                                                            }
                                                        </td>
                                                        <td>
                                                            {
                                                                invite.invited_email
                                                            }
                                                        </td>
                                                        <td>
                                                            {formatExpiration(
                                                                invite.expires_at,
                                                            )}
                                                        </td>
                                                    </tr>
                                                ),
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </div>
                    </Card>
                    <Card title={t('system.users')}>
                        <div className="mb-2 flex justify-end">
                            <button
                                className="btn btn-error btn-sm"
                                onClick={handleBulkDelete}
                                disabled={selectedUsers.length === 0}
                            >
                                {t('actions.delete_selected')}
                            </button>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="table-compact table w-full">
                                <thead>
                                    <tr>
                                        <th>
                                            <input
                                                type="checkbox"
                                                className="checkbox"
                                                onChange={toggleAll}
                                                checked={
                                                    selectedUsers.length ===
                                                        users.length &&
                                                    users.length > 0
                                                }
                                            />
                                        </th>
                                        <th>
                                            {t('projects.access_form.name')}
                                        </th>
                                        <th>
                                            {t('projects.access_form.email')}
                                        </th>
                                        <th>
                                            {t(
                                                'projects.access_form.permission',
                                            )}
                                        </th>
                                        <th>{t('projects.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {users.map((user) => (
                                        <tr key={user.id}>
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    className="checkbox"
                                                    onChange={() =>
                                                        toggleUser(user)
                                                    }
                                                    checked={selectedUsers.some(
                                                        (u) => u.id === user.id,
                                                    )}
                                                />
                                            </td>
                                            <td>{user.name}</td>
                                            <td>{user.email}</td>
                                            <td>
                                                {user.accesses.map((a) => (
                                                    <div key={a.project.id}>
                                                        {a.project.name} -{' '}
                                                        {a.capability}
                                                    </div>
                                                ))}
                                            </td>
                                            <td>
                                                <div
                                                    className="btn btn-ghost btn-sm text-error"
                                                    onClick={() =>
                                                        handleDelete(user)
                                                    }
                                                >
                                                    {t('actions.delete')}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>
            </div>
            <DeletionModal
                modalRef={deletionModalRef}
                name={deletionModalOptions.name}
                url={deletionModalOptions.url}
                data={deletionModalOptions.data}
            />
        </AuthenticatedLayout>
    );
}
