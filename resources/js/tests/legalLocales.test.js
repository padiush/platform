import { existsSync, readFileSync } from 'node:fs';
import path from 'node:path';
import { describe, expect, it } from 'vitest';

const LOCALES = ['es', 'en', 'pt'];
const DOCUMENTS = ['privacy', 'terms'];

const pathFor = (locale) =>
    path.resolve(process.cwd(), `public/locales/legal/${locale}.json`);

/**
 * The legal documents are deployment configuration, not source: they name a
 * specific data controller, so the repository ships none and a clean checkout
 * has nothing here. These assertions guard an operator's own documents against
 * a translation quietly dropping a clause — which means they have nothing to
 * say when no documents are installed, rather than failing.
 */
const installed = LOCALES.every((locale) => existsSync(pathFor(locale)));

function load(locale) {
    return JSON.parse(readFileSync(pathFor(locale), 'utf8'));
}

/**
 * The structural fingerprint of a document: how many sections, and for each,
 * the block types and how many bullets they carry. Comparing shapes rather
 * than strings catches a translation that quietly drops a clause or a bullet
 * without demanding that translators keep identical wording.
 */
function shapeOf(document) {
    return document.sections.map((section) =>
        (section.blocks ?? []).map((block) => ({
            type: block.type ?? 'p',
            items: block.items?.length ?? 0,
        })),
    );
}

const documents = installed
    ? Object.fromEntries(LOCALES.map((locale) => [locale, load(locale)]))
    : {};

describe.skipIf(!installed)('legal locale files', () => {
    it.each(LOCALES)('%s declares an updated_on label', (locale) => {
        expect(documents[locale].updated_on).toContain('{{date}}');
    });

    it.each(DOCUMENTS)('%s has the same structure in every language', (doc) => {
        const reference = shapeOf(documents.es[doc]);

        expect(reference.length).toBeGreaterThan(0);

        for (const locale of LOCALES.filter((l) => l !== 'es')) {
            expect(shapeOf(documents[locale][doc]), locale).toEqual(reference);
        }
    });

    it.each(DOCUMENTS)('%s carries its own title and date', (doc) => {
        for (const locale of LOCALES) {
            const document = documents[locale][doc];

            expect(document.title, locale).toBeTruthy();
            expect(document.updated, locale).toBeTruthy();
            expect(document.summary, locale).toBeTruthy();
        }
    });

    it.each(DOCUMENTS)('%s gives every section a heading', (doc) => {
        for (const locale of LOCALES) {
            for (const section of documents[locale][doc].sections) {
                expect(section.heading, `${locale}/${doc}`).toBeTruthy();
            }
        }
    });

    it.each(DOCUMENTS)('%s keeps the contact links identical', (doc) => {
        const hrefsFor = (locale) =>
            documents[locale][doc].sections
                .flatMap((section) => section.blocks ?? [])
                .filter((block) => block.type === 'links')
                .flatMap((block) => block.items.map((item) => item.href));

        const reference = hrefsFor('es');

        expect(reference).toContain('mailto:hola@padiushbio.com');

        for (const locale of LOCALES.filter((l) => l !== 'es')) {
            expect(hrefsFor(locale), locale).toEqual(reference);
        }
    });
});
