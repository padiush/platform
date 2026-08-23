import { render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('axios', () => ({ default: { get: vi.fn() } }));

globalThis.route = (name) => `/${name}`;

import axios from 'axios';
import InterviewMedia from './InterviewMedia';

const project = { id: 1 };

function resolve(media) {
    axios.get.mockResolvedValue({ data: { media } });
}

describe('InterviewMedia', () => {
    beforeEach(() => {
        axios.get.mockReset();
    });

    it('says so when the interview carries nothing', async () => {
        resolve([]);
        render(<InterviewMedia project={project} instanceId="abc" />);

        expect(await screen.findByText('data.media.none')).toBeInTheDocument();
    });

    it('shows a photograph and an audio player', async () => {
        resolve([
            { id: 1, kind: 'photo', url: '/m/1' },
            { id: 2, kind: 'audio', url: '/m/2' },
        ]);
        const { container } = render(
            <InterviewMedia project={project} instanceId="abc" />,
        );

        await waitFor(() =>
            expect(container.querySelector('img')).toHaveAttribute(
                'src',
                '/m/1',
            ),
        );
        expect(container.querySelector('audio')).toHaveAttribute('src', '/m/2');
    });

    it('shows a transcript when there is one', async () => {
        resolve([
            {
                id: 2,
                kind: 'audio',
                url: '/m/2',
                transcription_text: 'lo llamamos cortez blanco',
            },
        ]);
        render(<InterviewMedia project={project} instanceId="abc" />);

        expect(
            await screen.findByText('lo llamamos cortez blanco'),
        ).toBeInTheDocument();
    });

    it('shows no transcript box when there is no transcript', async () => {
        resolve([{ id: 2, kind: 'audio', url: '/m/2' }]);
        render(<InterviewMedia project={project} instanceId="abc" />);

        // It stays null until a transcriber is provisioned, and an empty box
        // would read as "nothing was said".
        await waitFor(() => expect(axios.get).toHaveBeenCalled());
        expect(
            screen.queryByText('data.media.transcript'),
        ).not.toBeInTheDocument();
    });

    it('reports a failure instead of an empty interview', async () => {
        axios.get.mockRejectedValue(new Error('nope'));
        render(<InterviewMedia project={project} instanceId="abc" />);

        expect(
            await screen.findByText('data.media.failed'),
        ).toBeInTheDocument();
    });
});
