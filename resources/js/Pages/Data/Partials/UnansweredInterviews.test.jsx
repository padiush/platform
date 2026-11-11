import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import UnansweredInterviews from './UnansweredInterviews';

const interview = {
    id: 'abc',
    recorded_at: '2026-10-05T16:47:00Z',
    recorder: 'Ana',
    media_count: 2,
};

describe('UnansweredInterviews', () => {
    it('shows nothing when every interview has answers', () => {
        const { container } = render(
            <UnansweredInterviews interviews={[]} onOpen={vi.fn()} />,
        );

        expect(container).toBeEmptyDOMElement();
    });

    it('opens the files of the interview chosen', () => {
        const onOpen = vi.fn();
        render(
            <UnansweredInterviews interviews={[interview]} onOpen={onOpen} />,
        );

        expect(
            screen.getByText('data.media.unanswered_title'),
        ).toBeInTheDocument();
        expect(screen.getByText(/Ana/)).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: /\(2\)/ }));

        expect(onOpen).toHaveBeenCalledWith('abc');
    });
});
