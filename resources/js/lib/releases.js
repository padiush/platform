/**
 * Release notes arrive as a list of `{ version, date, items }`, newest first,
 * from the `whatsnew` translation namespace (docs/releasing.md). These pick out
 * the ones a page should show.
 */

/**
 * Compare two `major.minor.patch` versions numerically, so 1.10.0 sorts after
 * 1.9.0. Missing parts count as zero.
 *
 * @returns {number} negative, zero or positive, like a sort comparator
 */
export function compareVersions(a, b) {
    const left = String(a ?? '').split('.');
    const right = String(b ?? '').split('.');

    for (let i = 0; i < Math.max(left.length, right.length); i++) {
        const difference =
            (parseInt(left[i], 10) || 0) - (parseInt(right[i], 10) || 0);
        if (difference !== 0) {
            return difference;
        }
    }

    return 0;
}

/**
 * The releases up to the one running, newest first. Notes drafted for a
 * release that has not shipped yet stay hidden until it does.
 */
export function releasedUpTo(releases, current) {
    return (Array.isArray(releases) ? releases : [])
        .filter((release) => compareVersions(release.version, current) <= 0)
        .sort((a, b) => compareVersions(b.version, a.version));
}

/** The releases after `since` and up to `current`: what a user has not seen. */
export function releasesSince(releases, since, current) {
    return releasedUpTo(releases, current).filter(
        (release) => compareVersions(release.version, since) > 0,
    );
}
