/** A share of a whole, as a bar; the figure beside it says the number. */
export default function UsageBar({ value, max, className = '' }) {
    const share = max > 0 ? Math.min(100, (value / max) * 100) : 0;

    return (
        <div
            className={`bg-base-300 h-2 overflow-hidden rounded-full ${className}`}
            aria-hidden="true"
        >
            <div
                className="bg-primary h-full rounded-full"
                style={{ width: `${share}%` }}
            />
        </div>
    );
}
