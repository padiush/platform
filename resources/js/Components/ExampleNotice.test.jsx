import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import ExampleNotice from './ExampleNotice';

const mockDelete = vi.fn();

vi.mock('@inertiajs/react', () => ({
    router: { delete: (...args) => mockDelete(...args) },
}));

globalThis.route = (name) => `/${name}`;

beforeEach(() => mockDelete.mockReset());

describe('ExampleNotice', () => {
    it('says the data is invented', () => {
        render(<ExampleNotice />);

        expect(screen.getByText('example.notice')).toBeInTheDocument();
    });

    it('removes the example only once the removal is confirmed', () => {
        render(<ExampleNotice />);

        fireEvent.click(screen.getByRole('button', { name: 'example.remove' }));
        expect(mockDelete).not.toHaveBeenCalled();

        const dialog = screen.getByRole('dialog');
        expect(
            within(dialog).getByText('example.remove_message'),
        ).toBeInTheDocument();

        fireEvent.click(
            within(dialog).getByRole('button', { name: 'example.remove' }),
        );
        expect(mockDelete).toHaveBeenCalledWith('/projects.example.destroy');
    });

    it('keeps the example when the removal is cancelled', () => {
        render(<ExampleNotice />);

        fireEvent.click(screen.getByRole('button', { name: 'example.remove' }));
        fireEvent.click(
            within(screen.getByRole('dialog')).getByRole('button', {
                name: 'actions.cancel',
            }),
        );

        expect(mockDelete).not.toHaveBeenCalled();
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
});
