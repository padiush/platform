import { faChevronDown, faFolderOpen } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

/**
 * The project the sidebar is open on, and the way to another one. Switching
 * keeps the section: from one project's field records to the other's, when
 * the other project offers them to this user.
 *
 * Finished projects are listed apart, after the ones still in progress: they
 * are still read, reported on and exported, just no longer added to.
 */
export default function ProjectSwitcher({
    nav,
    section,
    rail = false,
    onExpand = null,
}) {
    const { t } = useTranslation();

    const active = nav?.active ?? null;
    const projects = nav?.projects ?? [];
    const inProgress = projects.filter((project) => !project.finished);
    const finished = projects.filter((project) => project.finished);

    const activate = (project) => {
        // Close the dropdown: focus is what keeps a daisyUI dropdown open.
        document.activeElement?.blur();

        if (project.id === active?.id) {
            return;
        }

        router.post(route('projects.activate', project.id), { section });
    };

    const item = (project) => (
        <li key={project.id}>
            <button
                type="button"
                onClick={() => activate(project)}
                className={project.id === active?.id ? 'menu-active' : ''}
                aria-current={project.id === active?.id ? 'true' : undefined}
            >
                {/* Study titles run long; listed whole, not cut short. */}
                <span className="block w-full min-w-0 text-left break-words whitespace-normal">
                    {project.name}
                    {project.is_example && (
                        <span className="badge badge-info badge-soft badge-xs ml-2 align-middle">
                            {t('example.badge')}
                        </span>
                    )}
                </span>
            </button>
        </li>
    );

    const label = active?.name ?? t('navigation.no_project');

    /*
     * Folded, there is no room for the list: the sidebar clips whatever
     * reaches past its edge, and long study titles need the width. The
     * project's initial unfolds the sidebar, and the list opens from there.
     */
    if (rail) {
        return (
            <button
                type="button"
                onClick={onExpand}
                aria-label={t('navigation.switch_project')}
                title={label}
                className="btn btn-ghost bg-primary-content/10 hover:bg-primary-content/20 min-h-10 w-full border-0 px-0"
            >
                <span className="text-base font-bold" aria-hidden="true">
                    {label.charAt(0).toUpperCase()}
                </span>
            </button>
        );
    }

    return (
        <div className="dropdown w-full">
            <div
                tabIndex={0}
                role="button"
                aria-label={t('navigation.switch_project')}
                title={label}
                className="btn btn-ghost bg-primary-content/10 hover:bg-primary-content/20 h-auto min-h-10 w-full flex-nowrap justify-between border-0 py-2"
            >
                <span className="min-w-0 text-left">
                    <span className="block text-[0.65rem] font-semibold tracking-wide uppercase opacity-70">
                        {t('navigation.project')}
                    </span>
                    <span className="line-clamp-3 font-semibold break-words">
                        {label}
                    </span>
                    {active?.is_example && (
                        <span className="bg-primary-content/20 mt-1 inline-block rounded px-1.5 text-[0.65rem] font-semibold tracking-wide uppercase">
                            {t('example.badge')}
                        </span>
                    )}
                </span>
                <FontAwesomeIcon
                    icon={faChevronDown}
                    className="shrink-0 text-xs"
                />
            </div>
            <ul
                tabIndex={0}
                className="menu dropdown-content bg-base-100 text-base-content rounded-box z-50 mt-1 max-h-[70vh] w-full flex-nowrap overflow-y-auto p-2 shadow-lg"
            >
                {inProgress.length > 0 && (
                    <li className="menu-title">
                        {t('navigation.projects_in_progress')}
                    </li>
                )}
                {inProgress.map(item)}
                {finished.length > 0 && (
                    <li className="menu-title">
                        {t('navigation.projects_finished')}
                    </li>
                )}
                {finished.map(item)}
                <li className="border-base-300 mt-1 border-t pt-1">
                    <Link href={route('projects.index')}>
                        <FontAwesomeIcon icon={faFolderOpen} />
                        {t('navigation.all_projects')}
                    </Link>
                </li>
            </ul>
        </div>
    );
}
