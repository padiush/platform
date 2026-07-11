import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { formatDateTime, formatLongDate, formatRelativeTime } from './datetime';

describe('formatRelativeTime', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-07-10T12:00:00Z'));
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('returns an empty string for missing or invalid input', () => {
        expect(formatRelativeTime(null, 'en')).toBe('');
        expect(formatRelativeTime('', 'en')).toBe('');
        expect(formatRelativeTime('not-a-date', 'en')).toBe('');
    });

    it('formats future dates in the given locale', () => {
        expect(formatRelativeTime('2026-07-16T12:00:00Z', 'en')).toBe(
            'in 6 days',
        );
        expect(formatRelativeTime('2026-07-16T12:00:00Z', 'es')).toBe(
            'dentro de 6 días',
        );
    });

    it('formats past dates', () => {
        expect(formatRelativeTime('2026-07-10T11:30:00Z', 'en')).toBe(
            '30 minutes ago',
        );
    });

    it('rolls over to larger units', () => {
        expect(formatRelativeTime('2028-07-10T12:00:00Z', 'en')).toBe(
            'in 2 years',
        );
    });
});

describe('formatLongDate', () => {
    it('returns an empty string for missing or invalid input', () => {
        expect(formatLongDate(undefined, 'en')).toBe('');
        expect(formatLongDate('garbage', 'en')).toBe('');
    });

    it('formats a date per locale', () => {
        expect(formatLongDate('2026-07-10T12:00:00Z', 'en')).toBe(
            'July 10, 2026',
        );
        expect(formatLongDate('2026-07-10T12:00:00Z', 'es')).toBe(
            '10 de julio de 2026',
        );
    });
});

describe('formatDateTime', () => {
    it('returns an empty string for missing or invalid input', () => {
        expect(formatDateTime(null, 'en')).toBe('');
        expect(formatDateTime('garbage', 'en')).toBe('');
    });

    it('includes both date and time', () => {
        const result = formatDateTime('2026-07-10T12:00:00Z', 'en');
        expect(result).toContain('2026');
        expect(result).toMatch(/\d{1,2}:\d{2}/);
    });
});
