import Breadcrumbs from '@/Components/Breadcrumbs';
import ErrorBoundary from '@/Components/ErrorBoundary';
import { useFlashMessage } from '@/Hooks/useFlashMessage';
import Sidebar from '@/Layouts/Partials/Sidebar';
import { faBars } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Head, Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

const RAIL_KEY = 'padiush.sidebar.rail';

/** From this width the sidebar stays beside the page instead of over it. */
const WIDE = '(min-width: 768px)';

/**
 * Whether the sidebar starts folded to icons: as it was last left, or folded
 * on a tablet-sized screen, where its labels would cost the page too much.
 * Storage can be unavailable (private windows, blocked site data), and the
 * layout must still draw then.
 */
function initialRail() {
    try {
        const stored = window.localStorage.getItem(RAIL_KEY);
        if (stored !== null) {
            return stored === '1';
        }
    } catch {
        // No stored choice to honour.
    }

    return typeof window !== 'undefined' && window.innerWidth < 1024;
}

function useIsWide() {
    const [wide, setWide] = useState(
        () =>
            typeof window !== 'undefined' &&
            Boolean(window.matchMedia?.(WIDE).matches),
    );

    useEffect(() => {
        const query = window.matchMedia?.(WIDE);
        if (!query) {
            return undefined;
        }

        const onChange = (event) => setWide(event.matches);
        query.addEventListener('change', onChange);
        return () => query.removeEventListener('change', onChange);
    }, []);

    return wide;
}

/**
 * Every signed-in page: the sidebar, the page's header, and the page.
 *
 * The sidebar sits beside the page from tablet width up, and can fold to
 * icons there; on a phone it opens over the page from the menu button.
 */
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
    const { t } = useTranslation();
    const { FlashAlert, flashShown } = useFlashMessage();

    const wide = useIsWide();
    const [rail, setRail] = useState(initialRail);

    const toggleRail = () =>
        setRail((folded) => {
            const next = !folded;
            try {
                window.localStorage.setItem(RAIL_KEY, next ? '1' : '0');
            } catch {
                // Remembered for this page only.
            }
            return next;
        });

    return (
        <div className="drawer md:drawer-open h-screen">
            <Head
                title={headTitle ?? (typeof title === 'string' ? title : '')}
            />
            <input
                id="app-drawer"
                type="checkbox"
                className="drawer-toggle"
                aria-hidden="true"
            />

            <div className="drawer-content flex h-screen min-w-0 flex-col overflow-hidden">
                {/*
                    One compact row for the page: the project is in the
                    sidebar, so the header carries only where in it you are.
                    On a phone the menu button joins this row rather than
                    taking one of its own.
                */}
                <div className="bg-base-100/95 border-base-300 z-20 flex min-h-14 items-center gap-2 border-b px-2 py-1.5 backdrop-blur md:gap-3 md:px-6">
                    <label
                        htmlFor="app-drawer"
                        className="btn btn-ghost btn-square btn-sm md:hidden"
                        aria-label={t('navigation.open_menu')}
                    >
                        <FontAwesomeIcon icon={faBars} />
                    </label>

                    {action && action}

                    <div className="flex min-w-0 grow flex-col">
                        {breadcrumbs && (
                            <div className="hidden min-w-0 md:block">
                                <Breadcrumbs items={breadcrumbs} compact />
                            </div>
                        )}
                        <div className="truncate text-lg leading-tight font-bold md:text-xl">
                            {title}
                        </div>
                        {subtitle && (
                            <div className="text-base-content/60 hidden truncate text-xs font-medium sm:block">
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
                <footer className="bg-base-100 border-base-300 text-base-content/60 border-t px-4 py-2 text-center text-xs md:px-8">
                    <Link
                        href={route('software.notice')}
                        className="link link-hover"
                    >
                        {t('software.footer_link')}
                    </Link>
                </footer>
            </div>

            <div className="drawer-side z-40">
                <label
                    htmlFor="app-drawer"
                    className="drawer-overlay"
                    aria-label={t('navigation.close_menu')}
                />
                <Sidebar
                    rail={wide && rail}
                    onToggleRail={wide ? toggleRail : null}
                />
            </div>
        </div>
    );
}
