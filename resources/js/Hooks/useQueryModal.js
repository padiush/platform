import { useState } from 'react';

/**
 * Mirrors a modal's open state to a URL query param, so opening a form modal
 * is deep-linkable and refresh-stable, and closing strips the param. Uses
 * history.replaceState (client-only, preserves Inertia's history state, no
 * server round-trip). Seeded from the current URL on mount.
 *
 * Returns [value, setValue] where value is the param's string value (or null).
 */
export default function useQueryModal(key) {
    const read = () =>
        typeof window === 'undefined'
            ? null
            : new URLSearchParams(window.location.search).get(key);

    const [value, setStateValue] = useState(read);

    const setValue = (next) => {
        const url = new URL(window.location.href);

        if (next == null || next === false) {
            url.searchParams.delete(key);
        } else {
            url.searchParams.set(key, String(next));
        }

        window.history.replaceState(window.history.state, '', url);
        setStateValue(next == null || next === false ? null : String(next));
    };

    return [value, setValue];
}
