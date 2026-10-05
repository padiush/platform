import { act, render } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { forgetRecordedTours, useTours } from './useTours';

global.route = (name, param) => `/${name}/${param ?? ''}`;

let mockProps = {};
const mockPost = vi.fn(() => Promise.resolve());
window.axios = { post: (...args) => mockPost(...args) };
vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: mockProps }),
}));

const mockRuns = [];
/** Tours whose elements are not on the page, so they resolve to no steps. */
const mockResolveEmpty = new Set();
vi.mock('./runTour', () => ({
    resolveSteps: (tour, tourId) =>
        mockResolveEmpty.has(tourId)
            ? []
            : tour.steps.map((step) => ({ tourId, step })),
    runTour: (steps, { onEnd }) => {
        const run = { steps, onEnd, cancel: vi.fn() };
        mockRuns.push(run);
        return run;
    },
}));

let replay = null;
function Page({ tour, auto = true }) {
    replay = useTours(tour, { auto }).replay;
    return null;
}

beforeEach(() => {
    vi.useFakeTimers();
    mockRuns.length = 0;
    mockResolveEmpty.clear();
    mockPost.mockClear();
    forgetRecordedTours();
    mockProps = {
        tours: [],
        release: { version: '1.1.0', unseenSince: null },
        projectNav: { projects: [{ id: 1 }] },
    };
});

afterEach(() => {
    // End whatever is running, so the next test starts clean.
    mockRuns.forEach((run) => run.onEnd());
    vi.useRealTimers();
});

const settle = () => act(() => vi.advanceTimersByTime(1000));

describe('useTours', () => {
    it('welcomes a user before explaining any page', async () => {
        render(<Page tour="forms" />);
        await settle();

        expect(mockRuns).toHaveLength(1);
        expect(mockRuns[0].steps[0].tourId).toBe('welcome');
    });

    it('explains a page once the welcome is behind them', async () => {
        mockProps.tours = ['welcome', 'navigation'];

        render(<Page tour="forms" />);
        await settle();

        expect(mockRuns[0].steps[0].tourId).toBe('forms');
    });

    it('starts nothing the user has already been through', async () => {
        mockProps.tours = ['welcome', 'navigation', 'forms'];

        render(<Page tour="forms" />);
        await settle();

        expect(mockRuns).toHaveLength(0);
    });

    /** Two overlays at once would be one too many. */
    it('waits while a What’s new dialog is open', async () => {
        mockProps.release.unseenSince = '1.0.0';

        render(<Page tour="forms" />);
        await settle();

        expect(mockRuns).toHaveLength(0);
    });

    it('remembers a tour once it ends, however it ended', async () => {
        render(<Page tour="forms" />);
        await settle();

        mockRuns[0].onEnd();

        expect(mockPost).toHaveBeenCalledWith('/tours.done/', {
            tours: ['welcome', 'navigation'],
        });
    });

    it('replays a page’s tour on demand without marking anything', async () => {
        mockProps.tours = ['welcome', 'navigation', 'forms'];
        render(<Page tour="forms" />);
        await settle();

        act(() => replay());
        mockRuns[0].onEnd();

        expect(mockRuns[0].steps[0].tourId).toBe('forms');
        expect(mockPost).not.toHaveBeenCalled();
    });

    /** Left mid-tour, it is offered again next time. */
    it('takes a running tour down unmarked when the page goes', async () => {
        const { unmount } = render(<Page tour="forms" />);
        await settle();
        const run = mockRuns.pop();

        unmount();

        expect(run.cancel).toHaveBeenCalled();
        expect(mockPost).not.toHaveBeenCalled();
    });

    /** With a project, the welcome shows the sidebar's project steps too. */
    it('folds the project steps into the welcome when there is a project', async () => {
        render(<Page tour="forms" />);
        await settle();

        const tours = mockRuns[0].steps.map((step) => step.tourId);
        expect(tours[0]).toBe('welcome');
        expect(tours[1]).toBe('navigation');
    });

    it('welcomes a user with no project without them', async () => {
        mockProps.projectNav = { projects: [] };
        mockResolveEmpty.add('navigation');

        render(<Page tour={null} />);
        await settle();
        mockRuns[0].onEnd();

        expect(
            mockRuns[0].steps.every((step) => step.tourId === 'welcome'),
        ).toBe(true);
        expect(mockPost).toHaveBeenCalledWith('/tours.done/', {
            tours: ['welcome'],
        });
    });

    /** Started with no project: the sidebar is explained once there is one. */
    it('shows the project steps the first time a user has a project', async () => {
        mockProps.tours = ['welcome'];

        render(<Page tour="overview" />);
        await settle();

        expect(mockRuns[0].steps[0].tourId).toBe('navigation');
    });

    /** The user's next click must not undo it, nor the tour start again. */
    it('does not start a tour again before the server’s list catches up', async () => {
        const first = render(<Page tour="forms" />);
        await settle();
        mockRuns[0].onEnd();
        first.unmount();

        render(<Page tour="forms" />);
        await settle();

        // The props still say nothing is done; the next tour is the page's.
        expect(mockRuns.at(-1).steps[0].tourId).toBe('forms');
    });

    /** Someone who opened the release notes came to read them. */
    it('starts nothing on a page that asks not to', async () => {
        render(<Page tour={null} auto={false} />);
        await settle();

        expect(mockRuns).toHaveLength(0);
    });
});
