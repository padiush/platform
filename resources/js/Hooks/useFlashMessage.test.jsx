import { act, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

let mockFlash = {};
vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: { flash: mockFlash } }),
}));

import { useFlashMessage } from './useFlashMessage';

function Page() {
    const { FlashAlert, flashShown } = useFlashMessage();
    return flashShown ? <FlashAlert /> : null;
}

beforeEach(() => vi.useFakeTimers());
afterEach(() => vi.useRealTimers());

describe('useFlashMessage', () => {
    it('shows a message for five seconds', () => {
        mockFlash = {
            message: 'project.create_success',
            message_type: 'success',
        };
        render(<Page />);
        expect(screen.getByText('project.create_success')).toBeInTheDocument();

        act(() => vi.advanceTimersByTime(4999));
        expect(screen.getByText('project.create_success')).toBeInTheDocument();

        act(() => vi.advanceTimersByTime(1));
        expect(screen.queryByText('project.create_success')).toBeNull();
    });

    it('shows the next visit’s message afresh, even when it reads the same', () => {
        mockFlash = {
            message: 'project.create_success',
            message_type: 'success',
        };
        const { rerender } = render(<Page />);
        act(() => vi.advanceTimersByTime(5000));

        mockFlash = {
            message: 'project.create_success',
            message_type: 'success',
        };
        rerender(<Page />);

        expect(screen.getByText('project.create_success')).toBeInTheDocument();
    });

    it('shows nothing for a visit without a message', () => {
        mockFlash = {
            message: 'project.create_success',
            message_type: 'success',
        };
        const { rerender } = render(<Page />);

        mockFlash = {};
        rerender(<Page />);

        expect(screen.queryByText('project.create_success')).toBeNull();
    });
});
