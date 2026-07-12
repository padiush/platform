import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    LabelList,
    Legend,
    Line,
    LineChart,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

// Single-series marks follow the app theme: one primary hue, base-content ink
// for text/labels (never the series color), recessive axes.
const FILL = 'var(--color-primary)';
const INK = 'var(--color-base-content)';
const LINE = 'var(--color-base-300)';

// Validated categorical palette (dataviz skill, references/palette.md): eight
// fixed-order hues, stepped for each surface. Pie is the only multi-hue chart.
const CATEGORICAL_LIGHT = [
    '#2a78d6',
    '#1baf7a',
    '#eda100',
    '#008300',
    '#4a3aa7',
    '#e34948',
    '#e87ba4',
    '#eb6834',
];
const CATEGORICAL_DARK = [
    '#3987e5',
    '#199e70',
    '#c98500',
    '#008300',
    '#9085e9',
    '#e66767',
    '#d55181',
    '#d95926',
];
const OTHER_COLOR = '#898781';

const CHART_TOP = 12;
const PIE_TOP = 7;

const tooltipProps = {
    cursor: { fill: 'var(--color-base-200)' },
    contentStyle: {
        background: 'var(--color-base-100)',
        border: `1px solid ${LINE}`,
        borderRadius: '0.5rem',
        color: INK,
        fontSize: '0.8rem',
    },
    labelStyle: { color: INK },
};

// The theme toggle stamps data-theme on <html>; track it so the pie palette
// recolors when the user switches light/dark.
function useIsDark() {
    const read = () =>
        typeof document !== 'undefined' &&
        (document.documentElement.getAttribute('data-theme') || '').includes(
            'dark',
        );
    const [dark, setDark] = useState(read);

    useEffect(() => {
        const el = document.documentElement;
        const observer = new MutationObserver(() => setDark(read()));
        observer.observe(el, {
            attributes: true,
            attributeFilter: ['data-theme'],
        });
        return () => observer.disconnect();
    }, []);

    return dark;
}

function Empty({ label }) {
    return (
        <div className="text-base-content/40 flex h-32 items-center justify-center text-sm">
            {label}
        </div>
    );
}

function Stats({ stats, t }) {
    const cells = [
        ['count', stats.count],
        ['min', stats.min],
        ['max', stats.max],
        ['mean', stats.mean],
        ['median', stats.median],
    ];

    return (
        <div className="text-base-content/70 mb-2 flex flex-wrap gap-x-4 gap-y-1 text-xs tabular-nums">
            {cells.map(([key, value]) => (
                <span key={key}>
                    <span className="text-base-content/50">
                        {t(`data.view.stats.${key}`)}
                    </span>{' '}
                    {value}
                </span>
            ))}
        </div>
    );
}

function CategoricalBars({ field, t }) {
    const data = field.data.slice(0, CHART_TOP);
    const height = Math.max(120, data.length * 34);
    const truncated = (field.total_distinct ?? data.length) > CHART_TOP;

    return (
        <>
            <ResponsiveContainer width="100%" height={height}>
                <BarChart
                    layout="vertical"
                    data={data}
                    margin={{ left: 4, right: 28, top: 4, bottom: 4 }}
                >
                    <CartesianGrid horizontal={false} stroke={LINE} />
                    <XAxis
                        type="number"
                        allowDecimals={false}
                        tick={{ fill: INK, fillOpacity: 0.55, fontSize: 12 }}
                        stroke={LINE}
                    />
                    <YAxis
                        type="category"
                        dataKey="label"
                        width={128}
                        tick={{ fill: INK, fillOpacity: 0.8, fontSize: 12 }}
                        stroke={LINE}
                    />
                    <Tooltip {...tooltipProps} />
                    <Bar
                        dataKey="count"
                        fill={FILL}
                        radius={[0, 4, 4, 0]}
                        maxBarSize={22}
                    >
                        <LabelList
                            dataKey="count"
                            position="right"
                            fill={INK}
                            fontSize={11}
                        />
                    </Bar>
                </BarChart>
            </ResponsiveContainer>
            {truncated && (
                <p className="text-base-content/50 mt-1 text-center text-xs">
                    {t('data.view.chart.top_n_of_m', {
                        shown: CHART_TOP,
                        total: field.total_distinct,
                    })}
                </p>
            )}
        </>
    );
}

function VerticalBars({ field }) {
    const { t } = useTranslation();
    const angled = field.kind === 'number';

    return (
        <>
            {field.kind === 'number' && field.stats && (
                <Stats stats={field.stats} t={t} />
            )}
            <ResponsiveContainer width="100%" height={180}>
                <BarChart
                    data={field.data}
                    margin={{ left: 0, right: 8, top: 12, bottom: 4 }}
                >
                    <CartesianGrid vertical={false} stroke={LINE} />
                    <XAxis
                        dataKey="label"
                        interval={0}
                        angle={angled ? -25 : 0}
                        textAnchor={angled ? 'end' : 'middle'}
                        height={angled ? 52 : 24}
                        tick={{ fill: INK, fillOpacity: 0.55, fontSize: 11 }}
                        stroke={LINE}
                    />
                    <YAxis
                        allowDecimals={false}
                        width={28}
                        tick={{ fill: INK, fillOpacity: 0.55, fontSize: 12 }}
                        stroke={LINE}
                    />
                    <Tooltip {...tooltipProps} />
                    <Bar dataKey="count" fill={FILL} radius={[4, 4, 0, 0]}>
                        <LabelList
                            dataKey="count"
                            position="top"
                            fill={INK}
                            fontSize={11}
                        />
                    </Bar>
                </BarChart>
            </ResponsiveContainer>
        </>
    );
}

