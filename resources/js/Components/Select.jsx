export default function Select({
    className = "",
    disabled = false,
    label = "",
    bottomLabel = "",
    placeholder = "",
    children,
    required = false,
    selective = false,
    error = null,
    ...props
}) {
    const selectClassName = className
        .split(" ")
        .filter((c) => c.startsWith("select-"))
        .join(" ");

    className = className.replace(selectClassName, "");

    return (
        <fieldset className={`fieldset w-full ${className}`}>
            {label && (
                <legend className="fieldset-legend">
                    {label}{" "}
                    {required && (
                        <span
                            className="text-error tooltip tooltip-bottom"
                            data-tip="Campo requerido"
                        >
                            *
                        </span>
                    )}{" "}
                    {selective && (
                        <span
                            className="text-warning tooltip tooltip-bottom"
                            data-tip="Al menos uno es requerido"
                        >
                            *
                        </span>
                    )}
                </legend>
            )}
            <select
                placeholder={placeholder}
                className={`select select-bordered w-full ${selectClassName}`}
                disabled={disabled}
                required={required}
                {...props}
            >
                {children}
            </select>
            {bottomLabel && (
                <span className="fieldset-label">{bottomLabel}</span>
            )}
            {error && (
                <span className="fieldset-label">
                    <span className="text-error">{error}</span>
                </span>
            )}
        </fieldset>
    );
}
