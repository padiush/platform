import { faXmark } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';

/**
 * Controlled native-<dialog> shell for create/edit forms. `open` drives
 * showModal()/close() (which gives focus-trapping + Escape for free); the
 * dialog's native close event (Escape, backdrop) reports back through
 * `onClose`. The form itself is passed as children.
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
        <dialog ref={ref} className="modal" onClose={onClose}>
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
            <form method="dialog" className="modal-backdrop">
                <button>{t('actions.close')}</button>
            </form>
        </dialog>
    );
}
