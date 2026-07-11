import { useId } from 'react';
import { useTranslation } from 'react-i18next';

export default function Select({
    className = '',
    disabled = false,
    label = '',
    bottomLabel = '',
    placeholder = '',
    children,
    required = false,
    selective = false,
    error = null,
    ...props
}) {
    const { t } = useTranslation();
    const generatedId = useId();
    const id = props.id ?? generatedId;

    const describedBy =
        [error && `${id}-error`, bottomLabel && `${id}-hint`]
            .filter(Boolean)
            .join(' ') || undefined;

    const selectClassName = className
        .split(' ')
        .filter((c) => c.startsWith('select-'))
        .join(' ');

    className = className.replace(selectClassName, '');

    return (
        <div className={`fieldset w-full ${className}`}>
            {label && (
                <label htmlFor={id} className="fieldset-legend">
                    {label}{' '}
                    {required && (
                        <span
                            className="text-error tooltip tooltip-bottom"
                            data-tip={t('designer.required')}
                        >
                            *
                        </span>
                    )}{' '}
                    {selective && (
                        <span
                            className="text-warning tooltip tooltip-bottom"
                            data-tip={t('validation.at_least_one')}
                        >
                            *
                        </span>
                    )}
                </label>
            )}
            <select
                placeholder={placeholder}
                className={`select select-bordered w-full ${selectClassName}`}
                disabled={disabled}
                required={required}
                id={id}
                aria-invalid={error ? true : undefined}
                aria-describedby={describedBy}
                {...props}
            >
                {children}
            </select>
            {bottomLabel && (
                <span className="fieldset-label" id={`${id}-hint`}>
                    {bottomLabel}
                </span>
            )}
            {error && (
                <span
                    className="fieldset-label"
                    id={`${id}-error`}
                    role="alert"
                >
                    <span className="text-error">{error}</span>
                </span>
            )}
        </div>
    );
}
