import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import ConfirmModal from './ConfirmModal';

const noop = () => {};

describe('ConfirmModal', () => {
    it('renders the title, message and confirm label', () => {
        render(
            <ConfirmModal
                open
                title="Revoke access"
                message="Revoke Ana's access?"
                confirmLabel="Revoke"
                onConfirm={noop}
                onClose={noop}
            />,
        );

        expect(screen.getByText('Revoke access')).toBeInTheDocument();
        expect(screen.getByText("Revoke Ana's access?")).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Revoke' }),
        ).toBeInTheDocument();
    });

    it('calls onConfirm when the confirm button is clicked', () => {
        const onConfirm = vi.fn();
        render(
            <ConfirmModal
                open
                title="T"
                message="M"
                confirmLabel="Revoke"
                onConfirm={onConfirm}
                onClose={noop}
            />,
        );

        fireEvent.click(screen.getByRole('button', { name: 'Revoke' }));

        expect(onConfirm).toHaveBeenCalled();
    });

    it('calls onClose from cancel and from the backdrop', () => {
        const onClose = vi.fn();
        render(
            <ConfirmModal
                open
                title="T"
                message="M"
                confirmLabel="Revoke"
                onConfirm={noop}
                onClose={onClose}
            />,
        );

        // actions.cancel is the mocked key returned by the i18n stub.
        fireEvent.click(screen.getByRole('button', { name: 'actions.cancel' }));
        // The backdrop carries the close label.
        fireEvent.click(screen.getByRole('button', { name: 'actions.close' }));

        expect(onClose).toHaveBeenCalledTimes(2);
    });
});
