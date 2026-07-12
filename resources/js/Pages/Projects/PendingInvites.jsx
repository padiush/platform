import Alert from '@/Components/Alert';
import Card from '@/Components/Card';
import Input from '@/Components/Input';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatRelativeTime } from '@/utils/datetime';
import { faArrowLeft, faTrash } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function PendingInvites({ project, invites, filters }) {
    const { t, i18n } = useTranslation();
    const { data, setData } = useForm({
        search: filters?.search || '',
    });

    const [debouncedSearch, setDebouncedSearch] = useState(data.search);

    const handleRevoke = (inviteId) => {
        if (confirm(t('projects.access_form.revoke_confirmation'))) {
            router.delete(
                route('projects.accesses.invites.revoke', {
                    project: project.id,
                    invite: inviteId,
                }),
            );
        }
    };

    // Watch data.search and debounce the router call
    useEffect(() => {
        const timeout = setTimeout(() => {
            if (debouncedSearch !== data.search) {
                setDebouncedSearch(data.search);
                router.get(
                    route('projects.accesses.invites', {
                        project: project.id,
                        search: data.search,
                    }),
                    {},
                    { preserveScroll: true, replace: true },
                );
            }
        }, 400);

        return () => clearTimeout(timeout);
    }, [data.search, debouncedSearch, project.id]);

    return (
        <AuthenticatedLayout
            title={t('projects.pending_invites')}
            breadcrumbs={[
                {
                    label: t('navigation.projects'),
                    href: route('projects.index'),
                },
                {
                    label: project.name,
                    href: route('projects.accesses', { project: project.id }),
                },
                { label: t('projects.pending_invites') },
            ]}
            subtitle={project.name}
            action={
                <Link
                    className="btn btn-ghost btn-circle"
                    href={route('projects.accesses', { project: project.id })}
                >
                    <FontAwesomeIcon icon={faArrowLeft} />
                </Link>
            }
        >
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="grid w-full grid-cols-1 gap-4">
                        <Card title={t('projects.pending_invites')}>
                            <div className="mb-4">
                                <Input
                                    name="search"
                                    placeholder={t('projects.access_form.name')}
                                    value={data.search}
                                    onChange={(e) =>
                                        setData('search', e.target.value)
                                    }
                                />
                            </div>

                            {invites.data.length > 0 ? (
                                <>
                                    <table className="table-compact table w-full">
                                        <thead>
                                            <tr>
                                                <td>
                                                    {t(
                                                        'projects.access_form.name',
                                                    )}
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
                                                <td>{t('projects.expires')}</td>
                                                <td>
                                                    {t(
                                                        'projects.access_form.actions',
                                                    )}
                                                </td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {invites.data.map((invite) => (
                                                <tr key={invite.id}>
                                                    <td>
                                                        {invite.invited_user
                                                            ? invite
                                                                  .invited_user
                                                                  .name
                                                            : invite.invited_name}
                                                    </td>
                                                    <td className="hidden lg:table-cell">
                                                        {invite.invited_email}
                                                    </td>
                                                    <td>
                                                        {invite.capability.name}
                                                    </td>
                                                    <td>
                                                        {formatRelativeTime(
                                                            invite.expires_at,
                                                            i18n.language,
                                                        )}
                                                    </td>
                                                    <td>
                                                        <button
                                                            className="btn btn-error btn-xs"
                                                            onClick={() =>
                                                                handleRevoke(
                                                                    invite.id,
                                                                )
                                                            }
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
                                            ))}
                                        </tbody>
                                    </table>

                                    <div className="mt-4 flex flex-wrap justify-between">
                                        {invites.total > invites.per_page && (
                                            <div className="join">
                                                {invites.links.map((link) => (
                                                    <Link
                                                        key={link.label}
                                                        className={`join-item btn btn-sm ${
                                                            link.active
                                                                ? 'btn-primary'
                                                                : 'btn-ghost'
                                                        }`}
                                                        href={link.url || '#'}
                                                        dangerouslySetInnerHTML={{
                                                            __html: link.label,
                                                        }}
                                                    />
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </>
                            ) : (
                                <Alert
                                    type="warning"
                                    message={t('projects.no_matching_invites')}
                                />
                            )}
                        </Card>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
