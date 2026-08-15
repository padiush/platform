/**
 * Collects the licence of every package that reaches the browser, so the
 * attribution page is derived from the dependency tree rather than maintained
 * by hand — a hand-written list is wrong the first time a dependency changes.
 *
 * Only production dependencies are walked. Build tooling never leaves this
 * machine and carries no distribution obligation; the bundle does, and MIT,
 * ISC and BSD all require their notice to travel with the copies they are in.
 * FontAwesome's free icons are CC BY 4.0, which requires attribution outright.
 *
 * Run by `npm run build`, after Vite, because Vite empties the output directory.
 */
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { existsSync, readFileSync, readdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const OUT = 'public/build/licenses.json';

const LICENSE_FILE = /^(licen[cs]e|copying|notice)(\.(md|txt|markdown))?$/i;

/** The paths npm considers production dependencies, itself excluded. */
function productionPackagePaths() {
    const raw = execFileSync(
        'npm',
        ['ls', '--omit=dev', '--all', '--parseable'],
        { encoding: 'utf8', maxBuffer: 64 * 1024 * 1024 },
    );

    return raw.split('\n').filter((p) => p.includes('node_modules'));
}

/** The licence text a package ships, if it ships one. */
function licenceText(dir) {
    let entries;
    try {
        entries = readdirSync(dir);
    } catch {
        return null;
    }

    const files = entries.filter((e) => LICENSE_FILE.test(e)).sort();

    const texts = files
        .map((f) => {
            try {
                return readFileSync(join(dir, f), 'utf8').trim();
            } catch {
                return '';
            }
        })
        .filter(Boolean);

    return texts.length ? texts.join('\n\n') : null;
}

function spdx(manifest) {
    const { license, licenses } = manifest;
    if (typeof license === 'string') return license;
    if (license?.type) return license.type;
    if (Array.isArray(licenses)) {
        return licenses.map((l) => l.type ?? l).join(' OR ');
    }
    return 'UNKNOWN';
}

const packages = [];

for (const dir of productionPackagePaths()) {
    const manifestPath = join(dir, 'package.json');
    if (!existsSync(manifestPath)) continue;

    let manifest;
    try {
        manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
    } catch {
        continue;
    }
    if (!manifest.name) continue;

    packages.push({
        name: manifest.name,
        version: manifest.version ?? '',
        license: spdx(manifest),
        text: licenceText(dir),
    });
}

packages.sort((a, b) => a.name.localeCompare(b.name));

// Most of these are the same MIT text differing only in a copyright line, so
// grouping identical texts turns a hundred entries into a readable handful.
const groups = new Map();

for (const pkg of packages) {
    const key = pkg.text
        ? createHash('sha256').update(pkg.text).digest('hex')
        : `no-text:${pkg.license}`;

    if (!groups.has(key)) {
        groups.set(key, { licenses: new Set(), text: pkg.text, packages: [] });
    }
    const group = groups.get(key);
    // Packages that ship an identical notice can still declare different
    // terms — FontAwesome's icon packages are (CC-BY-4.0 AND MIT) alongside
    // MIT-only siblings under the same LICENSE.txt. Labelling the group by
    // whichever package sorted first would hide the CC BY obligation.
    group.licenses.add(pkg.license);
    group.packages.push({ name: pkg.name, version: pkg.version });
}

const output = {
    // Stamped by the caller, not here: this file is committed to no repository
    // and regenerated on every build, so a timestamp would only add churn.
    packageCount: packages.length,
    missingText: packages.filter((p) => !p.text).map((p) => p.name),
    groups: [...groups.values()]
        .map((g) => ({ ...g, licenses: [...g.licenses].sort() }))
        .sort(
            (a, b) =>
                b.packages.length - a.packages.length ||
                a.licenses[0].localeCompare(b.licenses[0]),
        ),
};

writeFileSync(OUT, JSON.stringify(output));

const withText = packages.length - output.missingText.length;
console.log(
    `licences: ${packages.length} packages, ${output.groups.length} distinct texts, ` +
        `${withText} carrying their own notice -> ${OUT}`,
);
