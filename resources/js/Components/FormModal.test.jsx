import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import FormModal from './FormModal';

describe('FormModal', () => {
    it('renders the title and children when open', () => {
        render(
            <FormModal open title="My title" onClose={() => {}}>
                <p>Body</p>
            </FormModal>,
        );

        expect(screen.getByText('My title')).toBeInTheDocument();
        expect(screen.getByText('Body')).toBeInTheDocument();
    });

    it('does not render children while closed', () => {
        render(
            <FormModal open={false} title="T" onClose={() => {}}>
                <p>Body</p>
            </FormModal>,
        );

        expect(screen.queryByText('Body')).not.toBeInTheDocument();
    });

    it('reports a close when the close button is clicked', () => {
        const onClose = vi.fn();
        render(
            <FormModal open title="T" onClose={onClose}>
                <p>Body</p>
            </FormModal>,
        );

        fireEvent.click(
            screen.getAllByRole('button', { name: 'actions.close' })[0],
        );

        expect(onClose).toHaveBeenCalled();
    });
});
