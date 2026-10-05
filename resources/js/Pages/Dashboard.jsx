import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { faFlask, faFolderOpen } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

/**
 * The welcome for someone with no project yet: everything else starts from
 * one, so it points at Proyectos. Anyone with a project lands on its overview.
 */
export default function Dashboard() {
    const { t } = useTranslation();
    const { auth } = usePage().props;

    return (
        <AuthenticatedLayout title={t('dashboard.title')}>
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="mb-8">
                        <h2 className="text-base-content text-2xl font-bold md:text-3xl">
                            {t('dashboard.greeting', {
                                name: auth.user.name,
                            })}
                        </h2>
                        <p className="text-base-content/70 mt-1 text-lg">
                            {t('dashboard.no_projects_hint')}
                        </p>
                    </div>

                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <QuickLink
                            tour="dashboard-projects"
                            href={route('projects.index')}
                            icon={faFolderOpen}
                            title={t('navigation.projects')}
                        >
                            {t('dashboard.projects_desc')}
                        </QuickLink>
                        <QuickLink
                            tour="dashboard-example"
                            href={route('projects.example.store')}
                            method="post"
                            icon={faFlask}
                            title={t('example.cta')}
                        >
                            {t('example.cta_hint')}
                        </QuickLink>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

/** A large card that opens a page, or — given `method` — does something. */
function QuickLink({
    href,
    icon,
    title,
    children,
    tour = undefined,
    method = undefined,
}) {
    return (
        <Link
            href={href}
            method={method}
            as={method ? 'button' : 'a'}
            data-tour={tour}
            className="card bg-base-200 text-base-content w-full text-left shadow-md transition-all duration-200 hover:-translate-y-1 hover:shadow-xl"
        >
            <div className="card-body">
                <div className="text-primary text-3xl">
                    <FontAwesomeIcon icon={icon} />
                </div>
                <h3 className="card-title">{title}</h3>
                <p className="text-base-content/70">{children}</p>
            </div>
        </Link>
    );
}
