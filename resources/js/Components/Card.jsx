/**
 * The app's panel surface: a bordered card on the tinted page background.
 * Elevation is reserved for things that float (modals, sticky bars) — panels
 * sit in the page with a border and a whisper of shadow.
 */
export default function Card({
    title = '',
    kicker = '',
    description = '',
    actions = null,
    children,
    className = '',
    sameHeight = false,
}) {
    const hasHeader = title || kicker || description || actions;

    return (
        <div
            className={`card bg-base-100 border-base-300 text-base-content rounded-box border shadow-sm ${
                sameHeight ? '' : 'self-start'
            } ${className}`}
        >
            <div
                className={`card-body gap-3 ${
                    sameHeight ? 'place-content-center' : ''
                }`}
            >
                {hasHeader && (
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="min-w-0">
                            {kicker && (
                                <p className="text-base-content/50 text-xs font-semibold tracking-[0.18em] uppercase">
                                    {kicker}
                                </p>
                            )}
                            {title && <h2 className="card-title">{title}</h2>}
                            {description && (
                                <p className="text-base-content/60 text-sm font-normal">
                                    {description}
                                </p>
                            )}
                        </div>
                        {actions && (
                            <div className="flex flex-wrap items-center gap-2">
                                {actions}
                            </div>
                        )}
                    </div>
                )}
                {children}
            </div>
        </div>
    );
}
