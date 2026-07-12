import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

/**
 * Icon-only button with a mandatory accessible label. Disabled state is the
 * boundary signal — icon buttons here never silently no-op.
 */
export default function IconButton({
    icon,
    label,
    onClick,
    disabled = false,
    className = '',
}) {
    return (
        <button
            type="button"
            className={`btn btn-ghost btn-sm ${className}`}
            aria-label={label}
            title={label}
            disabled={disabled}
            onClick={onClick}
        >
            <FontAwesomeIcon icon={icon} />
        </button>
    );
}
