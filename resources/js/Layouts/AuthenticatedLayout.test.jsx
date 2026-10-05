import { fireEvent, render, screen, within } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import AuthenticatedLayout from './AuthenticatedLayout';

// Ziggy's global helper is used two ways: route('name') for a URL, and a bare
// route() for the .current() check that marks the active section.
let currentRoute = null;
global.route = (name, params) =>
    name
        ? `/${name}${params !== undefined ? `/${params}` : ''}`
        : { current: (pattern) => matches(pattern, currentRoute) };

function matches(pattern, name) {
    if (!name) {
        return false;
    }
    return pattern.endsWith('*')
        ? name.startsWith(pattern.slice(0, -1))
        : name === pattern;
}

const mockPost = vi.fn();
let mockProps = {};

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    // `method` and `as` are Inertia's; kept off the plain anchor.
    // eslint-disable-next-line no-unused-vars
    Link: ({ href, children, method, as, ...rest }) => (
        <a href={href} {...rest}>
            {children}
        </a>
    ),
    router: { post: (...args) => mockPost(...args) },
    usePage: () => ({ props: mockProps }),
}));

vi.mock('@/Hooks/useFlashMessage', () => ({
    useFlashMessage: () => ({ FlashAlert: () => null, flashShown: false }),
}));

// Chrome that carries its own browser-API dependencies and is not under test.
vi.mock('@/Components/ThemeToggle', () => ({ default: () => null }));
vi.mock('@/Components/TranslationToggle', () => ({ default: () => null }));

const herbs = { id: 1, name: 'Hierbas', finished: false };
const trees = { id: 2, name: 'Árboles', finished: true };

function props(overrides = {}) {
    return {
        auth: { user: { id: 1, name: 'Investigadora', system_admin: false } },
        projectNav: {
            active: herbs,
            projects: [herbs, trees],
            sections: {
                overview: '/dashboard',
                records: '/catalogs.fieldRecords.index/1',
                catalog: '/catalogs.show/1',
            },
        },
        ...overrides,
    };
}

/** Pose as a screen wide enough for the sidebar to sit beside the page. */
function poseWide(wide) {
    window.matchMedia = vi.fn(() => ({
        matches: wide,
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
    }));
}

beforeEach(() => {
    mockProps = props();
    currentRoute = null;
    mockPost.mockReset();
    window.localStorage.clear();
    poseWide(true);
});

afterEach(() => {
    delete window.matchMedia;
});

const layout = () =>
    render(
        <AuthenticatedLayout title="Registros de campo">
            contenido
        </AuthenticatedLayout>,
    );

