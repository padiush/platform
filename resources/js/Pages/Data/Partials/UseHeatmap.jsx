import { useTranslation } from 'react-i18next';

const FILL = 'var(--color-primary)';
const INK = 'var(--color-base-content)';
const GRID = 'var(--color-base-300)';

const TOP = 12;
const LABEL_W = 150;
const HEADER_H = 34;
const CELL_W = 96;
const CELL_H = 32;
const PAD = 4;

/**
 * Species × use-category matrix, cells shaded by use-report count (sequential:
 * one hue, faint → saturated). Hand-built SVG so it exports as PNG like the
 * Recharts charts.
 */
export default function UseHeatmap({ species, useCategories }) {
    const { t } = useTranslation();

    const categories = useCategories.map((entry) => entry.use_category);

    const rows = [...species]
        .map((entry) => ({
            name: `${entry.species.genus} ${entry.species.name}`,
            byCategory: Object.fromEntries(
                entry.uses.map((use) => [use.use_category, use.reports]),
            ),
            total: entry.uses.reduce((sum, use) => sum + use.reports, 0),
        }))
        .sort((a, b) => b.total - a.total)
        .slice(0, TOP);

    const max = Math.max(
        1,
        ...rows.flatMap((row) => categories.map((c) => row.byCategory[c] || 0)),
    );

    const width = LABEL_W + categories.length * CELL_W;
    const height = HEADER_H + rows.length * CELL_H;

    return (
        <div className="overflow-x-auto">
            <svg
                viewBox={`0 0 ${width} ${height}`}
                width={width}
                height={height}
                style={{ maxWidth: '100%', height: 'auto' }}
                role="img"
            >
                {categories.map((category, ci) => (
                    <text
                        key={category}
                        x={LABEL_W + ci * CELL_W + CELL_W / 2}
                        y={HEADER_H - 12}
                        textAnchor="middle"
                        fontSize="12"
                        fill={INK}
                    >
                        {category}
                    </text>
                ))}

                {rows.map((row, ri) => (
                    <g key={row.name}>
                        <text
                            x={LABEL_W - 8}
                            y={HEADER_H + ri * CELL_H + CELL_H / 2}
                            textAnchor="end"
                            dominantBaseline="middle"
                            fontSize="12"
                            fontStyle="italic"
                            fill={INK}
                        >
                            {row.name}
                        </text>
                        {categories.map((category, ci) => {
                            const value = row.byCategory[category] || 0;
                            const x = LABEL_W + ci * CELL_W;
                            const y = HEADER_H + ri * CELL_H;
                            const opacity =
                                value > 0 ? 0.15 + 0.85 * (value / max) : 0;
                            return (
                                <g key={category}>
                                    <rect
                                        x={x + PAD}
                                        y={y + PAD}
                                        width={CELL_W - 2 * PAD}
                                        height={CELL_H - 2 * PAD}
                                        rx="3"
                                        fill={FILL}
                                        fillOpacity={opacity}
                                        stroke={GRID}
                                        strokeOpacity="0.3"
                                    />
                                    {value > 0 && (
                                        <text
                                            x={x + CELL_W / 2}
                                            y={y + CELL_H / 2}
                                            textAnchor="middle"
                                            dominantBaseline="middle"
                                            fontSize="11"
                                            fill={INK}
                                        >
                                            {value}
                                        </text>
                                    )}
                                </g>
                            );
                        })}
                    </g>
                ))}
            </svg>
            <p className="mt-2 text-xs opacity-60">
                {t('data.reports.charts.heatmap_note')}
            </p>
        </div>
    );
}
