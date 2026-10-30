/** The interface languages, in the order the public site lists them. */
export const LANGUAGES = ['es', 'en', 'pt'];

/**
 * The language the interface is in, as one of LANGUAGES. i18next reports what
 * it detected, which can carry a region ("en-US", from the browser); anything
 * it cannot match falls back to Spanish, as the translations do.
 *
 * @param {{resolvedLanguage?: string, language?: string}} i18n
 * @returns {'es' | 'en' | 'pt'}
 */
export function currentLanguage(i18n) {
    const language = (i18n.resolvedLanguage ?? i18n.language ?? '')
        .split('-')[0]
        .toLowerCase();

    return LANGUAGES.includes(language) ? language : 'es';
}
