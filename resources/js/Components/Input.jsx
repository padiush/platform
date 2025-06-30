export default function Input({
    className = '',
    type = 'text',
    disabled = false,
    label = '',
    bottomLabel = '',
    placeholder = '',
    required = false,
    leftAddon = null,
    rightAddon = null,
    error = null,
    selective = false,
    ...props
}) {
    const inputClassName = className
        .split(' ')
        .filter((c) => c.startsWith('input-'))
        .join(' ');

    className = className.replace(inputClassName, '');

    if (type === 'textarea') {
        return (
            <div className={`form-control w-full ${className}`}>
                {label && (
                    <fieldset className="fieldset">
                        <legend className="fieldset-legend">
                            {label}{' '}
                            {required && (
                                <span
                                    className="text-error tooltip tooltip-bottom select-none"
                                    data-tip="Campo requerido"
                                >
                                    *
                                </span>
                            )}{' '}
                            {selective && (
                                <span
                                    className="text-warning tooltip tooltip-bottom select-none"
                                    data-tip="Al menos uno es requerido"
                                >
                                    *
                                </span>
                            )}
                        </legend>
                    </fieldset>
                )}
                <textarea
                    placeholder={placeholder}
                    className={`textarea w-full ${inputClassName}`}
                    disabled={disabled}
                    required={required}
                    {...props}
                />
                {bottomLabel && (
                    <div className="fieldset-label">
                        <legend className="fieldset-legend">
                            <span className="label-text-alt">
                                {bottomLabel}
                            </span>
                        </legend>
                    </div>
                )}
                {error && (
                    <div className="fieldset-label">
                        <legend className="fieldset-legend">
                            <span className="text-error text-sm">{error}</span>
                        </legend>
                    </div>
                )}
            </div>
        );
    }

    if (type === 'checkbox') {
        return (
            <>
                <div className={`justify-left flex flex-row ${className}`}>
                    <label className="fieldset-label">
                        <input
                            type="checkbox"
                            className="checkbox"
                            {...props}
                        />
                        {label}{' '}
                        {required && (
                            <span
                                className="text-error tooltip tooltip-bottom select-none"
                                data-tip="Campo requerido"
                            >
                                *
                            </span>
                        )}{' '}
                        {selective && (
                            <span
                                className="text-warning tooltip tooltip-bottom select-none"
                                data-tip="Al menos uno es requerido"
                            >
                                *
                            </span>
                        )}
                    </label>
                </div>
                {error && (
                    <div className="fieldset-label">
                        <legend className="fieldset-legend">
                            <span className="text-error text-sm">{error}</span>
                        </legend>
                    </div>
                )}
            </>
        );
    }

    if (type === 'file') {
        return (
            <div className={`form-control w-full ${className}`}>
                {label && (
                    <fieldset className="fieldset">
                        <legend className="fieldset-legend">
                            {label}{' '}
                            {required && (
                                <span
                                    className="text-error tooltip tooltip-bottom select-none"
                                    data-tip="Campo requerido"
                                >
                                    *
                                </span>
                            )}{' '}
                            {selective && (
                                <span
                                    className="text-warning tooltip tooltip-bottom select-none"
                                    data-tip="Al menos uno es requerido"
                                >
                                    *
                                </span>
                            )}
                        </legend>
                    </fieldset>
                )}
                <input
                    type={type}
                    className={`file-input w-full ${inputClassName}`}
                    disabled={disabled}
                    required={required}
                    {...props}
                />
                {bottomLabel && (
                    <div className="fieldset-label mt-2">
                        <span className="label-text-alt text-sm">
                            {bottomLabel}
                        </span>
                    </div>
                )}
                {error && (
                    <div className="fieldset-label">
                        <legend className="fieldset-legend">
                            <span className="text-error text-sm">{error}</span>
                        </legend>
                    </div>
                )}
            </div>
        );
    }

    if (type === 'range') {
        return (
            <div className={`form-control w-full ${className}`}>
                {label && (
                    <fieldset className="fieldset">
                        <legend className="fieldset-legend">
                            {label}{' '}
                            {required && (
                                <span
                                    className="text-error tooltip tooltip-bottom select-none"
                                    data-tip="Campo requerido"
                                >
                                    *
                                </span>
                            )}{' '}
                            {selective && (
                                <span
                                    className="text-warning tooltip tooltip-bottom select-none"
                                    data-tip="Al menos uno es requerido"
                                >
                                    *
                                </span>
                            )}
                        </legend>
                    </fieldset>
                )}
                <div className="flex justify-center">
                    {props.value}
                    {props.unit && props.unit}
                </div>
                <input
                    type={type}
                    className={`range w-full ${inputClassName}`}
                    disabled={disabled}
                    required={required}
                    {...props}
                />
                {props.step && (
                    <div className="flex justify-between opacity-40">
                        <span className="text-xs">
                            {props.min}
                            {props.unit && props.unit}
                        </span>
                        <span className="text-xs">
                            {props.max}
                            {props.unit && props.unit}
                        </span>
                    </div>
                )}
                {bottomLabel && (
                    <div className="fieldset-label mt-2">
                        <span className="label-text-alt text-sm">
                            {bottomLabel}
                        </span>
                    </div>
                )}
            </div>
        );
    }

    return (
        <div className={`form-control w-full ${className}`}>
            {label && (
                <fieldset className="fieldset">
                    <legend className="fieldset-legend">
                        {label}{' '}
                        {required && (
                            <span
                                className="text-error tooltip tooltip-bottom select-none"
                                data-tip="Campo requerido"
                            >
                                *
                            </span>
                        )}{' '}
                        {selective && (
                            <span
                                className="text-warning tooltip tooltip-bottom select-none"
                                data-tip="Al menos uno es requerido"
                            >
                                *
                            </span>
                        )}
                    </legend>
                </fieldset>
            )}
            <div className={`${(leftAddon || rightAddon) && 'join'} w-full`}>
                {leftAddon && leftAddon}
                <input
                    type={type}
                    placeholder={placeholder}
                    className={`input text-base-content w-full ${inputClassName} ${
                        (leftAddon || rightAddon) && 'join-item'
                    }`}
                    disabled={disabled}
                    required={required}
                    {...props}
                />
                {rightAddon && rightAddon}
            </div>
            {bottomLabel && (
                <div className="fieldset-label mt-2">
                    <span className="label-text-alt text-sm">
                        {bottomLabel}
                    </span>
                </div>
            )}
            {error && (
                <div className="fieldset-label">
                    <legend className="fieldset-legend">
                        <span className="text-error text-sm">{error}</span>
                    </legend>
                </div>
            )}
        </div>
    );
}
