import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import MetricCard from './MetricCard';

describe('MetricCard', () => {
    it('shows the caption and the value', () => {
        render(<MetricCard label="Species registered" value={25} />);

        expect(screen.getByText('Species registered')).toBeInTheDocument();
        expect(screen.getByText('25')).toBeInTheDocument();
    });

    it('applies the tone accent to the value', () => {
        render(<MetricCard label="Unlinked" value={3} tone="warning" />);

        expect(screen.getByText('3')).toHaveClass('text-warning');
    });
});
