import { useTranslation } from 'react-i18next';
import {
    Bar,
    BarChart,
    CartesianGrid,
    LabelList,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

// Single-series bars follow the app theme: one primary hue for the marks,
// base-content ink for text/labels (never the series color), recessive axes.
const FILL = 'var(--color-primary)';
const INK = 'var(--color-base-content)';
const LINE = 'var(--color-base-300)';

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

/**
 * Renders one field's distribution: horizontal bars for categorical/species
 * (readable labels on the axis), vertical bars for numeric histograms and
 * month time-series. One series, so no legend — the card title names it.
 */
export default function FieldSummaryChart({ field }) {
    const { t } = useTranslation();
    const { kind, data = [], stats = null } = field;

    const isEmpty = kind === 'number' ? !stats : data.length === 0;

    if (isEmpty) {
        return (
            <div className="text-base-content/40 flex h-32 items-center justify-center text-sm">
                {t('data.view.no_chart_data')}
            </div>
        );
    }

    if (kind === 'categorical' || kind === 'species') {
        const height = Math.max(120, data.length * 34);

        return (
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
        );
    }

    const angled = kind === 'number';

    return (
        <>
            {kind === 'number' && stats && <Stats stats={stats} t={t} />}
            <ResponsiveContainer width="100%" height={180}>
                <BarChart
                    data={data}
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