describe('AuthenticatedLayout', () => {
    /**
     * Section 13 of the AGPL requires that people using Padiush over a network
     * can obtain its source. Every signed-in page therefore has to carry the
     * offer — losing this link would be a licence violation, not a cosmetic
     * regression, so it is asserted rather than left to inspection.
     */
    it('offers the source of the running software on every signed-in page', () => {
        layout();

        const link = screen.getByRole('link', { name: 'software.footer_link' });

        expect(link).toBeInTheDocument();
        expect(link).toHaveAttribute('href', '/software.notice');
    });

    it('marks an example project as one, in the switcher and on the page', () => {
        const example = {
            id: 3,
            name: 'Ejemplo: plantas útiles',
            finished: false,
            is_example: true,
        };
        mockProps = props({
            projectNav: {
                active: example,
                projects: [herbs, example],
                sections: { overview: '/dashboard' },
            },
        });
        layout();

        expect(screen.getByText('example.notice')).toBeInTheDocument();
        expect(screen.getAllByText('example.badge').length).toBeGreaterThan(0);
    });

    it('says nothing about examples in a real project', () => {
        layout();

        expect(screen.queryByText('example.notice')).not.toBeInTheDocument();
        expect(screen.queryByText('example.badge')).not.toBeInTheDocument();
    });

    it('offers the sections the active project opens, and only those', () => {
        layout();

        expect(
            screen.getByRole('link', { name: 'navigation.field_records' }),
        ).toHaveAttribute('href', '/catalogs.fieldRecords.index/1');
        expect(
            screen.getByRole('link', { name: 'navigation.catalog' }),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'navigation.forms' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'navigation.interviews' }),
        ).not.toBeInTheDocument();
    });

    it('marks the section the page is in', () => {
        currentRoute = 'catalogs.fieldRecords.index';
        layout();

        expect(
            screen.getByRole('link', { name: 'navigation.field_records' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            screen.getByRole('link', { name: 'navigation.catalog' }),
        ).not.toHaveAttribute('aria-current');
    });

    it('counts the permits as field records, not as the catalog', () => {
        currentRoute = 'catalogs.permits.index';
        layout();

        expect(
            screen.getByRole('link', { name: 'navigation.field_records' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            screen.getByRole('link', { name: 'navigation.catalog' }),
        ).not.toHaveAttribute('aria-current');
    });

    it('marks the catalog on a species page', () => {
        currentRoute = 'catalogs.species.show';
        layout();

        expect(
            screen.getByRole('link', { name: 'navigation.catalog' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            screen.getByRole('link', { name: 'navigation.field_records' }),
        ).not.toHaveAttribute('aria-current');
    });

    it('offers the project’s administration to the roles that run it', () => {
        currentRoute = 'projects.edit';
        mockProps = props({
            projectNav: {
                active: herbs,
                projects: [herbs, trees],
                sections: {
                    overview: '/dashboard',
                    settings: '/projects.edit/1',
                    members: '/projects.accesses/1',
                },
            },
        });
        render(<AuthenticatedLayout title="Ajustes">page</AuthenticatedLayout>);

        expect(
            screen.getByRole('link', { name: 'navigation.settings' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            screen.getByRole('link', { name: 'navigation.members' }),
        ).toHaveAttribute('href', '/projects.accesses/1');
        // The list of projects is not where a project's settings are.
        expect(
            screen.getByRole('link', { name: 'navigation.projects' }),
        ).not.toHaveAttribute('aria-current');
    });

    it('opens the account from its owner’s name', () => {
        currentRoute = 'account.show';
        layout();

        const account = screen.getByRole('link', { name: /Investigadora/ });
        expect(account).toHaveAttribute('href', '/account.show');
        expect(account).toHaveAttribute('aria-current', 'page');
    });

    it('offers the system only to its administrators', () => {
        layout();
        expect(
            screen.queryByRole('link', { name: 'navigation.system_dashboard' }),
        ).not.toBeInTheDocument();
    });

    describe('the project switcher', () => {
        it('names the active project and lists finished ones apart', () => {
            layout();

            const switcher = screen.getByRole('button', {
                name: 'navigation.switch_project',
            });
            expect(switcher).toHaveTextContent('Hierbas');

            const menu = switcher.parentElement.querySelector('ul');
            const entries = within(menu)
                .getAllByRole('listitem')
                .map((item) => item.textContent);
            expect(entries).toEqual([
                'navigation.projects_in_progress',
                'Hierbas',
                'navigation.projects_finished',
                'Árboles',
                'navigation.all_projects',
            ]);
        });

        /** From one project's field records to the other's. */
        it('switches project, keeping the section', () => {
            currentRoute = 'catalogs.fieldRecords.index';
            layout();

            fireEvent.click(screen.getByRole('button', { name: 'Árboles' }));

            expect(mockPost).toHaveBeenCalledWith('/projects.activate/2', {
                section: 'records',
            });
        });

        it('does nothing when the active project is chosen again', () => {
            layout();

            fireEvent.click(screen.getByRole('button', { name: 'Hierbas' }));

            expect(mockPost).not.toHaveBeenCalled();
        });

        it('is not shown to someone with no project', () => {
            mockProps = props({
                projectNav: { active: null, projects: [], sections: {} },
            });
            layout();

            expect(
                screen.queryByRole('button', {
                    name: 'navigation.switch_project',
                }),
            ).not.toBeInTheDocument();
        });
    });

    describe('folding the sidebar', () => {
        it('folds to icons, keeping each section named, and remembers it', () => {
            window.innerWidth = 1440;
            layout();

            fireEvent.click(
                screen.getByRole('button', {
                    name: 'navigation.collapse_menu',
                }),
            );

            const records = screen.getByRole('link', {
                name: 'navigation.field_records',
            });
            expect(records).toHaveAttribute(
                'title',
                'navigation.field_records',
            );
            expect(records).not.toHaveTextContent('navigation.field_records');
            expect(window.localStorage.getItem('padiush.sidebar.rail')).toBe(
                '1',
            );
        });

        /** Long study titles need the room: folded, the project unfolds it. */
        it('unfolds to switch project', () => {
            window.innerWidth = 1440;
            window.localStorage.setItem('padiush.sidebar.rail', '1');
            layout();

            fireEvent.click(
                screen.getByRole('button', {
                    name: 'navigation.switch_project',
                }),
            );

            expect(
                screen.getByRole('button', {
                    name: 'navigation.collapse_menu',
                }),
            ).toBeInTheDocument();
            expect(window.localStorage.getItem('padiush.sidebar.rail')).toBe(
                '0',
            );
        });

        it('starts folded where it was left folded', () => {
            window.innerWidth = 1440;
            window.localStorage.setItem('padiush.sidebar.rail', '1');
            layout();

            expect(
                screen.getByRole('button', { name: 'navigation.expand_menu' }),
            ).toBeInTheDocument();
        });

        /** Folded, the account sits in the same column as every other icon. */
        it('centres the account when folded', () => {
            window.innerWidth = 1440;
            window.localStorage.setItem('padiush.sidebar.rail', '1');
            layout();

            const account = screen.getByRole('link', {
                name: 'navigation.account',
            });
            expect(account).toHaveClass('justify-center');
            expect(account).not.toHaveClass('justify-start');
        });

        /** On a phone it opens over the page, labels and all. */
        it('does not fold on a phone', () => {
            poseWide(false);
            window.localStorage.setItem('padiush.sidebar.rail', '1');
            layout();

            expect(
                screen.queryByRole('button', {
                    name: 'navigation.collapse_menu',
                }),
            ).not.toBeInTheDocument();
            expect(
                screen.getByRole('link', { name: 'navigation.field_records' }),
            ).toHaveTextContent('navigation.field_records');
        });
    });
});
