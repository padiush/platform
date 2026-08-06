/**
 * Puppeteer backs the screenshot capture script only, and that script runs on
 * a developer machine rather than in CI or the container: the Node image is
 * Alpine, where the bundled Chromium (glibc) cannot run at all.
 *
 * So the browser is never downloaded as an install side effect. Fetch it once,
 * explicitly:
 *
 *   npx puppeteer browsers install chrome
 */
module.exports = {
    skipDownload: true,
};
