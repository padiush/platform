import { TOURS } from '@/tours/definitions';
import { resolveSteps, runTour } from '@/tours/runTour';
import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';

/** Long enough for a page's lists and charts to have drawn before pointing at them. */
const START_DELAY_MS = 700;

/** Only one tour on screen at a time, across every hook instance. */
let running = null;

function markDone(tourId) {
    router.post(
        route('tours.done', tourId),
        {},
        { preserveScroll: true, preserveState: true },
    );
}

/**
 * Start the tour a signed-in user has not been through yet, and offer the
 * page's tour on demand.
 *
 * The welcome tour comes first, on whatever page the user lands; a page's own
 * tour starts on the first visit after that, never in the same breath. Nothing
 * starts while a "What's new?" dialog is waiting to be read, and a tour that
 * is finished, closed or skipped is not offered again unasked.
 *
 * @param {string|null} pageTour the tour that explains the current page
 * @returns {{ replay: () => void, canReplay: boolean }}
 */
export function useTours(pageTour) {
    const { tours: completed, release } = usePage().props;
    const { t } = useTranslation();
    const started = useRef(false);

    const whatsNewPending = Boolean(release?.unseenSince);
    const done = Array.isArray(completed) ? completed : null;

    const next = !done
        ? null
        : !done.includes('welcome')
          ? 'welcome'
          : pageTour && TOURS[pageTour] && !done.includes(pageTour)
            ? pageTour
            : null;

    const start = useCallback(
        (tourId, { remember }) => {
            if (running || !TOURS[tourId]) return;
            const steps = resolveSteps(TOURS[tourId], tourId, t);
            if (steps.length === 0) {
                if (remember) markDone(tourId);
                return;
            }
            running = runTour(steps, {
                t,
                onEnd: () => {
                    running = null;
                    if (remember) markDone(tourId);
                },
            });
        },
        [t],
    );

    useEffect(() => {
        if (!next || whatsNewPending || started.current) {
            return undefined;
        }
        started.current = true;
        const timer = setTimeout(
            () => start(next, { remember: true }),
            START_DELAY_MS,
        );
        return () => clearTimeout(timer);
    }, [next, whatsNewPending, start]);

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
