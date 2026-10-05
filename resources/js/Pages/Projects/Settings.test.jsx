import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: { auth: { user: { name: 'A', email: 'a@x' } } } }),
    useForm: (initial) => ({
        data: initial,
        setData: vi.fn(),
        post: vi.fn(),
        processing: false,
        errors: {},
    }),
}));

vi.mock('@/Layouts/AuthenticatedLayout', () => ({
    default: ({ children }) => <div>{children}</div>,
}));

vi.mock('@/Components/DeletionModal', () => ({
    default: ({ url }) => <div data-testid="deletion" data-url={url} />,
}));

globalThis.route = (name, params) => `/${name}/${params?.project ?? ''}`;

import Settings from './Settings';

const project = {
    id: 5,
    name: 'Plantas útiles',
    author: 'R.',
    institution: '',
    author_email: 'r@x',
    country: 'GT',
};

describe('Projects/Settings', () => {
    it('edits the details in place, with nothing to cancel', () => {
        render(<Settings project={project} />);

        expect(screen.getByDisplayValue('Plantas útiles')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'actions.update' }),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'actions.cancel' }),
        ).not.toBeInTheDocument();
    });

    it('deletes the project from its settings', () => {
        render(<Settings project={project} />);

        expect(
            screen.getByRole('button', { name: 'projects.settings.delete' }),
        ).toBeInTheDocument();
        expect(screen.getByTestId('deletion')).toHaveAttribute(
            'data-url',
            '/projects.delete/5',
        );
    });
});
