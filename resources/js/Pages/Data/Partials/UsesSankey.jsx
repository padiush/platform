import { useMemo } from 'react';
import { ResponsiveContainer, Sankey, Tooltip } from 'recharts';

const SPECIES = 'var(--color-primary)';
const CATEGORY = 'var(--color-secondary)';
const LINK = 'var(--color-primary)';
const INK = 'var(--color-base-content)';

const TOP = 12;

/** A Sankey node: coloured rect + a label on its outer side. */
function SankeyNode({ x, y, width, height, payload, containerWidth }) {
    const isLeft = x + width / 2 < containerWidth / 2;
    return (
        <g>
            <rect
                x={x}
                y={y}
                width={width}
                height={height}
                rx={2}
                fill={isLeft ? SPECIES : CATEGORY}
                fillOpacity={0.9}
            />
            <text
                x={isLeft ? x - 6 : x + width + 6}
                y={y + height / 2}
                textAnchor={isLeft ? 'end' : 'start'}
                dominantBaseline="middle"
                fontSize={11}
                fontStyle={isLeft ? 'italic' : 'normal'}
                fill={INK}
            >
                {payload.name}
            </text>
        </g>
    );
}

function SankeyLink({
    sourceX,
    sourceY,
    sourceControlX,
    targetControlX,
    targetX,
    targetY,
    linkWidth,
}) {
    return (
        <path
            d={`M${sourceX},${sourceY} C${sourceControlX},${sourceY} ${targetControlX},${targetY} ${targetX},${targetY}`}
            fill="none"
            stroke={LINK}
            strokeWidth={Math.max(1, linkWidth)}
            strokeOpacity={0.3}
        />
    );
}

/** Flows of use reports from species (left) to use categories (right). */
export default function UsesSankey({ species, useCategories }) {
    const categories = useCategories.map((entry) => entry.use_category);

    const data = useMemo(() => {
        const top = [...species]
            .map((entry) => ({
                name: `${entry.species.genus} ${entry.species.name}`,
                uses: entry.uses,
                total: entry.uses.reduce((sum, use) => sum + use.reports, 0),
            }))
            .sort((a, b) => b.total - a.total)
            .slice(0, TOP);

        const nodes = [
            ...top.map((entry) => ({ name: entry.name })),
            ...categories.map((category) => ({ name: category })),
        ];

        const links = [];
        top.forEach((entry, si) => {
            entry.uses.forEach((use) => {
                const ci = categories.indexOf(use.use_category);
                if (ci >= 0) {
                    links.push({
                        source: si,
                        target: top.length + ci,
                        value: use.reports,
                    });
                }
            });
        });

        return { nodes, links, rows: Math.max(top.length, categories.length) };
    }, [species, categories]);

    return (
        // The node labels need fixed room on each side, so keep a legible width
        // and let narrow screens scroll horizontally (like the wide tables).
        <div className="overflow-x-auto">
            <div style={{ minWidth: 560 }}>
                <ResponsiveContainer
                    width="100%"
                    height={Math.max(300, data.rows * 42)}
                >
                    <Sankey
                        data={{ nodes: data.nodes, links: data.links }}
                        node={<SankeyNode />}
                        link={<SankeyLink />}
                        nodePadding={18}
                        margin={{ top: 8, right: 110, bottom: 8, left: 150 }}
                    >
                        <Tooltip
                            contentStyle={{
                                background: 'var(--color-base-100)',
                                border: '1px solid var(--color-base-300)',
                                borderRadius: 8,
                                color: INK,
                            }}
                        />
                    </Sankey>
                </ResponsiveContainer>
            </div>
        </div>
    );
}
