import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
}));

vi.mock('@/Layouts/AuthenticatedLayout', () => ({
    default: ({ children }) => <div>{children}</div>,
}));

globalThis.route = (name, params) =>
    `/${name}/${Object.values(params).join('/')}`;

import InterviewOverview from './Index';

const project = { id: 3, name: 'A study' };

describe('Interviews/Index', () => {
    it('starts or lists interviews on each form, in the project', () => {
        render(
            <InterviewOverview
                project={project}
                forms={[{ id: 8, name: 'Usos', instances_count: 4 }]}
            />,
        );

        expect(
            screen.getByRole('link', { name: 'interviews.new_interview' }),
        ).toHaveAttribute('href', '/interviews.create/3/8');
        expect(
            screen.getByRole('link', { name: 'interviews.view_existing' }),
        ).toHaveAttribute('href', '/interviews.instances/3/8');
        expect(
            screen.getByText('designer.interviews_recorded {"count":4}'),
        ).toBeInTheDocument();
    });

    it('points at Formularios when no form is on', () => {
        render(<InterviewOverview project={project} forms={[]} />);

        expect(
            screen.getByText('interviews.no_interviews'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('interviews.no_active_forms_hint'),
        ).toBeInTheDocument();
    });
});
