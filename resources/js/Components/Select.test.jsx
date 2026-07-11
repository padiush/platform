import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import Select from './Select';

describe('Select accessibility', () => {
    it('associates the visible label with the select', () => {
        render(
            <Select label="Field type" onChange={() => {}} value="a">
                <option value="a">A</option>
            </Select>,
        );

        expect(screen.getByLabelText(/Field type/).tagName).toBe('SELECT');
    });

    it('links errors to the select via aria attributes', () => {
        render(
            <Select
                label="Field type"
                error="Required"
                onChange={() => {}}
                value="a"
            >
                <option value="a">A</option>
            </Select>,
        );

        const select = screen.getByLabelText(/Field type/);
        expect(select).toHaveAttribute('aria-invalid', 'true');
        expect(select.getAttribute('aria-describedby')).toContain(
            screen.getByRole('alert').id,
        );
    });
});
