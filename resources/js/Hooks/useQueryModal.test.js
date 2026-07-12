import { act, renderHook } from '@testing-library/react';
import { beforeEach, describe, expect, it } from 'vitest';
import useQueryModal from './useQueryModal';

describe('useQueryModal', () => {
    beforeEach(() => window.history.replaceState({}, '', '/'));

    it('seeds its value from the URL param', () => {
        window.history.replaceState({}, '', '/?edit=7');

        const { result } = renderHook(() => useQueryModal('edit'));

        expect(result.current[0]).toBe('7');
    });

    it('sets and clears the param in the URL', () => {
        const { result } = renderHook(() => useQueryModal('create'));

        act(() => result.current[1]('1'));
        expect(new URLSearchParams(window.location.search).get('create')).toBe(
            '1',
        );
        expect(result.current[0]).toBe('1');

        act(() => result.current[1](null));
        expect(
            new URLSearchParams(window.location.search).get('create'),
        ).toBeNull();
        expect(result.current[0]).toBeNull();
    });
});
