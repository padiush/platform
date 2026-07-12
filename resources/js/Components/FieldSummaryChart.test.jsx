import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

// Recharts needs real layout measurement that jsdom lacks, so stub it to
// lightweight pass-throughs — these tests cover the component's branching,
// not SVG geometry.
vi.mock('recharts', () => {
    const Pass = ({ children }) => <div>{children}</div>;
    const Nil = () => null;
    return {
        ResponsiveContainer: Pass,
        BarChart: Pass,
        Bar: Pass,
        XAxis: Nil,
        YAxis: Nil,
        CartesianGrid: Nil,
        Tooltip: Nil,
        LabelList: Nil,
    };
});

import FieldSummaryChart from './FieldSummaryChart';

describe('FieldSummaryChart', () => {
    it('shows an empty state when a categorical field has no data', () => {
        render(<FieldSummaryChart field={{ kind: 'categorical', data: [] }} />);

        expect(screen.getByText('data.view.no_chart_data')).toBeInTheDocument();
    });

    it('renders a categorical distribution', () => {
        render(
            <FieldSummaryChart
                field={{
                    kind: 'categorical',
                    data: [{ label: 'Alimento', count: 3 }],
                }}
            />,
        );

        expect(
            screen.queryByText('data.view.no_chart_data'),
        ).not.toBeInTheDocument();
    });

    it('renders numeric stats', () => {
        render(
            <FieldSummaryChart
                field={{
                    kind: 'number',
                    data: [{ label: '0–1', count: 2 }],
                    stats: { count: 2, min: 1, max: 6, mean: 3.5, median: 3.5 },
                }}
            />,
        );

        expect(screen.getByText('data.view.stats.count')).toBeInTheDocument();
    });

    it('shows an empty state when a numeric field has no values', () => {
        render(
            <FieldSummaryChart
                field={{ kind: 'number', data: [], stats: null }}
            />,
        );

        expect(screen.getByText('data.view.no_chart_data')).toBeInTheDocument();
    });
});
