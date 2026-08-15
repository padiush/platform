import {
    faCircleCheck,
    faCircleExclamation,
    faCircleInfo,
    faCircleXmark,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

const VARIANTS = {
    info: { className: 'alert-info', icon: faCircleInfo },
    warning: { className: 'alert-warning', icon: faCircleExclamation },
    error: { className: 'alert-error', icon: faCircleXmark },
    success: { className: 'alert-success', icon: faCircleCheck },
};

/**
 * Soft (tinted) alerts: status color as a wash with readable text, instead
 * of the saturated full-fill default.
 */
export default function Alert({ message, type = 'success' }) {
    const variant = VARIANTS[type] ?? VARIANTS.success;

    return (
        <div role="alert" className={`alert alert-soft ${variant.className}`}>
            <FontAwesomeIcon icon={variant.icon} />
            <span>{message}</span>
        </div>
    );
}
