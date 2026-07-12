/**
 * Compact stat tile: caption label over a big tabular number, with an
 * optional tone accent. Replaces the tall dashed stat stacks on hub pages.
 */
const TONES = {
    default: 'text-base-content',
    primary: 'text-primary',
    warning: 'text-warning',
};

export default function MetricCard({ label, value, tone = 'default' }) {
    return (
        <div className="border-base-300 bg-base-100 rounded-box border px-4 py-3">
            <p className="text-base-content/55 text-xs font-semibold tracking-[0.14em] uppercase">
                {label}
            </p>
            <p
                className={`mt-1 text-2xl font-bold tabular-nums ${TONES[tone] ?? TONES.default}`}
            >
                {value}
            </p>
        </div>
    );
}
