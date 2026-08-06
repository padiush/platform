import { describe, expect, it } from 'vitest';
import { primaryCtaTarget } from './publicCta';

describe('primaryCtaTarget', () => {
    it('sends a signed-in user to the dashboard', () => {
        expect(
            primaryCtaTarget({ signedIn: true, registrationEnabled: true }),
        ).toBe('dashboard');

        expect(
            primaryCtaTarget({ signedIn: true, registrationEnabled: false }),
        ).toBe('dashboard');
    });

    it('offers registration when it is open', () => {
        expect(
            primaryCtaTarget({ signedIn: false, registrationEnabled: true }),
        ).toBe('register');
    });

    it('falls back to the contact form when registration is closed', () => {
        // Registration routes redirect to login while invitation-only, so
        // pointing at them would be a dead end.
        expect(
            primaryCtaTarget({ signedIn: false, registrationEnabled: false }),
        ).toBe('contact');
    });
});
