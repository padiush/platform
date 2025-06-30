export default function Card({
    title = '',
    children,
    className = '',
    sameHeight = false,
}) {
    return (
        <div
            className={`card bg-base-200 text-base-content shadow-xl ${sameHeight ? '' : 'self-start'} ${className}`}
        >
            <div
                className={`card-body ${
                    sameHeight ? 'place-content-center' : ''
                }`}
            >
                {title && <h2 className="card-title">{title}</h2>}
                {children}
            </div>
        </div>
    );
}
