import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import ChartCard from './ChartCard';

describe('ChartCard', () => {
    it('renders the download options and PNG/SVG buttons', () => {
        render(
            <ChartCard title="Chart" filename="chart">
                <svg data-testid="chart" />
            </ChartCard>,
        );

        expect(
            screen.getByRole('button', {
                name: 'data.reports.charts.download',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'data.reports.charts.png' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'data.reports.charts.svg' }),
        ).toBeInTheDocument();
        // Background + resolution selects.
        expect(screen.getAllByRole('combobox')).toHaveLength(2);
    });
});
