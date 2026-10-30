import { describe, expect, it } from 'vitest';
import { LANGUAGES, currentLanguage } from './interfaceLanguage';

describe('currentLanguage', () => {
    it('lists the interface languages in the order the public site does', () => {
        expect(LANGUAGES).toEqual(['es', 'en', 'pt']);
    });

    it('drops the region a browser reports with its language', () => {
        expect(currentLanguage({ language: 'en-US' })).toBe('en');
        expect(currentLanguage({ language: 'pt-BR' })).toBe('pt');
        expect(currentLanguage({ language: 'ES-sv' })).toBe('es');
    });

    it('prefers the language i18next resolved to', () => {
        expect(
            currentLanguage({ resolvedLanguage: 'pt', language: 'pt-PT' }),
        ).toBe('pt');
    });

    it('falls back to Spanish for any other language', () => {
        expect(currentLanguage({ language: 'de-DE' })).toBe('es');
        expect(currentLanguage({})).toBe('es');
    });
});
