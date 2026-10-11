import ApplicationFullLogo from '@/Components/ApplicationFullLogo';
import LanguageMenu from '@/Components/LanguageMenu';
import ThemeToggle from '@/Components/ThemeToggle';
import { useFlashMessage } from '@/Hooks/useFlashMessage';
import { faLinkedin } from '@fortawesome/free-brands-svg-icons';
import { faBars } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function PublicLayout({ children, title }) {
    const { FlashAlert, flashShown } = useFlashMessage();
    const { auth, publicSiteUrl } = usePage().props;
    const { t } = useTranslation();

    // The landing and About pages are a separate site (PUBLIC_SITE_URL), not
    // pages of this application. Links to it have to be ordinary anchors: an
    // Inertia <Link> fetches the target over XHR, and a page that is not an
    // Inertia response then opens in a modal over this one.
    const home = publicSiteUrl ? `${publicSiteUrl}/` : null;

    const links = [
        home && {
            key: 'home',
            href: home,
            label: t('public.home'),
            site: true,
        },
        {
            key: 'contact',
            href: route('public.contact'),
            label: t('public.contact'),
        },
        auth?.user
            ? {
                  key: 'account',
                  href: route('dashboard'),
                  label: t('public.user_area'),
              }
            : { key: 'login', href: route('login'), label: t('public.login') },
    ].filter(Boolean);

    return (
        <div className="bg-base-100 text-base-content min-h-screen">
            <Head title={title} />
            <header className="navbar bg-primary text-primary-content sticky top-0 z-40 gap-1">
                <div className="navbar-start">
                    <div className="dropdown lg:hidden">
                        <button
                            type="button"
                            className="btn btn-ghost btn-square"
                            aria-label={t('navigation.open_menu')}
                            aria-controls="public-mobile-navigation"
                        >
                            <FontAwesomeIcon
                                icon={faBars}
                                className="text-xl"
                            />
                        </button>
                        <ul
                            id="public-mobile-navigation"
                            tabIndex={0}
                            className="menu dropdown-content bg-base-200 text-base-content rounded-box z-50 mt-3 w-56 p-2 shadow-sm"
                        >
                            {links.map((link) => (
                                <li key={link.key}>
                                    <NavLink link={link} />
                                </li>
                            ))}
                        </ul>
                    </div>
                    {home ? (
                        <a
                            className="btn btn-ghost px-2"
                            href={home}
                            aria-label="Padiush"
                        >
                            <ApplicationFullLogo className="h-10 w-auto fill-current" />
                        </a>
                    ) : (
                        <Link
                            className="btn btn-ghost px-2"
                            href={route(auth?.user ? 'dashboard' : 'login')}
                            aria-label="Padiush"
                        >
                            <ApplicationFullLogo className="h-10 w-auto fill-current" />
                        </Link>
                    )}
                </div>
                {/* Same order as the public site's header: the links, then
                    the theme, then the language. */}
                <div className="navbar-end gap-1">
                    <ul className="menu menu-horizontal hidden px-1 lg:flex">
                        {links.map((link) => (
                            <li key={link.key}>
                                <NavLink link={link} />
                            </li>
                        ))}
                    </ul>
                    <ThemeToggle
                        className="btn-square"
                        iconClassName="text-xl"
                    />
                    <LanguageMenu />
                </div>
            </header>
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
                    <span className="footer-title">Padiush</span>
                    {home && (
                        <a className="link link-hover" href={home}>
                            {t('public.home')}
                        </a>
                    )}
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
                    {/* AGPL section 13 — the offer of source. */}
                    <Link
                        className="link link-hover"
                        href={route('software.notice')}
                    >
                        {t('software.footer_link')}
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

/** A header link: a plain anchor to the site, an Inertia link otherwise. */
function NavLink({ link }) {
    return link.site ? (
        <a href={link.href}>{link.label}</a>
    ) : (
        <Link href={link.href}>{link.label}</Link>
    );
}
