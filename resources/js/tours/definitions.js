/**
 * The guided tours, by name. Each step points at an element marked with a
 * `data-tour` attribute, or at nothing (a card in the middle of the screen);
 * its words are `tours.<tour>.<step>.title` and `.body` in the translation
 * files. A step with `requires: true` is left out when its element is not on
 * the page — a section the user's role does not open, an empty list — rather
 * than describing something the user cannot see.
 *
 * The names must match User::TOURS on the server, which remembers who has
 * been through which; a test holds the two lists together.
 */
export const TOURS = {
    welcome: {
        // The sidebar's project steps (`navigation`) are folded in after the
        // first step when the user already has a project; see useTours.
        steps: [
            { key: 'intro' },
            {
                key: 'start',
                element: '[data-tour="dashboard-projects"]',
                requires: true,
            },
            {
                key: 'example',
                element: '[data-tour="dashboard-example"]',
                requires: true,
            },
            {
                key: 'account',
                element: '[data-tour="nav-account"]',
                side: 'right',
            },
            { key: 'help', element: '[data-tour="help"]', side: 'bottom' },
        ],
    },
    /**
     * The project switcher and the sections it opens. Part of the welcome for
     * a user who has a project; on its own, the first time a user who started
     * with none has one.
     */
    navigation: {
        steps: [
            {
                key: 'switcher',
                element: '[data-tour="project-switcher"]',
                requires: true,
                side: 'right',
            },
            {
                key: 'sections',
                element: '[data-tour="nav-sections"]',
                requires: true,
                side: 'right',
            },
            {
                key: 'admin',
                element: '[data-tour="nav-admin"]',
                requires: true,
                side: 'right',
            },
        ],
    },
    projects: {
        steps: [
            { key: 'what' },
            {
                key: 'invites',
                element: '[data-tour="projects-invites"]',
                requires: true,
            },
            {
                key: 'list',
                element: '[data-tour="projects-list"]',
                requires: true,
            },
            { key: 'create', element: '[data-tour="projects-create"]' },
            // Offered only while the user has no example project open.
            {
                key: 'example',
                element: '[data-tour="projects-example"]',
                requires: true,
            },
        ],
    },
    overview: {
        steps: [
            // First, inside the example project: none of this is real.
            {
                key: 'example',
                element: '[data-tour="example-notice"]',
                requires: true,
            },
            { key: 'stats', element: '[data-tour="overview-stats"]' },
            {
                key: 'actions',
                element: '[data-tour="overview-actions"]',
                requires: true,
            },
            { key: 'pending', element: '[data-tour="overview-pending"]' },
            {
                key: 'recent',
                element: '[data-tour="overview-recent"]',
                requires: true,
            },
        ],
    },
    forms: {
        steps: [
            { key: 'new', element: '[data-tour="forms-new"]' },
            {
                key: 'design',
                element: '[data-tour="forms-design"]',
                requires: true,
            },
            {
                key: 'toggle',
                element: '[data-tour="forms-toggle"]',
                requires: true,
            },
            { key: 'capture' },
        ],
    },
    designer: {
        steps: [
            {
                key: 'sections',
                element: '[data-tour="designer-sections"]',
                side: 'right',
            },
            {
                key: 'fields',
                element: '[data-tour="designer-add-field"]',
                requires: true,
            },
            { key: 'save', element: '[data-tour="designer-save-state"]' },
            { key: 'preview', element: '[data-tour="designer-preview"]' },
        ],
    },
    interviews: {
        steps: [
            { key: 'forms', element: '[data-tour="interviews-forms"]' },
            {
                key: 'new',
                element: '[data-tour="interviews-new"]',
                requires: true,
            },
            {
                key: 'existing',
                element: '[data-tour="interviews-existing"]',
                requires: true,
            },
            { key: 'companion' },
        ],
    },
    records: {
        steps: [
            { key: 'what' },
            { key: 'tabs', element: '[data-tour="records-tabs"]' },
            { key: 'summary', element: '[data-tour="records-summary"]' },
            { key: 'filters', element: '[data-tour="records-filters"]' },
            {
                key: 'add',
                element: '[data-tour="records-add"]',
                requires: true,
            },
        ],
    },
    permits: {
        steps: [
            { key: 'what', element: '[data-tour="permits-card"]' },
            {
                key: 'add',
                element: '[data-tour="permits-add"]',
                requires: true,
            },
        ],
    },
    catalog: {
        steps: [
            { key: 'metrics', element: '[data-tour="catalog-metrics"]' },
            {
                key: 'register',
                element: '[data-tour="catalog-register"]',
                requires: true,
            },
            {
                key: 'filters',
                element: '[data-tour="catalog-filters"]',
                requires: true,
            },
            {
                key: 'list',
                element: '[data-tour="catalog-list"]',
                requires: true,
            },
        ],
    },
    data: {
        steps: [
            { key: 'tabs', element: '[data-tour="data-tabs"]' },
            {
                key: 'table',
                element: '[data-tour="data-tab-table"]',
                requires: true,
            },
            {
                key: 'link',
                element: '[data-tour="data-tab-link"]',
                requires: true,
            },
            {
                key: 'reports',
                element: '[data-tour="data-tab-reports"]',
                requires: true,
            },
            {
                key: 'export',
                element: '[data-tour="data-tab-export"]',
                requires: true,
            },
        ],
    },
    members: {
        steps: [
            { key: 'invite', element: '[data-tour="members-invite"]' },
            { key: 'list', element: '[data-tour="members-list"]' },
            {
                key: 'pending',
                element: '[data-tour="members-pending"]',
                requires: true,
            },
        ],
    },
};
