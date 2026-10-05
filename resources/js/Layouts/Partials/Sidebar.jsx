import ApplicationLogo from '@/Components/ApplicationLogo';
import ThemeToggle from '@/Components/ThemeToggle';
import TranslationToggle from '@/Components/TranslationToggle';
import {
    faAnglesLeft,
    faAnglesRight,
    faBookOpen,
    faChartColumn,
    faCircleUser,
    faClipboardQuestion,
    faFolderOpen,
    faGauge,
    faGear,
    faPenRuler,
    faRightFromBracket,
    faSeedling,
    faServer,
    faUsers,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import ProjectSwitcher from './ProjectSwitcher';

/**
 * The project's sections, in the order the work goes: design the forms, run
 * the interviews, record in the field, identify against the catalog, analyse.
 * `patterns` are the route names that belong to each, for marking the one the
 * page is in.
 */
const SECTIONS = [
    {
        key: 'overview',
        label: 'navigation.overview',
        icon: faGauge,
        patterns: ['dashboard', 'projects.overview'],
    },
    {
        key: 'forms',
        label: 'navigation.forms',
        icon: faPenRuler,
        patterns: ['designer.*'],
    },
    {
        key: 'interviews',
        label: 'navigation.interviews',
        icon: faClipboardQuestion,
        patterns: ['interviews.*'],
    },
    {
        key: 'records',
        label: 'navigation.field_records',
        icon: faSeedling,
        patterns: ['catalogs.fieldRecords.*', 'catalogs.permits.*'],
    },
    {
        key: 'catalog',
        label: 'navigation.catalog',
        icon: faBookOpen,
        patterns: ['catalogs.index', 'catalogs.show', 'catalogs.species.*'],
    },
    {
        key: 'data',
        label: 'navigation.data',
        icon: faChartColumn,
        patterns: ['data.*'],
    },
];

/** The project's administration, for the roles that run it. */
const ADMIN_SECTIONS = [
    {
        key: 'settings',
        label: 'navigation.settings',
        icon: faGear,
        patterns: ['projects.edit'],
    },
    {
        key: 'members',
        label: 'navigation.members',
        icon: faUsers,
        patterns: ['projects.accesses', 'projects.accesses.*'],
    },
];

/** The section the current page belongs to, if any. */
export function currentSection() {
    const current = route();

    return (
        [...SECTIONS, ...ADMIN_SECTIONS].find((section) =>
            section.patterns.some((pattern) => current.current(pattern)),
        )?.key ?? null
    );
}

function NavItem({ href, icon, label, active, rail, method, as }) {
    return (
        <li>
            <Link
                href={href}
                method={method}
                as={as}
                // Folded, the icon is the whole target: a fuller one for a finger.
                className={`${active ? 'menu-active' : ''} ${rail ? 'justify-center py-3' : ''}`}
                aria-current={active ? 'page' : undefined}
                aria-label={rail ? label : undefined}
                title={rail ? label : undefined}
            >
                <FontAwesomeIcon
                    icon={icon}
                    className={rail ? 'text-base' : 'w-4'}
                    fixedWidth
                />
                {!rail && <span className="truncate">{label}</span>}
            </Link>
        </li>
    );
}

/**
 * The application's navigation: the project it is working in, that project's
 * sections, and below them what belongs to no single project.
 *
 * `rail` folds it to icons, which keeps a tablet's or a small laptop's width
 * for the page; the labels come back as tooltips and accessible names.
 */
export default function Sidebar({ rail = false, onToggleRail = null }) {
    const { t } = useTranslation();
    const { auth, projectNav } = usePage().props;

    const section = currentSection();
    const offered = projectNav?.sections ?? {};
    const sections = SECTIONS.filter(({ key }) => offered[key]);
    const adminSections = ADMIN_SECTIONS.filter(({ key }) => offered[key]);

    return (
        <aside
            className={`bg-primary text-primary-content flex h-full flex-col gap-3 p-3 transition-[width] duration-200 ${
                rail ? 'w-20' : 'w-72'
            }`}
            aria-label={t('navigation.menu')}
        >
            <div
                className={`flex items-center ${rail ? 'flex-col gap-2' : 'justify-between'}`}
            >
                <Link
                    href={route('dashboard')}
                    className="btn btn-ghost px-2"
                    aria-label={t('navigation.home')}
                >
                    <ApplicationLogo className="h-9 w-auto fill-current" />
                </Link>
                {onToggleRail && (
                    <button
                        type="button"
                        onClick={onToggleRail}
                        className="btn btn-ghost btn-sm btn-square"
                        aria-label={
                            rail
                                ? t('navigation.expand_menu')
                                : t('navigation.collapse_menu')
                        }
                        title={
                            rail
                                ? t('navigation.expand_menu')
                                : t('navigation.collapse_menu')
                        }
                    >
                        <FontAwesomeIcon
                            icon={rail ? faAnglesRight : faAnglesLeft}
                        />
                    </button>
                )}
            </div>

            {projectNav?.projects?.length > 0 && (
                <div data-tour="project-switcher">
                    <ProjectSwitcher
                        nav={projectNav}
                        section={section}
                        rail={rail}
                        onExpand={onToggleRail}
                    />
                </div>
            )}

            <nav className="min-h-0 flex-1 overflow-x-hidden overflow-y-auto">
                {/* Someone with no project yet has only Proyectos to go to. */}
                {sections.length > 0 && (
                    <>
                        <ul
                            className="menu w-full gap-1 p-0"
                            data-tour="nav-sections"
                        >
                            {sections.map(({ key, label, icon }) => (
                                <NavItem
                                    key={key}
                                    href={offered[key]}
                                    icon={icon}
                                    label={t(label)}
                                    active={section === key}
                                    rail={rail}
                                />
                            ))}
                        </ul>

                        {adminSections.length > 0 && (
                            <ul
                                className="menu mt-3 w-full gap-1 p-0"
                                data-tour="nav-admin"
                            >
                                {adminSections.map(({ key, label, icon }) => (
                                    <NavItem
                                        key={key}
                                        href={offered[key]}
                                        icon={icon}
                                        label={t(label)}
                                        active={section === key}
                                        rail={rail}
                                    />
                                ))}
                            </ul>
                        )}

                        <div className="border-primary-content/20 my-3 border-t" />
                    </>
                )}

                <ul className="menu w-full gap-1 p-0">
                    <NavItem
                        href={route('projects.index')}
                        icon={faFolderOpen}
                        label={t('navigation.projects')}
                        active={route().current('projects.index')}
                        rail={rail}
                    />
                    {auth.user.system_admin && (
                        <NavItem
                            href={route('system.index')}
                            icon={faServer}
                            label={t('navigation.system_dashboard')}
                            active={route().current('system.*')}
                            rail={rail}
                        />
                    )}
                </ul>
            </nav>

            <div className="border-primary-content/20 flex flex-col gap-1 border-t pt-3">
                {/* The account, under its owner's name. */}
                <Link
                    href={route('account.show')}
                    data-tour="nav-account"
                    className={`btn btn-ghost h-auto min-h-9 justify-start gap-2 px-2 py-1.5 font-medium ${
                        route().current('account.*')
                            ? 'bg-primary-content/15'
                            : ''
                    } ${rail ? 'justify-center' : ''}`}
                    aria-current={
                        route().current('account.*') ? 'page' : undefined
                    }
                    aria-label={rail ? t('navigation.account') : undefined}
                    title={t('navigation.account')}
                >
                    <FontAwesomeIcon icon={faCircleUser} className="shrink-0" />
                    {!rail && (
                        <span className="min-w-0 truncate text-left text-sm">
                            {auth.user.name}
                        </span>
                    )}
                </Link>
                <div
                    className={`flex items-center ${rail ? 'flex-col' : 'flex-wrap'} gap-1`}
                >
                    <ThemeToggle />
                    <TranslationToggle compact={rail} />
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
        </aside>
    );
}
