/**
 * Which destination the public pages' primary call to action should point at.
 *
 * Registration is invitation-only for most of the beta, and the registration
 * routes redirect straight back to login when it is closed — so offering
 * "create an account" then would send people into a dead end. In that case the
 * contact form is the real way in.
 *
 * @param {{signedIn: boolean, registrationEnabled: boolean}} state
 * @returns {'dashboard' | 'register' | 'contact'}
 */
export function primaryCtaTarget({ signedIn, registrationEnabled }) {
    if (signedIn) {
        return 'dashboard';
    }

    return registrationEnabled ? 'register' : 'contact';
}
