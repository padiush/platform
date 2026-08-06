/**
 * Captures the product screenshots used on the public pages.
 *
 * Everything it photographs comes from DemoProjectSeeder — a fictional study
 * with invented informants — so no real interview data can leak onto the
 * marketing site.
 *
 * Runs on a developer machine, not in CI or the container: the Node image is
 * Alpine, where Puppeteer's bundled Chromium cannot run.
 *
 *   docker compose exec app php artisan db:seed --class=DemoProjectSeeder
 *   npx puppeteer browsers install chrome   # once
 *   npm run capture:screenshots
 *
 * Each shot is taken in both themes, because the page swaps them to match the
 * reader rather than dropping a bright white screenshot into a dark layout.
 */

import { mkdir } from 'node:fs/promises';
import path from 'node:path';
import puppeteer from 'puppeteer';

const BASE = process.env.PADIUSH_URL ?? 'http://localhost:8000';
const EMAIL = process.env.DEMO_EMAIL ?? 'demo@padiush.test';
const PASSWORD = process.env.DEMO_PASSWORD ?? 'demo-screenshots';
const OUT = path.resolve(import.meta.dirname, '../public/images/site');

// Retina, so the shots stay sharp when the page scales them down.
const VIEWPORT = { width: 1440, height: 900, deviceScaleFactor: 2 };
const THEMES = ['padiushlight', 'padiushdark'];

/** Chrome renders a caret and focus ring that read as artefacts in a still. */
const HIDE_ARTEFACTS = `
    *, *::before, *::after {
        caret-color: transparent !important;
        transition: none !important;
        animation: none !important;
    }
    *:focus, *:focus-visible { outline: none !important; }
`;

async function signIn(page) {
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle0' });
    await page.type('input[type="email"]', EMAIL);
    await page.type('input[type="password"]', PASSWORD);

    await page.click('button[type="submit"]');

    // Inertia submits over XHR and swaps the page client-side, so there is no
    // navigation event to await — watch the path instead.
    try {
        await page.waitForFunction(
            () => !window.location.pathname.startsWith('/login'),
            { timeout: 15000 },
        );
    } catch {
        const reason = await page.evaluate(
            () =>
                document.querySelector('[role="alert"]')?.textContent?.trim() ??
                'no error shown',
        );

        throw new Error(
            `Sign-in failed (${reason}). Has DemoProjectSeeder been run against this database?`,
        );
    }

    await page.waitForNetworkIdle({ idleTime: 500 });
}

/**
 * The seeder does not know what ids it will get, so the demo project is found
 * the way a person would: from the projects list.
 */
async function findDemoProject(page) {
    await page.goto(`${BASE}/catalogs`, { waitUntil: 'networkidle0' });

    const id = await page.evaluate(() => {
        const href = [...document.querySelectorAll('a[href*="/catalogs/"]')]
            .map((a) => a.getAttribute('href'))
            .find((h) => /\/catalogs\/\d+/.test(h));

        return href ? href.match(/\/catalogs\/(\d+)/)[1] : null;
    });

    if (!id) {
        throw new Error(
            'Could not find the demo project from the catalogs list.',
        );
    }

    return id;
}

/**
 * Marks the card holding the nth sizeable chart so it can be photographed on
 * its own. The charts sit below the fold, and a full-page screenshot of a
 * report is far too tall to use as a marketing visual.
 */
async function tagChartCard(page, index) {
    const found = await page.evaluate((wanted) => {
        const cards = [...document.querySelectorAll('svg')]
            .filter((svg) => svg.getBoundingClientRect().width > 200)
            .map((svg) => svg.closest('.card'))
            .filter(Boolean);

        const card = cards[wanted];
        if (!card) return false;

        card.dataset.shot = 'target';

        return true;
    }, index);

    if (!found) {
        throw new Error(`No chart card at index ${index}.`);
    }

    return '[data-shot="target"]';
}

async function capture(page, { name, url, theme, waitFor, chartIndex }) {
    await page.evaluateOnNewDocument((value) => {
        localStorage.setItem('theme', value);
    }, theme);

    await page.goto(url, { waitUntil: 'networkidle0' });
    await page.addStyleTag({ content: HIDE_ARTEFACTS });

    if (waitFor) {
        await page.waitForSelector(waitFor, { timeout: 15000 });
    }

    // Recharts animates in; give the final frame a moment to settle.
    await new Promise((resolve) => setTimeout(resolve, 600));

    const variant = theme === 'padiushdark' ? 'dark' : 'light';
    const file = path.join(OUT, `${name}-${variant}.webp`);
    const options = { path: file, type: 'webp', quality: 90 };

    if (chartIndex === undefined) {
        await page.screenshot(options);

        return file;
    }

    const selector = await tagChartCard(page, chartIndex);
    const card = await page.$(selector);
    await card.screenshot(options);

    return file;
}

