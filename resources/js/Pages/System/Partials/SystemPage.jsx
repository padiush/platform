import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { faUserPlus } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import InviteModal from './InviteModal';

const TABS = [
    { key: 'summary', route: 'system.index' },
    { key: 'users', route: 'system.users' },
    { key: 'storage', route: 'system.storage' },
];

/**
 * The frame every page of the system panel shares: its tabs, and the
 * invitation, which is offered from all of them.
 */
export default function SystemPage({ tab, children }) {
    const { t } = useTranslation();
    const [inviting, setInviting] = useState(false);

    return (
        <AuthenticatedLayout
            title={t('system.dashboard')}
            actionRight={
                <button
                    type="button"
                    className="btn btn-primary btn-sm shrink-0"
                    onClick={() => setInviting(true)}
                >
                    <FontAwesomeIcon icon={faUserPlus} />
                    <span className="hidden sm:inline">
                        {t('system.invite.button')}
                    </span>
                    <span className="sr-only sm:hidden">
                        {t('system.invite.button')}
                    </span>
                </button>
            }
        >
            <div className="mx-auto max-w-6xl space-y-4 p-4 md:p-6">
                <nav
                    aria-label={t('system.tabs.label')}
                    className="flex flex-wrap gap-1"
                >
                    {TABS.map(({ key, route: name }) => (
                        <Link
                            key={key}
                            href={route(name)}
                            aria-current={tab === key ? 'page' : undefined}
                            className={`btn btn-sm ${
                                tab === key ? 'btn-primary' : 'btn-ghost'
                            }`}
                        >
                            {t(`system.tabs.${key}`)}
                        </Link>
                    ))}
                </nav>

                {children}
            </div>

            <InviteModal open={inviting} onClose={() => setInviting(false)} />
        </AuthenticatedLayout>
    );
}
