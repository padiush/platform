import { useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';

/**
 * Lightweight confirm/cancel dialog for reversible actions (e.g. revoking a
 * member's access) — a styled, localized replacement for window.confirm().
 * For irreversible data loss, use DeletionModal (type-to-confirm) instead.
 *
 * Like FormModal, every dismissal routes through `onClose` (backdrop + Escape)
 * rather than the native close event, which some webviews never emit.
 */
export default function ConfirmModal({
    open,
    title,
    message,
    confirmLabel,
    onConfirm,
    onClose,
    confirmClassName = 'btn-error',
}) {
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
                e.preventDefault();
                onClose();
            }}
        >
            <div className="modal-box">
                <h3 className="text-lg font-bold">{title}</h3>
                <p className="py-4">{message}</p>
                <div className="flex justify-end gap-2">
                    <button
                        type="button"
                        className="btn btn-ghost"
                        onClick={onClose}
                    >
                        {t('actions.cancel')}
                    </button>
                    <button
                        type="button"
                        className={`btn ${confirmClassName}`}
                        onClick={onConfirm}
                    >
                        {confirmLabel}
                    </button>
                </div>
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
