import {
    faCircleCheck,
    faCircleExclamation,
    faCircleInfo,
    faCircleXmark,
} from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

export default function Alert({ message, type = 'success' }) {
    if (type === 'info') {
        return (
            <div role="alert" className="alert alert-info">
                <FontAwesomeIcon icon={faCircleInfo} />
                <span>{message}</span>
            </div>
        );
    }

    if (type === 'warning') {
        return (
            <div role="alert" className="alert alert-warning">
                <FontAwesomeIcon icon={faCircleExclamation} />
                <span>{message}</span>
            </div>
        );
    }

    if (type === 'error') {
        return (
            <div role="alert" className="alert alert-error">
                <FontAwesomeIcon icon={faCircleXmark} />
                <span>{message}</span>
            </div>
        );
    }

    return (
        <div role="alert" className="alert alert-success">
            <FontAwesomeIcon icon={faCircleCheck} />
            <span>{message}</span>
        </div>
    );
}
