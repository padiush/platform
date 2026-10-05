import { describe, expect, it } from 'vitest';
import { formatBytes } from './bytes';

describe('formatBytes', () => {
    it('counts bytes as they are', () => {
        expect(formatBytes(0, 'en')).toBe('0 B');
        expect(formatBytes(512, 'en')).toBe('512 B');
    });

    it('steps up in binary multiples, with one decimal while small', () => {
        expect(formatBytes(1536, 'en')).toBe('1.5 KB');
        expect(formatBytes(9.8 * 1024 ** 3, 'en')).toBe('9.8 GB');
        expect(formatBytes(150 * 1024 ** 2, 'en')).toBe('150 MB');
    });

    it('writes the number the reader’s way', () => {
        expect(formatBytes(9.8 * 1024 ** 3, 'es')).toBe('9,8 GB');
        expect(formatBytes(1536, 'pt')).toBe('1,5 KB');
    });

    it('treats nothing as nothing', () => {
        expect(formatBytes(null, 'en')).toBe('0 B');
        expect(formatBytes(-5, 'en')).toBe('0 B');
    });
});
