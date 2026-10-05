import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

// vi.mock is hoisted above ordinary consts, so the spies have to be too.
const { destroy, post } = vi.hoisted(() => ({
    destroy: vi.fn(),
    post: vi.fn(),
}));

vi.mock('@inertiajs/react', () => ({
    router: { delete: destroy },
    useForm: () => ({
        setData: vi.fn(),
        post,
        reset: vi.fn(),
        processing: false,
        errors: {},
    }),
}));

globalThis.route = (name) => `/${name}`;

import RecordMedia from './RecordMedia';

const project = { id: 1 };

const photo = {
    id: 7,
    kind: 'photo',
    content_type: 'image/jpeg',
    url: '/media/7',
};
const audio = {
    id: 8,
    kind: 'audio',
    content_type: 'audio/mpeg',
    url: '/media/8',
};

describe('RecordMedia', () => {
    it('tells an observation that the photograph is the record', () => {
        render(
            <RecordMedia
                project={project}
                fieldRecord={{ id: 1, was_collected: false, media: [] }}
            />,
        );

        // Nothing was pressed, so there is nothing else to go back to.
        expect(
            screen.getByText('catalogs.fieldRecords.media.none_observation'),
        ).toBeInTheDocument();
    });

    it('says something milder when material was collected', () => {
        render(
            <RecordMedia
                project={project}
                fieldRecord={{ id: 1, was_collected: true, media: [] }}
            />,
        );

        expect(
            screen.getByText('catalogs.fieldRecords.media.none'),
        ).toBeInTheDocument();
    });

    it('renders a photograph as an image and audio as a player', () => {
        const { container } = render(
            <RecordMedia
                project={project}
                fieldRecord={{
                    id: 1,
                    was_collected: false,
                    media: [photo, audio],
                }}
            />,
        );

        expect(container.querySelector('img')).toHaveAttribute(
            'src',
            '/media/7',
        );
        expect(container.querySelector('audio')).toHaveAttribute(
            'src',
            '/media/8',
        );
    });

    it('offers no upload or removal without the capability', () => {
        render(
            <RecordMedia
                project={project}
                fieldRecord={{ id: 1, was_collected: false, media: [photo] }}
                canEdit={false}
            />,
        );

        expect(
            screen.queryByText('catalogs.fieldRecords.media.add'),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByText('catalogs.fieldRecords.media.remove'),
        ).not.toBeInTheDocument();
    });

    it('asks before destroying the only evidence a record has', () => {
        render(
            <RecordMedia
                project={project}
                fieldRecord={{ id: 1, was_collected: false, media: [photo] }}
                canEdit
            />,
        );

        fireEvent.click(screen.getByText('catalogs.fieldRecords.media.remove'));

        // The bytes go with the row, and nothing was collected — so it warns
        // rather than deleting on the click.
        expect(destroy).not.toHaveBeenCalled();
        expect(
            screen.getByText(
                'catalogs.fieldRecords.media.confirm_delete_only_evidence',
            ),
        ).toBeInTheDocument();
    });

    it('warns more plainly where material was collected', () => {
        render(
            <RecordMedia
                project={project}
                fieldRecord={{ id: 1, was_collected: true, media: [photo] }}
                canEdit
            />,
        );

        fireEvent.click(screen.getByText('catalogs.fieldRecords.media.remove'));

        expect(
            screen.getByText('catalogs.fieldRecords.media.confirm_delete'),
        ).toBeInTheDocument();
    });
});
