import ApplicationLogo from '@/Components/ApplicationLogo';
import ErrorBoundary from '@/Components/ErrorBoundary';
import TranslationToggle from '@/Components/TranslationToggle';
import { useFlashMessage } from '@/Hooks/useFlashMessage';
import { faRightFromBracket } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function AuthenticatedLayout({
    children,
    title,
    subtitle,
    action,
    actionRight,
}) {
    const { auth } = usePage().props;
    const { t } = useTranslation();
    const { FlashAlert, flashShown } = useFlashMessage();

    return (
        <div className="z-10 flex h-screen w-full flex-col overflow-hidden">
            <Head title={title} />
            <div className="navbar bg-primary text-primary-content sticky top-0 z-30 h-16 shadow-xl">
                <div className="navbar-start">
                    <div className="dropdown">
                        <div
                            tabIndex={0}
                            role="button"
                            className="btn btn-ghost lg:hidden"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                className="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                {' '}
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="2"
                                    d="M4 6h16M4 12h8m-8 6h16"
                                />{' '}
                            </svg>
                        </div>
                        <ul
                            tabIndex={0}
                            className="menu menu-sm dropdown-content bg-base-100 rounded-box z-1 mt-3 w-52 p-2 shadow"
                        >
                            <li>
                                <Link href={route('projects.index')}>
                                    {t('navigation.projects')}
                                </Link>
                            </li>
                            {auth.projects > 0 && (
                                <>
                                    <li>
                                        <details>
                                            <summary>
                                                {t('navigation.interviews')}
                                            </summary>
                                            <ul className="bg-base-200 z-20 p-2 shadow-xl">
                                                <li>
                                                    <Link
                                                        href={route(
                                                            'designer.index',
                                                        )}
                                                    >
                                                        {t('navigation.design')}
                                                    </Link>
                                                </li>
                                                <li>
                                                    <Link
                                                        href={route(
                                                            'interviews.index',
                                                        )}
                                                    >
                                                        {t(
                                                            'navigation.interview',
                                                        )}
                                                    </Link>
                                                </li>
                                            </ul>
                                        </details>
                                    </li>
                                    <li>
                                        <Link href={route('catalogs.index')}>
                                            {t('navigation.catalogs')}
                                        </Link>
                                    </li>
                                    <li>
                                        <Link href={route('data.index')}>
                                            {t('navigation.data')}
                                        </Link>
                                    </li>
                                </>
                            )}
                        </ul>
                    </div>
                    <Link
                        className="btn btn-ghost text-xl"
                        href={route('dashboard')}
                    >
                        <ApplicationLogo className="h-10 w-auto fill-current" />
                    </Link>
                </div>
                <div className="navbar-center hidden lg:flex">
                    <ul className="menu menu-horizontal px-1">
                        <li>
                            <Link href={route('projects.index')}>
                                {t('navigation.projects')}
                            </Link>
                        </li>
                        {auth.projects > 0 && (
                            <>
                                <li>
                                    <details>
                                        <summary>
                                            {t('navigation.interviews')}
                                        </summary>
                                        <ul className="bg-base-200 z-20 p-2 shadow-xl">
                                            <li>
                                                <Link
                                                    href={route(
                                                        'designer.index',
                                                    )}
                                                >
                                                    {t('navigation.design')}
                                                </Link>
                                            </li>
                                            <li>
                                                <Link
                                                    href={route(
                                                        'interviews.index',
                                                    )}
                                                >
                                                    {t('navigation.interview')}
                                                </Link>
                                            </li>
                                        </ul>
                                    </details>
                                </li>
                                <li>
                                    <Link href={route('catalogs.index')}>
                                        {t('navigation.catalogs')}
                                    </Link>
                                </li>
                                <li>
                                    <Link href={route('data.index')}>
                                        {t('navigation.data')}
                                    </Link>
                                </li>
                            </>
                        )}
                    </ul>
                </div>
                <div className="navbar-end">
                    <TranslationToggle />

                    <Link
                        className="btn btn-ghost"
                        href="/logout"
                        method="post"
                        as="button"
                    >
                        <FontAwesomeIcon icon={faRightFromBracket} />
                    </Link>
                </div>
            </div>

            <div className="bg-base-200 sticky top-16 z-20 flex items-center justify-between gap-4 px-4 py-4 text-lg font-semibold shadow-xl md:px-12 md:py-6 md:text-xl lg:px-24 lg:text-2xl">
                {action && action}

                <div className="items-left flex grow flex-col">
                    <div className="text-2xl font-bold">{title}</div>
                    <div className="text-lg font-semibold">{subtitle}</div>
                </div>

                {actionRight && actionRight}
            </div>

            <div className="bg-base-100 flex-1 overflow-y-auto">
                {flashShown && <FlashAlert />}

                <ErrorBoundary>{children}</ErrorBoundary>
            </div>
        </div>
    );
}
