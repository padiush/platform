/**
 * Render Play Store feature graphics at exactly 1024x500.
 *
 * Play crops this asset and may overlay the app icon and title on it, so every
 * variant keeps its content inside a central safe area rather than filling the
 * canvas. Colours come from the companion's design tokens (src/theme.ts) so the
 * banner and the screenshots read as one product.
 *
 *   node scripts/feature-graphic.mjs <out-dir>
 */
import { readFileSync, mkdirSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import puppeteer from 'puppeteer';

const here = dirname(fileURLToPath(import.meta.url));
const outDir = process.argv[2] ?? join(here, '..', 'feature-graphic');
mkdirSync(outDir, { recursive: true });

// The mark, stripped of its hard-coded fill so each variant can colour it.
const logo = readFileSync(join(here, '..', 'public', 'images', 'padiush-logo.svg'), 'utf8')
  .replace(/fill="#[0-9a-fA-F]{3,8}"/, '')
  .replace('<svg', '<svg preserveAspectRatio="xMidYMid meet"');

/** Companion design tokens — keep in step with src/theme.ts. */
const token = {
  primary: '#3c6200',
  onPrimary: '#f5fce5',
  darkBg: '#131712',
  lightBg: '#e4e4e4',
  darkText: '#dbe5d8',
  muted: '#64675e',
};

const page = (variant) => `
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  html, body { width: 1024px; height: 500px; overflow: hidden; }
  body {
    display: flex; align-items: center; justify-content: center;
    background: ${variant.bg};
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  }
  /* Safe area: Play can crop the edges and overlay the title, so nothing
     meaningful goes outside this box. */
  .safe {
    width: 830px; height: 380px;
    display: flex; flex-direction: column;
    align-items: ${variant.align}; justify-content: center; gap: 26px;
    text-align: ${variant.align === 'flex-start' ? 'left' : 'center'};
  }
  .logo { width: ${variant.logoWidth}px; }
  .logo svg { width: 100%; height: auto; display: block; fill: ${variant.ink}; }
  .tagline {
    font-size: 30px; font-weight: 500; letter-spacing: 0.2px;
    color: ${variant.taglineColor}; opacity: .92;
  }
</style>
<div class="safe">
  <div class="logo">${logo}</div>
  ${variant.tagline ? `<div class="tagline">${variant.tagline}</div>` : ''}
</div>`;

const variants = [
  {
    name: 'a-dark',
    bg: token.darkBg,
    ink: token.darkText,
    logoWidth: 520,
    tagline: 'Registro de campo, sin conexión',
    taglineColor: token.darkText,
    align: 'center',
  },
  {
    name: 'b-green',
    bg: token.primary,
    ink: token.onPrimary,
    logoWidth: 560,
    tagline: 'Registro de campo, sin conexión',
    taglineColor: token.onPrimary,
    align: 'center',
  },
  {
    name: 'c-light',
    bg: token.lightBg,
    ink: token.primary,
    logoWidth: 520,
    tagline: 'Registro de campo, sin conexión',
    taglineColor: token.muted,
    align: 'center',
  },
  {
    name: 'd-dark-markonly',
    bg: token.darkBg,
    ink: token.darkText,
    logoWidth: 620,
    tagline: '',
    taglineColor: token.darkText,
    align: 'center',
  },
];

const browser = await puppeteer.launch();
try {
  for (const variant of variants) {
    const tab = await browser.newPage();
    await tab.setViewport({ width: 1024, height: 500, deviceScaleFactor: 1 });
    await tab.setContent(page(variant), { waitUntil: 'load' });
    const file = join(outDir, `feature-${variant.name}.png`);
    await tab.screenshot({ path: file, type: 'png' });
    await tab.close();
    console.log(`  -> ${file}`);
  }
} finally {
  await browser.close();
}
