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

    const selectClassName = className
        .split(' ')
        .filter((c) => c.startsWith('select-'))
        .join(' ');

    className = className.replace(selectClassName, '');

    return (
        <fieldset className={`fieldset w-full ${className}`}>
            {label && (
                <legend className="fieldset-legend">
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
