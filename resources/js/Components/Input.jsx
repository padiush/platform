import { useId } from 'react';
import { useTranslation } from 'react-i18next';

function RequiredMarks({ required, selective }) {
    const { t } = useTranslation();

    return (
        <>
            {required && (
                <span
                    className="text-error tooltip tooltip-bottom select-none"
                    data-tip={t('designer.required')}
                >
                    *
                </span>
            )}{' '}
            {selective && (
                <span
                    className="text-warning tooltip tooltip-bottom select-none"
                    data-tip={t('validation.at_least_one')}
                >
                    *
                </span>
            )}
        </>
    );
}

function FieldLabel({ id, label, required, selective }) {
    if (!label) return null;

    return (
        <div className="fieldset">
            <label htmlFor={id} className="fieldset-legend">
                {label}{' '}
                <RequiredMarks required={required} selective={selective} />
            </label>
        </div>
    );
}

function FieldError({ id, error }) {
    if (!error) return null;

    return (
        <div className="fieldset-label">
            <span className="fieldset-legend" id={`${id}-error`} role="alert">
                <span className="text-error text-sm">{error}</span>
            </span>
        </div>
    );
}

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
    const generatedId = useId();
    const id = props.id ?? generatedId;

    const describedBy =
        [error && `${id}-error`, bottomLabel && `${id}-hint`]
            .filter(Boolean)
            .join(' ') || undefined;

    const a11yProps = {
        id,
        'aria-invalid': error ? true : undefined,
        'aria-describedby': describedBy,
    };

    const inputClassName = className
        .split(' ')
        .filter((c) => c.startsWith('input-'))
        .join(' ');

    className = className.replace(inputClassName, '');

    if (type === 'textarea') {
        return (
            <div className={`form-control w-full ${className}`}>
                <FieldLabel
                    id={id}
                    label={label}
                    required={required}
                    selective={selective}
                />
                <textarea
                    placeholder={placeholder}
                    className={`textarea w-full ${inputClassName}`}
                    disabled={disabled}
                    required={required}
                    {...a11yProps}
                    {...props}
                />
                {bottomLabel && (
                    <div className="fieldset-label">
                        <span className="fieldset-legend" id={`${id}-hint`}>
                            <span className="label-text-alt">
                                {bottomLabel}
                            </span>
                        </span>
                    </div>
                )}
                <FieldError id={id} error={error} />
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
                            aria-invalid={error ? true : undefined}
                            {...props}
                        />
                        {label}{' '}
                        <RequiredMarks
                            required={required}
                            selective={selective}
                        />
                    </label>
                </div>
                <FieldError id={id} error={error} />
            </>
        );
    }

    if (type === 'file') {
        return (
            <div className={`form-control w-full ${className}`}>
                <FieldLabel
                    id={id}
                    label={label}
                    required={required}
                    selective={selective}
                />
                <input
                    type={type}
                    className={`file-input w-full ${inputClassName}`}
                    disabled={disabled}
                    required={required}
                    {...a11yProps}
                    {...props}
                />
                {bottomLabel && (
                    <div className="fieldset-label mt-2">
                        <span
                            className="label-text-alt text-sm"
                            id={`${id}-hint`}
                        >
                            {bottomLabel}
                        </span>
                    </div>
                )}
                <FieldError id={id} error={error} />
            </div>
        );
    }

    if (type === 'range') {
        return (
            <div className={`form-control w-full ${className}`}>
                <FieldLabel
                    id={id}
                    label={label}
                    required={required}
                    selective={selective}
                />
                <div className="flex justify-center">
                    {props.value}
                    {props.unit && props.unit}
                </div>
                <input
                    type={type}
                    className={`range w-full ${inputClassName}`}
                    disabled={disabled}
                    required={required}
                    {...a11yProps}
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
                        <span
                            className="label-text-alt text-sm"
                            id={`${id}-hint`}
                        >
                            {bottomLabel}
                        </span>
                    </div>
                )}
            </div>
        );
    }

    return (
        <div className={`form-control w-full ${className}`}>
            <FieldLabel
                id={id}
                label={label}
                required={required}
                selective={selective}
            />
            <div className={`${leftAddon || rightAddon ? 'join' : ''} w-full`}>
                {leftAddon && leftAddon}
                <input
                    type={type}
                    placeholder={placeholder}
                    className={`input text-base-content w-full ${inputClassName} ${
                        leftAddon || rightAddon ? 'join-item' : ''
                    }`}
                    disabled={disabled}
                    required={required}
                    {...a11yProps}
                    {...props}
                />
                {rightAddon && rightAddon}
            </div>
            {bottomLabel && (
                <div className="fieldset-label mt-2">
                    <span className="label-text-alt text-sm" id={`${id}-hint`}>
                        {bottomLabel}
                    </span>
                </div>
            )}
            <FieldError id={id} error={error} />
        </div>
    );
}
