import { afterEach, describe, expect, it, vi } from 'vitest';
import { resolveSteps } from './runTour';

const t = (key) => key;

function place(tour, box = { width: 100, height: 40, left: 10, right: 110 }) {
    const element = document.createElement('div');
    element.setAttribute('data-tour', tour);
    element.getBoundingClientRect = () => box;
    document.body.appendChild(element);
    return element;
}

afterEach(() => {
    document.body.innerHTML = '';
});

describe('resolveSteps', () => {
    it('points each step at its element, with its words', () => {
        const element = place('a');
        const [step] = resolveSteps(
            { steps: [{ key: 'one', element: '[data-tour="a"]' }] },
            'demo',
            t,
        );

        expect(step.element).toBe(element);
        expect(step.popover.title).toBe('tours.demo.one.title');
        expect(step.popover.description).toBe('tours.demo.one.body');
    });

    /** A section the user's role does not open is not described to them. */
    it('leaves out a step whose element is not on the page, when it needs one', () => {
        const steps = resolveSteps(
            {
                steps: [
                    { key: 'intro' },
                    { key: 'gone', element: '[data-tour="x"]', requires: true },
                ],
            },
            'demo',
            t,
        );

        expect(steps).toHaveLength(1);
    });

    /** On a phone the sidebar is a closed drawer: the step becomes a card. */
    it('shows a step whose element is hidden as a card in the middle', () => {
        place('drawer', { width: 0, height: 0, left: -300, right: -300 });

        const [step] = resolveSteps(
            {
                steps: [
                    {
                        key: 'nav',
                        element: '[data-tour="drawer"]',
                        requires: true,
                    },
                ],
            },
            'demo',
            t,
        );

        expect(step.element).toBeUndefined();
        expect(step.popover.description).toBe(
            'tours.demo.nav.body tours.in_menu',
        );
    });
});

vi.mock('driver.js', () => ({ driver: vi.fn() }));
