import axios from 'axios';

/**
 * The interface language, kept in step with the account's preference.
 *
 * A signed-in user's language lives on the account (users.locale), so it is
 * the same in every browser and on every device; the server writes the pages'
 * strings and the user's emails in it. The client still translates the
 * interface itself through i18next, so it has to follow the account.
 */

/** A choice not yet acknowledged by the server, so stale page props cannot undo it. */
let pending = null;

/**
 * Switches the interface to `language` and, for a signed-in user, stores it
 * as the account's preference. The request runs in the background, like the
 * tours, so choosing never interrupts what the user was doing.
 *
 * @param {import('i18next').i18n} i18n
 * @param {string} language
 * @param {boolean} signedIn
 * @returns {Promise<void>}
 */
export function chooseLanguage(i18n, language, signedIn) {
    i18n.changeLanguage(language);

    if (!signedIn) {
        return Promise.resolve();
    }

    pending = language;

    return axios
        .put(route('account.language'), { locale: language })
        .catch(() => {
            // The interface has already switched; the account keeps its old
            // preference and the next page restores it, which is the honest
            // outcome of a save that did not happen.
        })
        .finally(() => {
            if (pending === language) {
                pending = null;
            }
        });
}

/**
 * Brings the interface to the signed-in account's language, on the first
 * page and after every visit (signing in lands on a page whose props carry
 * it). Guests keep whatever i18next detected or they chose.
 *
 * @param {import('i18next').i18n} i18n
 * @param {object} props The page props.
 */
export function syncAccountLanguage(i18n, props) {
    const preferred = props?.auth?.user?.locale;

    if (!preferred || pending || i18n.language === preferred) {
        return;
    }

    i18n.changeLanguage(preferred);
}
