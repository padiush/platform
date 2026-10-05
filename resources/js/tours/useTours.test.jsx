import { act, render } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useTours } from './useTours';

global.route = (name, param) => `/${name}/${param ?? ''}`;

let mockProps = {};
const mockPost = vi.fn();
vi.mock('@inertiajs/react', () => ({
    router: { post: (...args) => mockPost(...args) },
    usePage: () => ({ props: mockProps }),
}));

const mockRuns = [];
vi.mock('./runTour', () => ({
    resolveSteps: (tour, tourId) =>
        tour.steps.map((step) => ({ tourId, step })),
    runTour: (steps, { onEnd }) => {
        const run = { steps, onEnd, cancel: vi.fn() };
        mockRuns.push(run);
        return run;
    },
}));

let replay = null;
function Page({ tour }) {
    replay = useTours(tour).replay;
    return null;
}

beforeEach(() => {
    vi.useFakeTimers();
    mockRuns.length = 0;
    mockPost.mockClear();
    mockProps = { tours: [], release: { version: '1.1.0', unseenSince: null } };
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
        mockProps.tours = ['welcome'];

        render(<Page tour="forms" />);
        await settle();

        expect(mockRuns[0].steps[0].tourId).toBe('forms');
    });

    it('starts nothing the user has already been through', async () => {
        mockProps.tours = ['welcome', 'forms'];

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

        expect(mockPost).toHaveBeenCalledWith(
            '/tours.done/welcome',
            {},
            expect.any(Object),
        );
    });

    it('replays a page’s tour on demand without marking anything', async () => {
        mockProps.tours = ['welcome', 'forms'];
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
});