/**
 * The link-preview card.
 *
 * Built inside a loaded page rather than from a standalone file so it inherits
 * the real stylesheet and typeface, and so the wordmark can be cloned from the
 * footer instead of duplicating several hundred lines of path data.
 *
 * Layout uses inline styles because Tailwind compiles from source: classes
 * invented at runtime would not exist in the built CSS.
 *
 * PNG, not WebP — WhatsApp and some LinkedIn paths refuse to render a WebP
 * og:image, and the preview silently falls back to no image at all.
 */
async function captureOgCard(browser, { url, tagline, site }) {
    const page = await browser.newPage();

    // 1200x630 is the size every platform crops toward. Kept at 1x on
    // purpose: at 2x the file passed 480 KB, and WhatsApp quietly drops
    // previews well before that.
    await page.setViewport({ width: 1200, height: 630, deviceScaleFactor: 1 });
    await page.goto(url, { waitUntil: 'networkidle0' });

    await page.evaluate(
        ({ line, host }) => {
            const mark = document.querySelector('footer svg');
            const font = getComputedStyle(document.body).fontFamily;

            const card = document.createElement('div');
            card.id = 'og-card';
            Object.assign(card.style, {
                position: 'fixed',
                inset: '0',
                width: '1200px',
                height: '630px',
                display: 'flex',
                flexDirection: 'column',
                justifyContent: 'center',
                alignItems: 'flex-start',
                gap: '34px',
                padding: '0 96px',
                background:
                    'linear-gradient(135deg, #3c6200 0%, #4c7a08 55%, #5c8f12 100%)',
                fontFamily: font,
                color: '#f4f7ec',
                zIndex: '2147483647',
            });

            if (mark) {
                const logo = mark.cloneNode(true);
                logo.removeAttribute('class');
                logo.setAttribute('height', '96');
                logo.style.height = '96px';
                logo.style.width = 'auto';
                logo.style.fill = '#f4f7ec';
                card.append(logo);
            }

            const text = document.createElement('p');
            text.textContent = line;
            Object.assign(text.style, {
                margin: '0',
                maxWidth: '900px',
                fontSize: '46px',
                lineHeight: '1.25',
                fontWeight: '600',
            });
            card.append(text);

            const domain = document.createElement('p');
            domain.textContent = host;
            Object.assign(domain.style, {
                margin: '0',
                fontSize: '26px',
                opacity: '0.75',
            });
            card.append(domain);

            document.body.append(card);
        },
        { line: tagline, host: site },
    );

    const file = path.join(OUT, 'og-card.png');
    const card = await page.$('#og-card');
    await card.screenshot({ path: file, type: 'png' });
    await page.close();

    return file;
}

const browser = await puppeteer.launch({
    headless: true,
    defaultViewport: VIEWPORT,
});

try {
    await mkdir(OUT, { recursive: true });

    const page = await browser.newPage();
    await signIn(page);

    const project = await findDemoProject(page);

    const shots = [
        {
            name: 'reports',
            url: `${BASE}/data/${project}/reports`,
            waitFor: 'table',
        },
        {
            name: 'sankey',
            url: `${BASE}/data/${project}/reports`,
            waitFor: 'table',
            chartIndex: 2,
        },
        {
            // Nothing on the list animates in, so settling on network idle is
            // enough; the reports page needs its charts to finish drawing.
            name: 'catalog',
            url: `${BASE}/catalogs/${project}`,
        },
    ];

    for (const theme of THEMES) {
        for (const shot of shots) {
            const file = await capture(page, { ...shot, theme });
            console.log(`wrote ${path.relative(process.cwd(), file)}`);
        }
    }

    const card = await captureOgCard(browser, {
        url: BASE,
        tagline: 'Plataforma de investigación etnobotánica',
        site: 'padiushbio.com',
    });
    console.log(`wrote ${path.relative(process.cwd(), card)}`);
} finally {
    await browser.close();
}
