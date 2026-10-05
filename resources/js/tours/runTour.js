import { driver } from 'driver.js';

/**
 * Whether an element is on screen to point at. On a phone the sidebar is a
 * closed drawer, so its links exist but cannot be seen; a step aimed at one
 * is shown as a card in the middle of the screen instead.
 */
export function isVisible(element) {
    if (!element) return false;
    const box = element.getBoundingClientRect();
    return (
        box.width > 0 &&
        box.height > 0 &&
        box.right > 0 &&
        box.left < window.innerWidth &&
        window.getComputedStyle(element).visibility !== 'hidden'
    );
}

/**
 * Turn a tour's steps into driver.js steps: words from the translations, and
 * each step pointed at its element when that element is there and visible.
 * A step that only makes sense next to its element (`requires: true`) is left
 * out when the element is not on the page — a section the user's role does
 * not open, an empty state.
 */
export function resolveSteps(tour, tourId, t) {
    return tour.steps
        .map((step) => {
            const element = step.element
                ? document.querySelector(step.element)
                : null;
            const visible = isVisible(element);

            if (step.element && step.requires && !element) {
                return null;
            }

            const body = t(`tours.${tourId}.${step.key}.body`);

            return {
                element: visible ? element : undefined,
                popover: {
                    title: t(`tours.${tourId}.${step.key}.title`),
                    // Its element is there but out of sight — in the phone's
                    // closed menu — so the card says where to find it.
                    description:
                        element && !visible
                            ? `${body} ${t('tours.in_menu')}`
                            : body,
                    side: step.side,
                    align: 'start',
                },
            };
        })
        .filter(Boolean);
}

/**
 * Run one tour. Finishing it, closing it or pressing Saltar all end it the
 * same way, through `onEnd`, so it is never offered again unasked. `cancel`
 * takes it down without calling `onEnd`: for a page left mid-tour, which
 * should offer the tour again next time.
 *
 * @returns {{ cancel: () => void }}
 */
export function runTour(steps, { t, onEnd }) {
    let ended = false;
    const end = () => {
        if (!ended) {
            ended = true;
            onEnd?.();
        }
    };

    const tour = driver({
        steps,
        showProgress: steps.length > 1,
        progressText: t('tours.progress', {
            current: '{{current}}',
            total: '{{total}}',
        }),
        nextBtnText: t('tours.next'),
        prevBtnText: t('tours.back'),
        doneBtnText: t('tours.done'),
        allowClose: true,
        overlayOpacity: 0.55,
        stagePadding: 6,
        stageRadius: 10,
        popoverClass: 'padiush-tour',
        // The close (×) is there for those who look for it; Saltar says what
        // it does in words, next to the steps' own buttons.
        onPopoverRender: (popover, { driver: active }) => {
            // Nothing to go back to on the first step: no button to say so.
            if (active.isFirstStep()) {
                popover.previousButton.style.display = 'none';
            }
            if (active.isLastStep()) return;
            const skip = document.createElement('button');
            skip.type = 'button';
            skip.className = 'padiush-tour-skip';
            skip.textContent = t('tours.skip');
            skip.addEventListener('click', () => active.destroy());
            popover.footerButtons.prepend(skip);
        },
        onDestroyed: end,
    });

    tour.drive();

    return {
        cancel: () => {
            ended = true;
            tour.destroy();
        },
    };
}
