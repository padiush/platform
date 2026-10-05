const UNITS = ['B', 'KB', 'MB', 'GB', 'TB'];

/**
 * Format a byte count for people ("9,8 GB", "512 KB") in the given locale,
 * in binary multiples. One decimal below 100 of a unit, none above.
 */
export function formatBytes(bytes, locale) {
    let value = Math.max(0, Number(bytes) || 0);
    let unit = 0;

    while (value >= 1024 && unit < UNITS.length - 1) {
        value /= 1024;
        unit++;
    }

    const digits = unit === 0 || value >= 100 ? 0 : 1;
    const number = new Intl.NumberFormat(locale, {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
    }).format(value);

    return `${number} ${UNITS[unit]}`;
}
