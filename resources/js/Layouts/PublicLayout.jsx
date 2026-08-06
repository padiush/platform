import ApplicationFullLogo from '@/Components/ApplicationFullLogo';
import ThemeToggle from '@/Components/ThemeToggle';
import TranslationToggle from '@/Components/TranslationToggle';
import { useFlashMessage } from '@/Hooks/useFlashMessage';
import { faLinkedin } from '@fortawesome/free-brands-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function PublicLayout({ children, title }) {
    const { FlashAlert, flashShown } = useFlashMessage();
    const { auth } = usePage().props;
    const { t } = useTranslation();

    return (
        <div className="bg-base-100 text-neutral-content min-h-screen">
            <Head title={title} />
            <div className="navbar bg-primary text-primary-content sticky top-0 z-40">
                <div className="navbar-start">
                    <div className="dropdown">
                        <label tabIndex={0} className="btn btn-ghost lg:hidden">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                className="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="2"
                                    d="M4 6h16M4 12h8m-8 6h16"
                                />
                            </svg>
                        </label>
                        <ul
                            tabIndex={0}
                            className="menu menu-compact dropdown-content bg-base-200 text-base-content rounded-box mt-3 w-52 p-2 shadow-sm"
                        >
                            {!route().current('public.index') && (
                                <li>
                                    <Link href={route('public.index')}>
                                        {t('public.home')}
                                    </Link>
                                </li>
                            )}
                            <li>
                                <Link href={route('public.about')}>
                                    {t('public.about')}
                                </Link>
                            </li>
                            <li>
                                <Link href={route('public.contact')}>
                                    {t('public.contact')}
                                </Link>
                            </li>
                            {auth?.user ? (
                                <li>
                                    <Link href={route('dashboard')}>
                                        {t('public.user_area')}
                                    </Link>
                                </li>
                            ) : (
                                <li>
                                    <Link href={route('login')}>
                                        {t('public.login')}
                                    </Link>
                                </li>
                            )}
                        </ul>
                    </div>
                    <Link
                        className="btn btn-ghost"
                        href={route('public.index')}
                    >
                        <ApplicationFullLogo className="h-10 w-auto fill-current" />
                    </Link>
                </div>
                <div className="navbar-end hidden lg:flex">
                    <ThemeToggle />
                    <TranslationToggle />
                    <ul className="menu menu-horizontal px-1">
                        {!route().current('public.index') && (
                            <li>
                                <Link href={route('public.index')}>
                                    {t('public.home')}
                                </Link>
                            </li>
                        )}
                        <li>
                            <Link href={route('public.about')}>
                                {t('public.about')}
                            </Link>
                        </li>
                        <li>
                            <Link href={route('public.contact')}>
                                {t('public.contact')}
                            </Link>
                        </li>
                        {auth?.user ? (
                            <li>
                                <Link href={route('dashboard')}>
                                    {t('public.user_area')}
                                </Link>
                            </li>
                        ) : (
                            <li>
                                <Link href={route('login')}>
                                    {t('public.login')}
                                </Link>
                            </li>
                        )}
                    </ul>
                </div>
                <div className="navbar-end lg:hidden">
                    <ThemeToggle />
                    <TranslationToggle />
                </div>
            </div>
            <main className="z-0">
                {flashShown && <FlashAlert />}
                <div id="content">{children}</div>
            </main>
            <footer className="footer sm:footer-horizontal bg-base-300 text-base-content p-10">
                <aside>
                    <ApplicationFullLogo className="text-primary h-12 w-auto fill-current" />
                    <p className="text-base-content/70 max-w-xs">
                        {t('public.tagline')}
                    </p>
                </aside>
                <div>
                    <span className="footer-title">{t('public.about')}</span>
                    <Link
                        className="link link-hover"
                        href={route('public.about')}
                    >
                        {t('public.about')}
                    </Link>
                    <Link
                        className="link link-hover"
                        href={route('public.contact')}
                    >
                        {t('public.contact')}
                    </Link>
                    <span>{t('public.made_with_love')}</span>
                </div>
                <div>
                    <span className="footer-title">{t('public.legal')}</span>
                    <Link
                        className="link link-hover"
                        href={route('public.privacy')}
                    >
                        {t('public.privacy')}
                    </Link>
                    <Link
                        className="link link-hover"
                        href={route('public.terms')}
                    >
                        {t('public.terms')}
                    </Link>
                </div>
                <div>
                    <span className="footer-title">{t('public.social')}</span>
                    <div className="grid grid-flow-col gap-4 text-3xl">
                        <a
                            href="https://linkedin.com/company/padiushbio"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <FontAwesomeIcon icon={faLinkedin} />
                        </a>
                    </div>
                </div>
            </footer>
        </div>
    );
}