function PieView({ field, t }) {
    const dark = useIsDark();
    const palette = dark ? CATEGORICAL_DARK : CATEGORICAL_LIGHT;

    const top = field.data.slice(0, PIE_TOP);
    const shown = top.reduce((sum, d) => sum + d.count, 0);
    const total = field.total_count ?? shown;
    const other = total - shown;

    const slices =
        other > 0
            ? [
                  ...top,
                  {
                      label: t('data.view.chart.other'),
                      count: other,
                      other: true,
                  },
              ]
            : top;

    return (
        <ResponsiveContainer width="100%" height={280}>
            <PieChart>
                <Pie
                    data={slices}
                    dataKey="count"
                    nameKey="label"
                    cx="50%"
                    cy="45%"
                    outerRadius={80}
                    label={({ percent }) => `${Math.round(percent * 100)}%`}
                    labelLine={false}
                >
                    {slices.map((slice, index) => (
                        <Cell
                            key={index}
                            fill={
                                slice.other
                                    ? OTHER_COLOR
                                    : palette[index % palette.length]
                            }
                        />
                    ))}
                </Pie>
                <Tooltip {...tooltipProps} />
                <Legend wrapperStyle={{ fontSize: '0.75rem', color: INK }} />
            </PieChart>
        </ResponsiveContainer>
    );
}

function LineView({ field }) {
    return (
        <ResponsiveContainer width="100%" height={200}>
            <LineChart
                data={field.data}
                margin={{ left: 0, right: 12, top: 12, bottom: 4 }}
            >
                <CartesianGrid vertical={false} stroke={LINE} />
                <XAxis
                    dataKey="label"
                    tick={{ fill: INK, fillOpacity: 0.55, fontSize: 11 }}
                    stroke={LINE}
                />
                <YAxis
                    allowDecimals={false}
                    width={28}
                    tick={{ fill: INK, fillOpacity: 0.55, fontSize: 12 }}
                    stroke={LINE}
                />
                <Tooltip {...tooltipProps} />
                <Line
                    type="monotone"
                    dataKey="count"
                    stroke={FILL}
                    strokeWidth={2}
                    dot={{ r: 3, fill: FILL }}
                />
            </LineChart>
        </ResponsiveContainer>
    );
}

function TableView({ field, t }) {
    if (field.kind === 'number') {
        return <Stats stats={field.stats} t={t} />;
    }

    const total =
        field.total_count ?? field.data.reduce((sum, d) => sum + d.count, 0);
    const shown = field.data.reduce((sum, d) => sum + d.count, 0);
    const hiddenCount =
        (field.total_distinct ?? field.data.length) - field.data.length;
    const pct = (count) =>
        total > 0 ? `${Math.round((count / total) * 100)}%` : '—';

    return (
        <div className="max-h-72 overflow-auto">
            <table className="table-sm table">
                <thead>
                    <tr>
                        <th>{t('data.view.chart.value')}</th>
                        <th className="text-right">
                            {t('data.view.chart.count')}
                        </th>
                        <th className="text-right">
                            {t('data.view.chart.percent')}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {field.data.map((row) => (
                        <tr key={row.label}>
                            <td className="truncate">{row.label}</td>
                            <td className="text-right tabular-nums">
                                {row.count}
                            </td>
                            <td className="text-right tabular-nums">
                                {pct(row.count)}
                            </td>
                        </tr>
                    ))}
                    {hiddenCount > 0 && (
                        <tr className="text-base-content/60">
                            <td>
                                {t('data.view.chart.other')} ({hiddenCount})
                            </td>
                            <td className="text-right tabular-nums">
                                {total - shown}
                            </td>
                            <td className="text-right tabular-nums">
                                {pct(total - shown)}
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    );
}

/**
 * Renders one field's distribution in the chosen chart type. `chartType`
 * comes from the user's (persisted) choice; each type is gated to the field's
 * kind by the chooser, so only valid combinations reach here.
 */
export default function FieldSummaryChart({ field, chartType }) {
    const { t } = useTranslation();
    const type = chartType || field.chart_type || 'bar';

    const isEmpty =
        field.kind === 'number'
            ? !field.stats
            : (field.data?.length ?? 0) === 0;

    if (isEmpty) {
        return <Empty label={t('data.view.no_chart_data')} />;
    }

    if (type === 'table') {
        return <TableView field={field} t={t} />;
    }

    if (type === 'pie') {
        return <PieView field={field} t={t} />;
    }

    if (type === 'line') {
        return <LineView field={field} />;
    }

    if (field.kind === 'categorical' || field.kind === 'species') {
        return <CategoricalBars field={field} t={t} />;
    }

    return <VerticalBars field={field} />;
}
