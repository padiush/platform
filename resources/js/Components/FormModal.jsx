import { faXmark } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';

/**
 * Controlled native-<dialog> shell for create/edit forms. `open` drives
 * showModal()/close() (which gives focus-trapping for free). Every dismissal —
 * the close button, the backdrop, and Escape — routes through `onClose` so the
 * caller can strip its deep-link query param; we don't rely on the dialog's
 * native close event, which some webviews never emit. The form is the children.
 */
export default function FormModal({ open, onClose, title, children }) {
    const { t } = useTranslation();
    const ref = useRef(null);

    useEffect(() => {
        const dialog = ref.current;
        if (!dialog) return;

        if (open && !dialog.open) {
            dialog.showModal();
        } else if (!open && dialog.open) {
            dialog.close();
        }
    }, [open]);

    return (
        <dialog
            ref={ref}
            className="modal"
            onCancel={(e) => {
                // Escape: close through onClose (strips the param) rather than
                // letting the dialog quietly close itself.
                e.preventDefault();
                onClose();
            }}
        >
            <div className="modal-box">
                <div className="mb-4 flex items-center justify-between gap-4">
                    <h3 className="text-lg font-bold">{title}</h3>
                    <button
                        type="button"
                        className="btn btn-ghost btn-sm btn-circle"
                        aria-label={t('actions.close')}
                        onClick={onClose}
                    >
                        <FontAwesomeIcon icon={faXmark} />
                    </button>
                </div>
                {open && children}
            </div>
            <button
                type="button"
                className="modal-backdrop"
                aria-label={t('actions.close')}
                onClick={onClose}
            />
        </dialog>
    );
}
