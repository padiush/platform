import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import WhatsNewDialog from './WhatsNewDialog';

global.route = (name) => `/${name}`;

const mockPost = vi.fn();
let mockProps = {};
let mockReleases = [];

vi.mock('@inertiajs/react', () => ({
    Link: ({ href, children, onClick, ...rest }) => (
        <a href={href} onClick={onClick} {...rest}>
            {children}
        </a>
    ),
    router: { post: (...args) => mockPost(...args) },
    usePage: () => ({ props: mockProps }),
}));

// The release notes come from their own namespace; everything else is keys.
vi.mock('react-i18next', () => ({
    useTranslation: (namespace) => ({
        t: (key, options) =>
            namespace === 'whatsnew' && key === 'releases'
                ? mockReleases
                : options
                  ? `${key} ${JSON.stringify(options)}`
                  : key,
        ready: true,
        i18n: { language: 'en' },
    }),
}));

const RELEASES = [
    { version: '1.2.0', date: null, items: ['Not shipped yet'] },
    {
        version: '1.1.0',
        date: '2026-10-10',
        items: ['A sidebar', 'An overview'],
    },
    { version: '1.0.0', date: '2026-08-15', items: ['First release'] },
];

beforeEach(() => {
    mockPost.mockClear();
    mockReleases = RELEASES;
    mockProps = { release: { version: '1.1.0', unseenSince: '1.0.0' } };
});

describe('WhatsNewDialog', () => {
    it('shows what shipped since the last release the user saw', () => {
        render(<WhatsNewDialog />);

        expect(screen.getByRole('dialog', { hidden: true })).toHaveAttribute(
            'open',
        );
        expect(screen.getByText('A sidebar')).toBeInTheDocument();
        expect(screen.getByText('An overview')).toBeInTheDocument();
        // Seen already, and not shipped yet.
        expect(screen.queryByText('First release')).not.toBeInTheDocument();
        expect(screen.queryByText('Not shipped yet')).not.toBeInTheDocument();
        expect(mockPost).not.toHaveBeenCalled();
    });

    it('marks the release seen when dismissed, and closes', () => {
        render(<WhatsNewDialog />);

        fireEvent.click(
            screen.getByRole('button', {
                name: 'whatsNew.got_it',
                hidden: true,
            }),
        );

        expect(mockPost).toHaveBeenCalledWith(
            '/whats-new.seen',
            {},
            expect.any(Object),
        );
        expect(
            screen.getByRole('dialog', { hidden: true }),
        ).not.toHaveAttribute('open');
    });

    it('counts following the link to all the notes as seen', () => {
        render(<WhatsNewDialog />);

        fireEvent.click(screen.getByText('whatsNew.see_all'));

        expect(mockPost).toHaveBeenCalledTimes(1);
    });

    it('counts Escape as seen', () => {
        render(<WhatsNewDialog />);

        fireEvent(
            screen.getByRole('dialog', { hidden: true }),
            new Event('cancel', { cancelable: true }),
        );

        expect(mockPost).toHaveBeenCalledTimes(1);
    });

    it('marks a release without notes seen, without a dialog', () => {
        mockReleases = [RELEASES[2]];

        render(<WhatsNewDialog />);

        expect(mockPost).toHaveBeenCalledTimes(1);
        expect(
            screen.getByRole('dialog', { hidden: true }),
        ).not.toHaveAttribute('open');
    });

    it('stays out of the way of a user who is up to date', () => {
        mockProps = { release: { version: '1.1.0', unseenSince: null } };

        const { container } = render(<WhatsNewDialog />);

        expect(container).toBeEmptyDOMElement();
        expect(mockPost).not.toHaveBeenCalled();
    });
});
