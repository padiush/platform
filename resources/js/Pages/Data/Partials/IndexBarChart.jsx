import { useMemo, useState } from 'react';
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

// Single-series magnitude: one primary hue, base-content ink for text, a
// recessive grid — following the app's chart conventions.
const FILL = 'var(--color-primary)';
const INK = 'var(--color-base-content)';
const GRID = 'var(--color-base-300)';

const INDICES = [
    { key: 'fc', integer: true, nameKey: 'data.reports.fc_full' },
    { key: 'nu', integer: true, nameKey: 'data.reports.nu_full' },
    { key: 'rfc', integer: false, nameKey: 'data.reports.rfc_full' },
    { key: 'uv', integer: false, nameKey: 'data.reports.uv_full' },
    { key: 'ci', integer: false, nameKey: 'data.reports.ci_full' },
    { key: 'ri', integer: false, nameKey: 'data.reports.ri_full' },
    { key: 'cv', integer: false, nameKey: 'data.reports.cv_full' },
];
const TOP = 12;

/** Top species ranked by a selectable index, as horizontal bars. */
export default function IndexBarChart({ species }) {
    const { t } = useTranslation();
    const [metric, setMetric] = useState('uv');

    const config = INDICES.find((index) => index.key === metric);
    const format = (value) =>
        config.integer ? String(value) : Number(value).toFixed(2);

    const data = useMemo(
        () =>
            [...species]
                .sort((a, b) => b[metric] - a[metric])
                .slice(0, TOP)
                .map((entry) => ({
                    name: `${entry.species.genus} ${entry.species.name}`,
                    value: Number(entry[metric]),
                })),
        [species, metric],
    );

    return (
        <div className="space-y-3">
            <label className="flex flex-wrap items-center gap-2 text-sm">
                <span className="opacity-70">
                    {t('data.reports.charts.index')}
                </span>
                <select
                    className="select select-bordered select-sm w-auto"
                    value={metric}
                    onChange={(event) => setMetric(event.target.value)}
                >
                    {INDICES.map((index) => (
                        <option key={index.key} value={index.key}>
                            {index.key.toUpperCase()} — {t(index.nameKey)}
                        </option>
                    ))}
                </select>
            </label>

            <ResponsiveContainer
                width="100%"
                height={Math.max(220, data.length * 34)}
            >
                <BarChart
                    layout="vertical"
                    data={data}
                    margin={{ top: 4, right: 44, bottom: 4, left: 4 }}
                >
                    <CartesianGrid
                        horizontal={false}
                        stroke={GRID}
                        strokeOpacity={0.4}
                    />
                    <XAxis
                        type="number"
                        tick={{ fill: INK, fontSize: 12 }}
                        stroke={GRID}
                    />
                    <YAxis
                        type="category"
                        dataKey="name"
                        width={150}
                        tick={{ fill: INK, fontSize: 12 }}
                        stroke={GRID}
                    />
                    <Tooltip
                        cursor={{ fill: GRID, fillOpacity: 0.15 }}
                        formatter={(value) => [
                            format(value),
                            metric.toUpperCase(),
                        ]}
                        contentStyle={{
                            background: 'var(--color-base-100)',
                            border: '1px solid var(--color-base-300)',
                            borderRadius: 8,
                            color: INK,
                        }}
                    />
                    <Bar
                        dataKey="value"
                        fill={FILL}
                        radius={[0, 4, 4, 0]}
                        maxBarSize={22}
                    >
                        <LabelList
                            dataKey="value"
                            position="right"
                            fill={INK}
                            fontSize={11}
                            formatter={format}
                        />
                    </Bar>
                </BarChart>
            </ResponsiveContainer>
        </div>
    );
}
