import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
}));

vi.mock('@/Layouts/AuthenticatedLayout', () => ({
    default: ({ children, title }) => (
        <div>
            <h1>{title}</h1>
            {children}
        </div>
    ),
}));

globalThis.route = (name, params) => `/${name}/${params}`;

import Overview from './Overview';

const project = { id: 1, name: 'Plantas útiles', finished: false };

const sections = {
    overview: '/projects/1',
    interviews: '/interviews?project=1',
    records: '/catalogs/1/records',
    catalog: '/catalogs?project=1',
    data: '/data?project=1',
};

function renderPage(props = {}) {
    return render(
        <Overview
            project={project}
            counts={{
                interviews: 4,
                field_records: 13,
                species: 9,
                unlinked_answers: 2,
            }}
            waiting={{
                pending_media: 1,
                undetermined_records: 10,
                unlinked_answers: 2,
            }}
            recentInterviews={[
                {
                    id: 'a9731e28',
                    form: 'Usos de plantas',
                    recorded_by: 'Equipo Padiush',
                    created_at: '2026-10-04T20:01:00Z',
                },
            ]}
            recentRecords={[
                {
                    id: 13,
                    vernacular_name: 'Ruda',
                    was_collected: false,
                    species: null,
                    collected_on: '2026-10-04',
                },
            ]}
            sections={sections}
            can={{ link_species: true, export: true }}
            {...props}
        />,
    );
}

describe('Overview', () => {
    it('names the project and counts what it has gathered', () => {
        renderPage();

        expect(screen.getByRole('heading', { name: 'Plantas útiles' }));
        expect(screen.getByText('13').closest('a')).toHaveAttribute(
            'href',
            '/catalogs/1/records',
        );
    });

    /** Each waiting item opens where it is dealt with. */
    it('links what is waiting to where it is done', () => {
        renderPage();

        expect(
            screen.getByText(/overview\.undetermined_records/).closest('a'),
        ).toHaveAttribute('href', '/catalogs/1/records?filter=undetermined');
        expect(
            screen.getByText(/overview\.unlinked_answers/).closest('a'),
        ).toHaveAttribute('href', '/data.link/1');
        // Nothing to do on the web about a file a phone has not sent.
        expect(
            screen.getByText(/overview\.pending_media/).closest('a'),
        ).toBeNull();
    });

    it('says when nothing is waiting', () => {
        renderPage({
            waiting: {
                pending_media: 0,
                undetermined_records: 0,
                unlinked_answers: 0,
            },
        });

        expect(
            screen.getByText('overview.nothing_waiting'),
        ).toBeInTheDocument();
    });

    it('opens a recent record on its row', () => {
        renderPage();

        expect(screen.getByText('Ruda').closest('a')).toHaveAttribute(
            'href',
            '/catalogs/1/records#record-13',
        );
    });

    /** A role that records nothing is not shown who recorded what. */
    it('leaves out what the role cannot open', () => {
        renderPage({
            recentInterviews: null,
            sections: {
                overview: '/projects/1',
                records: '/catalogs/1/records',
            },
            can: { link_species: false, export: false },
        });

        expect(
            screen.queryByText('overview.recent_interviews'),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByText('overview.new_interview'),
        ).not.toBeInTheDocument();
        expect(screen.queryByText('overview.export')).not.toBeInTheDocument();
        expect(
            screen.getByText(/overview\.unlinked_answers/).closest('a'),
        ).toBeNull();
    });

    it('says a finished project takes no new work', () => {
        renderPage({ project: { ...project, finished: true } });

        expect(screen.getByRole('status')).toHaveTextContent(
            'overview.finished',
        );
    });
});
