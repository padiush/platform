import ApplicationLogo from '@/Components/ApplicationLogo';
import Breadcrumbs from '@/Components/Breadcrumbs';
import ErrorBoundary from '@/Components/ErrorBoundary';
import ThemeToggle from '@/Components/ThemeToggle';
import TranslationToggle from '@/Components/TranslationToggle';
import { useFlashMessage } from '@/Hooks/useFlashMessage';
import { faRightFromBracket } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function AuthenticatedLayout({
    children,
    title,
    subtitle,
    action,
    actionRight,
    breadcrumbs = null,
    // Plain-text document title for when `title` is JSX.
    headTitle = null,
}) {
    const { auth } = usePage().props;
    const { t } = useTranslation();
    const { FlashAlert, flashShown } = useFlashMessage();

    const activeWhen = (pattern) =>
        route().current(pattern) ? 'menu-active' : '';

    // One flat, capability-gated list feeding both menus: the navbar only
    // promises sections the user's project roles actually unlock.
    const capabilities = auth.capabilities ?? {};
    const navItems = [
        {
            label: t('navigation.projects'),
            href: route('projects.index'),
            pattern: 'projects.*',
            show: true,
        },
        {
            label: t('navigation.system_dashboard'),
            href: route('system.index'),
            pattern: 'system.*',
            show: Boolean(auth.user.system_admin),
        },
        {
            label: t('navigation.design'),
            href: route('designer.index'),
            pattern: 'designer.*',
            show: Boolean(capabilities.manage_forms),
        },
        {
            label: t('navigation.interview'),
            href: route('interviews.index'),
            pattern: 'interviews.*',
            show: Boolean(capabilities.record_data),
        },
        {
            label: t('navigation.catalogs'),
            href: route('catalogs.index'),
            pattern: 'catalogs.*',
            show: Boolean(capabilities.view_catalog),
        },
        {
            label: t('navigation.data'),
            href: route('data.index'),
            pattern: 'data.*',
            show: Boolean(capabilities.data),
        },
    ].filter((item) => item.show);

    return (
        <div className="z-10 flex h-screen w-full flex-col overflow-hidden">
            <Head
                title={headTitle ?? (typeof title === 'string' ? title : '')}
            />
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
                            className="menu menu-sm dropdown-content bg-base-100 text-base-content rounded-box z-1 mt-3 w-52 p-2 shadow"
                        >
                            {navItems.map((item) => (
                                <li key={item.pattern}>
                                    <Link
                                        href={item.href}
                                        className={activeWhen(item.pattern)}
                                    >
                                        {item.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                    <Link
                        className="btn btn-ghost text-xl"
                        href={route('dashboard')}
                        aria-label={t('navigation.home')}
                    >
                        <ApplicationLogo className="h-10 w-auto fill-current" />
                    </Link>
                </div>
                <div className="navbar-center hidden lg:flex">
                    <ul className="menu menu-horizontal px-1">
                        {navItems.map((item) => (
                            <li key={item.pattern}>
                                <Link
                                    href={item.href}
                                    className={activeWhen(item.pattern)}
                                >
                                    {item.label}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
                <div className="navbar-end">
                    <ThemeToggle />
                    <TranslationToggle />

                    <Link
                        className="btn btn-ghost"
                        href="/logout"
                        method="post"
                        as="button"
                        aria-label={t('navigation.logout')}
                        title={t('navigation.logout')}
                    >
                        <FontAwesomeIcon icon={faRightFromBracket} />
                    </Link>
                </div>
            </div>

            <div className="bg-base-100/95 border-base-300 sticky top-16 z-20 flex items-center justify-between gap-4 border-b px-4 py-3 backdrop-blur md:px-12 lg:px-24">
                {action && action}

                <div className="items-left flex min-w-0 grow flex-col gap-0.5">
                    {breadcrumbs && <Breadcrumbs items={breadcrumbs} />}
                    <div className="truncate text-xl font-bold md:text-2xl">
                        {title}
                    </div>
                    {subtitle && (
                        <div className="text-base-content/60 truncate text-sm font-medium">
                            {subtitle}
                        </div>
                    )}
                </div>

                {actionRight && actionRight}
            </div>

            <div className="bg-base-200/50 flex-1 overflow-y-auto">
                {flashShown && <FlashAlert />}

                <ErrorBoundary>{children}</ErrorBoundary>
            </div>

            {/*
                AGPL section 13 requires that people using Padiush over a
                network can obtain its source. This is that offer, so it sits
                on every signed-in page rather than behind a menu.
            */}
            <footer className="bg-base-100 border-base-300 text-base-content/60 border-t px-4 py-2 text-center text-xs md:px-12">
                <Link
                    href={route('software.notice')}
                    className="link link-hover"
                >
                    {t('software.footer_link')}
                </Link>
            </footer>
        </div>
    );
}
