import { compareVersions } from '@/lib/releases';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import { describe, expect, it } from 'vitest';

const LOCALES = ['es', 'en', 'pt'];

const read = (file) =>
    JSON.parse(readFileSync(path.resolve(process.cwd(), file), 'utf8'));

const notes = Object.fromEntries(
    LOCALES.map((locale) => [
        locale,
        read(`public/locales/whatsnew/${locale}.json`).releases,
    ]),
);

const version = read('package.json').version;

/** Every key path in a catalogue, ignoring values. */
function keyPaths(value, prefix = '') {
    if (value === null || typeof value !== 'object' || Array.isArray(value)) {
        return [prefix];
    }
    return Object.entries(value).flatMap(([key, child]) =>
        keyPaths(child, prefix ? `${prefix}.${key}` : key),
    );
}

describe('the main translation catalogues', () => {
    it.each(['en', 'pt'])('%s has exactly the keys Spanish has', (locale) => {
        expect(keyPaths(read(`public/locales/${locale}.json`)).sort()).toEqual(
            keyPaths(read('public/locales/es.json')).sort(),
        );
    });
});

/**
 * Release notes are the one place users read about a release, so a language
 * that drops a bullet, or dates a release differently, is a release
 * announced differently depending on who reads it.
 */
describe('release notes', () => {
    it.each(['en', 'pt'])(
        '%s announces the same releases, on the same days, with as many points',
        (locale) => {
            const shape = (releases) =>
                releases.map(({ version, date, items }) => ({
                    version,
                    date,
                    items: items.length,
                }));

            expect(shape(notes[locale])).toEqual(shape(notes.es));
        },
    );

    it.each(LOCALES)('%s leaves no point empty', (locale) => {
        for (const release of notes[locale]) {
            expect(release.items.length, release.version).toBeGreaterThan(0);
            for (const item of release.items) {
                expect(item.trim(), release.version).not.toBe('');
            }
        }
    });

    it('lists releases newest first', () => {
        const versions = notes.es.map((release) => release.version);
        expect([...versions].sort((a, b) => compareVersions(b, a))).toEqual(
            versions,
        );
    });

    /**
     * Notes are drafted ahead of a release and dated when it ships. A release
     * that is running with no date was shipped without finishing its notes.
     */
    it('dates every release up to the one in package.json, and none after', () => {
        for (const release of notes.es) {
            const shipped = compareVersions(release.version, version) <= 0;
            expect(Boolean(release.date), release.version).toBe(shipped);
        }
    });
});
