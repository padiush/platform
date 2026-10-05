import { readFileSync } from 'node:fs';
import path from 'node:path';
import { describe, expect, it } from 'vitest';
import { TOURS } from './definitions';

const read = (locale) =>
    JSON.parse(
        readFileSync(
            path.resolve(process.cwd(), `public/locales/${locale}.json`),
            'utf8',
        ),
    );

const catalogues = { es: read('es'), en: read('en'), pt: read('pt') };

describe('the guided tours', () => {
    it.each(Object.keys(catalogues))('say every step in %s', (locale) => {
        const words = catalogues[locale].tours;
        for (const [tour, { steps }] of Object.entries(TOURS)) {
            for (const step of steps) {
                const text = words?.[tour]?.[step.key];
                expect(text?.title?.trim(), `${tour}.${step.key}`).toBeTruthy();
                expect(text?.body?.trim(), `${tour}.${step.key}`).toBeTruthy();
            }
        }
    });

    it('name each step once within its tour', () => {
        for (const { steps } of Object.values(TOURS)) {
            const keys = steps.map((step) => step.key);
            expect(new Set(keys).size).toBe(keys.length);
        }
    });

    it('point only at elements marked for a tour', () => {
        for (const { steps } of Object.values(TOURS)) {
            for (const step of steps) {
                if (step.element) {
                    expect(step.element).toMatch(
                        /^\[data-tour="[a-z0-9-]+"\]$/,
                    );
                }
            }
        }
    });
});
