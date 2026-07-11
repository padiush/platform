import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import Input from './Input';

describe('Input accessibility', () => {
    it('associates the visible label with the control', () => {
        render(<Input label="Project Name" onChange={() => {}} value="" />);

        const input = screen.getByLabelText(/Project Name/);
        expect(input).toBeInTheDocument();
        expect(input.tagName).toBe('INPUT');
    });

    it('associates textarea labels too', () => {
        render(
            <Input
                type="textarea"
                label="Description"
                onChange={() => {}}
                value=""
            />,
        );

        expect(screen.getByLabelText(/Description/).tagName).toBe('TEXTAREA');
    });

    it('links errors to the control via aria attributes', () => {
        render(
            <Input
                label="Email"
                error="Email is invalid"
                onChange={() => {}}
                value=""
            />,
        );

        const input = screen.getByLabelText(/Email/);
        expect(input).toHaveAttribute('aria-invalid', 'true');

        const alert = screen.getByRole('alert');
        expect(alert).toHaveTextContent('Email is invalid');
        expect(input.getAttribute('aria-describedby')).toContain(alert.id);
    });

    it('does not mark valid fields as invalid', () => {
        render(<Input label="Country" onChange={() => {}} value="" />);

        const input = screen.getByLabelText(/Country/);
        expect(input).not.toHaveAttribute('aria-invalid');
        expect(input).not.toHaveAttribute('aria-describedby');
    });

    it('respects a caller-provided id', () => {
        render(
            <Input label="Name" id="custom-id" onChange={() => {}} value="" />,
        );

        expect(screen.getByLabelText(/Name/).id).toBe('custom-id');
    });
});
