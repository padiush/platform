import { TOURS } from '@/tours/definitions';
import { resolveSteps, runTour } from '@/tours/runTour';
import { usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';

/** Long enough for a page's lists and charts to have drawn before pointing at them. */
const START_DELAY_MS = 700;

/** Only one tour on screen at a time, across every hook instance. */
let running = null;

/**
 * Tours finished in this page session. The server is told in the background —
 * a page visit would be cancelled by the user's next click — so until the
 * next page brings the server's list, this is what stops a tour starting
 * twice.
 */
const recorded = new Set();

/** Forget the tours finished here, when every tour is being offered again. */
export function forgetRecordedTours() {
    recorded.clear();
}

function markDone(tourIds) {
    tourIds.forEach((tourId) => recorded.add(tourId));
    window.axios
        .post(route('tours.done'), { tours: tourIds })
        // Not recorded: the tour is offered again next time, nothing worse.
        .catch(() => undefined);
}

/**
 * Start the tour a signed-in user has not been through yet, and offer the
 * page's tour on demand.
 *
 * The welcome tour comes first, on whatever page the user lands. For a user
 * with a project it includes the sidebar's project steps (`navigation`); a
 * user who started with none gets those the first time they have one. A
 * page's own tour starts on the first visit after that, never in the same
 * breath. Nothing starts while a "What's new?" dialog is waiting to be read,
 * and a tour that is finished, closed or skipped is not offered again
 * unasked.
 *
 * @param {string|null} pageTour the tour that explains the current page
 * @param {{ auto?: boolean }} options `auto: false` starts nothing on its own
 * @returns {{ replay: () => void, canReplay: boolean }}
 */
export function useTours(pageTour, { auto = true } = {}) {
    const { tours: completed, release, projectNav } = usePage().props;
    const { t } = useTranslation();
    const started = useRef(false);

    const whatsNewPending = Boolean(release?.unseenSince);
    const done = Array.isArray(completed) ? [...completed, ...recorded] : null;
    const hasProject = (projectNav?.projects?.length ?? 0) > 0;

    const next = !done
        ? null
        : !done.includes('welcome')
          ? 'welcome'
          : hasProject && !done.includes('navigation')
            ? 'navigation'
            : pageTour && TOURS[pageTour] && !done.includes(pageTour)
              ? pageTour
              : null;

    const start = useCallback(
        (tourId, { remember }) => {
            if (running || !TOURS[tourId]) return;

            let steps = resolveSteps(TOURS[tourId], tourId, t);
            const covered = [tourId];

            // The welcome folds in the project steps when there is a project
            // to show them on, right after its first card.
            if (tourId === 'welcome') {
                const project = resolveSteps(TOURS.navigation, 'navigation', t);
                if (project.length > 0) {
                    steps = [steps[0], ...project, ...steps.slice(1)];
                    covered.push('navigation');
                }
            }

            if (steps.length === 0) {
                if (remember) markDone(covered);
                return;
            }
            running = runTour(steps, {
                t,
                onEnd: () => {
                    running = null;
                    if (remember) markDone(covered);
                },
            });
        },
        [t],
    );

    useEffect(() => {
        if (!auto || !next || whatsNewPending || started.current) {
            return undefined;
        }
        started.current = true;
        const timer = setTimeout(
            () => start(next, { remember: true }),
            START_DELAY_MS,
        );
        return () => clearTimeout(timer);
    }, [auto, next, whatsNewPending, start]);

    // Leaving the page mid-tour takes the tour with it, unmarked: it is
    // offered again on the next visit.
    useEffect(
        () => () => {
            if (running) {
                const tour = running;
                running = null;
                tour.cancel();
            }
        },
        [],
    );

    const replay = useCallback(
        () => start(pageTour ?? 'welcome', { remember: false }),
        [pageTour, start],
    );

    return { replay, canReplay: Boolean(TOURS[pageTour ?? 'welcome']) };
}
