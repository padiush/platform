import { fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    // `method` and `as` are Inertia's; kept off the plain anchor.
    // eslint-disable-next-line no-unused-vars
    Link: ({ children, method, as, ...props }) => <a {...props}>{children}</a>,
}));

vi.mock('@/Layouts/AuthenticatedLayout', () => ({
    default: ({ children }) => <div>{children}</div>,
}));

vi.mock('@/Components/DeletionModal', () => ({ default: () => null }));

// The form itself is not under test; here it only has to be the one opened.
vi.mock('./Partials/FormDetailsForm', () => ({
    default: ({ form }) => (
        <form aria-label={form ? `edit-${form.id}` : 'create'} />
    ),
}));

globalThis.route = (name, params) =>
    `/${name}/${Object.values(params).join('/')}`;

import DesignerIndex from './Index';

const project = { id: 3, name: 'A study' };
const form = {
    id: 8,
    name: 'Usos',
    description: null,
    is_active: true,
    instances_count: 2,
};

afterEach(() => window.history.replaceState(null, '', '/'));

describe('Designer/Index', () => {
    it('opens each form in the project, and its interviews', () => {
        render(<DesignerIndex project={project} forms={[form]} />);

        expect(
            screen.getByRole('link', { name: 'designer.index.wizard' }),
        ).toHaveAttribute('href', '/designer.form.wizard/3/8');
        expect(
            screen.getByRole('link', {
                name: 'designer.interviews_recorded {"count":2}',
            }),
        ).toHaveAttribute('href', '/interviews.instances/3/8');
    });

    it('creates a form in the project', () => {
        render(<DesignerIndex project={project} forms={[]} />);

        expect(
            screen.getByText('designer.index.no_forms_hint'),
        ).toBeInTheDocument();

        fireEvent.click(
            screen.getByRole('button', { name: 'designer.index.create' }),
        );

        expect(screen.getByLabelText('create')).toBeInTheDocument();
        expect(window.location.search).toBe('?create=1');
    });

    it('opens a form’s details from a deep link', () => {
        window.history.replaceState(null, '', '/?edit=8');
        render(<DesignerIndex project={project} forms={[form]} />);

        expect(screen.getByLabelText('edit-8')).toBeInTheDocument();
    });

    it('says which forms take interviews', () => {
        render(
            <DesignerIndex
                project={project}
                forms={[form, { ...form, id: 9, is_active: false }]}
            />,
        );

        expect(
            screen.getByText('designer.index.status_on'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('designer.index.status_off'),
        ).toBeInTheDocument();
    });
});
