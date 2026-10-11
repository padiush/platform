import axios from 'axios';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { chooseLanguage, syncAccountLanguage } from './accountLanguage';

vi.mock('axios', () => ({ default: { put: vi.fn() } }));

const i18n = { language: 'es', changeLanguage: vi.fn() };

beforeEach(() => {
    i18n.language = 'es';
    i18n.changeLanguage.mockReset();
    i18n.changeLanguage.mockImplementation((language) => {
        i18n.language = language;
    });
    axios.put.mockReset();
    window.route = vi.fn((name) => `/${name}`);
});

describe('chooseLanguage', () => {
    it("switches a guest's interface without saving anything", async () => {
        await chooseLanguage(i18n, 'en', false);

        expect(i18n.changeLanguage).toHaveBeenCalledWith('en');
        expect(axios.put).not.toHaveBeenCalled();
    });

    it("saves a signed-in user's choice on the account", async () => {
        axios.put.mockResolvedValue({});

        await chooseLanguage(i18n, 'pt', true);

        expect(i18n.changeLanguage).toHaveBeenCalledWith('pt');
        expect(axios.put).toHaveBeenCalledWith('/account.language', {
            locale: 'pt',
        });
    });

    it('keeps the interface working when the save fails', async () => {
        axios.put.mockRejectedValue(new Error('offline'));

        await expect(chooseLanguage(i18n, 'en', true)).resolves.toBeUndefined();
        expect(i18n.language).toBe('en');
    });
});

describe('syncAccountLanguage', () => {
    it("follows the signed-in account's preference", () => {
        syncAccountLanguage(i18n, { auth: { user: { locale: 'pt' } } });

        expect(i18n.changeLanguage).toHaveBeenCalledWith('pt');
    });

    it('leaves a guest with the language they are reading', () => {
        syncAccountLanguage(i18n, { auth: { user: null } });
        syncAccountLanguage(i18n, {});

        expect(i18n.changeLanguage).not.toHaveBeenCalled();
    });

    it('does nothing when the interface already matches', () => {
        syncAccountLanguage(i18n, { auth: { user: { locale: 'es' } } });

        expect(i18n.changeLanguage).not.toHaveBeenCalled();
    });

    it('does not undo a choice the server has not acknowledged yet', async () => {
        let acknowledge;
        axios.put.mockReturnValue(
            new Promise((resolve) => {
                acknowledge = resolve;
            }),
        );

        const saving = chooseLanguage(i18n, 'en', true);
        // A page visit still carrying the old preference arrives meanwhile.
        syncAccountLanguage(i18n, { auth: { user: { locale: 'es' } } });

        expect(i18n.language).toBe('en');

        acknowledge({});
        await saving;
    });
});
