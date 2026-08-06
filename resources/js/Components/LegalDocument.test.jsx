import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import LegalDocument from './LegalDocument';

const DOCUMENT = {
    title: 'Privacy policy',
    updated: '6 August 2026',
    summary: 'What we collect and why.',
    sections: [
        {
            heading: '1. Who we are',
            blocks: [{ type: 'p', text: 'We operate Padiush.' }],
        },
        {
            heading: '2. What we collect',
            blocks: [
                {
                    type: 'ul',
                    items: [
                        'A plain bullet.',
                        { term: 'Account data:', text: 'name and email.' },
                    ],
                },
                {
                    type: 'links',
                    items: [
                        {
                            label: 'hola@padiushbio.com',
                            href: 'mailto:hola@padiushbio.com',
                        },
                        { label: 'Contact form', href: '/contacto' },
                        { label: 'GBIF', href: 'https://gbif.org' },
                    ],
                },
            ],
        },
    ],
};

// This component reads structured objects out of the `legal` namespace, so the
// shared key-echoing mock in tests/setup.js isn't enough here.
let ready = true;

vi.mock('react-i18next', () => ({
    useTranslation: () => ({
        ready,
        t: (key, options) => {
            const path = key.replace(/^privacy\./, '');

            if (key === 'updated_on') {
                return `Updated on ${options.date}`;
            }

            return options?.returnObjects ? DOCUMENT[path] : DOCUMENT[path];
        },
    }),
}));

describe('LegalDocument', () => {
    it('renders the document with a single h1 and one h2 per section', () => {
        render(<LegalDocument document="privacy" />);

        const headingOne = screen.getAllByRole('heading', { level: 1 });
        expect(headingOne).toHaveLength(1);
        expect(headingOne[0]).toHaveTextContent('Privacy policy');

        const headingTwo = screen.getAllByRole('heading', { level: 2 });
        expect(headingTwo.map((h) => h.textContent)).toEqual([
            '1. Who we are',
            '2. What we collect',
        ]);

        expect(
            screen.getByText('Updated on 6 August 2026'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('What we collect and why.'),
        ).toBeInTheDocument();
        expect(screen.getByText('We operate Padiush.')).toBeInTheDocument();
    });

    it('renders both plain and term bullets', () => {
        render(<LegalDocument document="privacy" />);

        expect(screen.getByText('A plain bullet.')).toBeInTheDocument();
        expect(screen.getByText('Account data:').tagName).toBe('STRONG');
        expect(screen.getByText(/name and email\./)).toBeInTheDocument();
    });

    it('opens only external links in a new tab', () => {
        render(<LegalDocument document="privacy" />);

        const email = screen.getByRole('link', {
            name: 'hola@padiushbio.com',
        });
        expect(email).toHaveAttribute('href', 'mailto:hola@padiushbio.com');
        expect(email).not.toHaveAttribute('target');

        const internal = screen.getByRole('link', { name: 'Contact form' });
        expect(internal).toHaveAttribute('href', '/contacto');
        expect(internal).not.toHaveAttribute('target');

        const external = screen.getByRole('link', { name: 'GBIF' });
        expect(external).toHaveAttribute('target', '_blank');
        expect(external).toHaveAttribute('rel', 'noreferrer');
    });

    it('shows a loading state until the namespace resolves', () => {
        ready = false;

        try {
            render(<LegalDocument document="privacy" />);

            expect(screen.getByRole('status')).toBeInTheDocument();
            expect(
                screen.queryByRole('heading', { level: 1 }),
            ).not.toBeInTheDocument();
        } finally {
            ready = true;
        }
    });
});
