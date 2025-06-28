import Card from '@/Components/Card';
import DeletionModal from '@/Components/DeletionModal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function SystemIndex({ users, user_count, project_count }) {
    const { t } = useTranslation();
    const deletionModalRef = useRef();
    const [deletionModalOptions, setDeletionModalOptions] = useState({
        url: '',
        name: '',
    });

    const handleDelete = (user) => {
        setDeletionModalOptions({
            name: user.name,
            url: route('system.users.delete', user.id),
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
                    <Card title={t('system.users')}>
                        <table className="table-compact table w-full">
                            <thead>
                                <tr>
                                    <th>{t('projects.access_form.name')}</th>
                                    <th>{t('projects.access_form.email')}</th>
                                    <th>
                                        {t('projects.access_form.permission')}
                                    </th>
                                    <th>{t('projects.actions')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {users.map((user) => (
                                    <tr key={user.id}>
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
                                                className="btn btn-error btn-xs"
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
