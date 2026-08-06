import FieldLabel from '@/Components/FieldLabel';
import { useId } from 'react';

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
            <FieldLabel
                id={id}
                label={label}
                required={required}
                selective={selective}
            />
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
