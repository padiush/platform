import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
}));

vi.mock('@/Layouts/AuthenticatedLayout', () => ({
    default: ({ children }) => <div>{children}</div>,
}));

vi.mock('@/Pages/Data/Partials/InterviewMedia', () => ({
    default: ({ instanceId, kind }) => (
        <div>
            {kind} of {instanceId}
        </div>
    ),
}));

vi.mock('./Partials/SectionRender', () => ({
    default: () => <div>a section</div>,
}));

globalThis.route = (name) => `/${name}`;

import Instance from './Instance';

const props = {
    project: { id: 3 },
    form: { id: 8, name: 'Usos', sections: [{ id: 1 }] },
    instance: { id: 'abc', created_at: '2026-10-05T16:47:00Z' },
    answers: [],
};

describe('Interviews/Instance', () => {
    it('places audio above the form and photos below it, as the app does', () => {
        render(<Instance {...props} audioCount={2} photoCount={1} />);

        const audio = screen.getByText('audio of abc');
        const form = screen.getByText('a section');
        const photos = screen.getByText('photo of abc');

        expect(
            audio.compareDocumentPosition(form) &
                Node.DOCUMENT_POSITION_FOLLOWING,
        ).toBeTruthy();
        expect(
            form.compareDocumentPosition(photos) &
                Node.DOCUMENT_POSITION_FOLLOWING,
        ).toBeTruthy();
    });

    it('adds nothing to an interview without files', () => {
        render(<Instance {...props} />);

        expect(
            screen.queryByText('data.media.audio_title'),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByText('data.media.photos_title'),
        ).not.toBeInTheDocument();
        expect(screen.getByText('a section')).toBeInTheDocument();
    });
});
