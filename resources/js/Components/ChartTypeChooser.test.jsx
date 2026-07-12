import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import ChartTypeChooser from './ChartTypeChooser';

describe('ChartTypeChooser', () => {
    it('renders a button per available type and marks the active one', () => {
        render(
            <ChartTypeChooser
                available={['bar', 'pie', 'table']}
                value="pie"
                onChange={() => {}}
            />,
        );

        expect(screen.getAllByRole('button')).toHaveLength(3);
        expect(
            screen.getByRole('button', { name: 'data.view.chart.pie' }),
        ).toHaveAttribute('aria-pressed', 'true');
    });

    it('calls onChange with the picked type', () => {
        const onChange = vi.fn();
        render(
            <ChartTypeChooser
                available={['bar', 'pie', 'table']}
                value="bar"
                onChange={onChange}
            />,
        );

        fireEvent.click(
            screen.getByRole('button', { name: 'data.view.chart.table' }),
        );

        expect(onChange).toHaveBeenCalledWith('table');
    });

    it('renders nothing when there is nothing to choose', () => {
        const { container } = render(
            <ChartTypeChooser
                available={['bar']}
                value="bar"
                onChange={() => {}}
            />,
        );

        expect(container).toBeEmptyDOMElement();
    });
});
