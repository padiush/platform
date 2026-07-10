const DIVISIONS = [
    { amount: 60, unit: 'seconds' },
    { amount: 60, unit: 'minutes' },
    { amount: 24, unit: 'hours' },
    { amount: 7, unit: 'days' },
    { amount: 4.34524, unit: 'weeks' },
    { amount: 12, unit: 'months' },
    { amount: Number.POSITIVE_INFINITY, unit: 'years' },
];

/**
 * Format an ISO timestamp as a localized relative time (e.g. "in 6 days",
 * "dentro de 6 días", "em 6 dias") in the given locale. Returns '' for a
 * missing or unparseable value.
 */
export function formatRelativeTime(iso, locale) {
    if (!iso) return '';

    const target = new Date(iso);
    if (Number.isNaN(target.getTime())) return '';

    const rtf = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });

    let duration = (target.getTime() - Date.now()) / 1000;
    for (const division of DIVISIONS) {
        if (Math.abs(duration) < division.amount) {
            return rtf.format(Math.round(duration), division.unit);
        }
        duration /= division.amount;
    }

    return '';
}

/**
 * Format an ISO timestamp as a localized long date (e.g. "July 10, 2026",
 * "10 de julio de 2026") in the given locale. Returns '' for a missing or
 * unparseable value.
 */
export function formatLongDate(iso, locale) {
    if (!iso) return '';

    const target = new Date(iso);
    if (Number.isNaN(target.getTime())) return '';

    return new Intl.DateTimeFormat(locale, { dateStyle: 'long' }).format(
        target,
    );
}
