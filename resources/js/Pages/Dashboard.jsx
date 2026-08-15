import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    faClipboardQuestion,
    faFolderOpen,
    faLeaf,
    faPenRuler,
    faTable,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Dashboard() {
    const { t } = useTranslation();
    const { auth } = usePage().props;
    const hasProjects = auth.projects > 0;

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
                            {hasProjects
                                ? t('dashboard.subtitle')
                                : t('dashboard.no_projects_hint')}
                        </p>
                    </div>

                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <QuickLink
                            href={route('projects.index')}
                            icon={faFolderOpen}
                            title={t('navigation.projects')}
                        >
                            {t('dashboard.projects_desc')}
                        </QuickLink>
                        {hasProjects && (
                            <>
                                <QuickLink
                                    href={route('designer.index')}
                                    icon={faPenRuler}
                                    title={t('navigation.design')}
                                >
                                    {t('dashboard.design_desc')}
                                </QuickLink>
                                <QuickLink
                                    href={route('interviews.index')}
                                    icon={faClipboardQuestion}
                                    title={t('navigation.interview')}
                                >
                                    {t('dashboard.interview_desc')}
                                </QuickLink>
                                <QuickLink
                                    href={route('catalogs.index')}
                                    icon={faLeaf}
                                    title={t('navigation.catalogs')}
                                >
                                    {t('dashboard.catalogs_desc')}
                                </QuickLink>
                                <QuickLink
                                    href={route('data.index')}
                                    icon={faTable}
                                    title={t('navigation.data')}
                                >
                                    {t('dashboard.data_desc')}
                                </QuickLink>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function QuickLink({ href, icon, title, children }) {
    return (
        <Link
            href={href}
            className="card bg-base-200 text-base-content shadow-md transition-all duration-200 hover:-translate-y-1 hover:shadow-xl"
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
