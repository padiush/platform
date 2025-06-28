import Input from '@/Components/Input';
import { requestHeaders } from '@/utils/requestHeaders';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function DeletionModal({
    modalRef,
    name,
    url,
    data = null,
    useRouter = true,
    onDeleted = () => {},
}) {
    const { t } = useTranslation();

    const [confirmation, setConfirmation] = useState('');

    const handleDelete = (e) => {
        if (confirmation === t('deletion_modal.has_to_write')) {
            e.preventDefault();

            if (useRouter) {
                if (data) {
                    router.delete(url, { data });
                } else {
                    router.delete(url);
                }
            } else {
                fetch(url, {
                    method: 'DELETE',
                    headers: requestHeaders(),
                })
                    .then((response) => {
                        if (response.ok) {
                            modalRef.current.close();
                        }
                    })
                    .catch((error) => {
                        console.error('Error deleting item:', error);
                    });
            }

            setConfirmation('');

            modalRef.current.close();

            onDeleted();
        }
    };

    return (
        <dialog className="modal" ref={modalRef}>
            <div className="modal-box">
                <h3 className="text-lg font-bold">
                    {t('deletion_modal.title')}
                </h3>
                <p className="prose">
                    {t('deletion_modal.warning', {
                        name: name,
                    })}
                </p>

                <div className="divider"></div>

                <p className="prose">
                    {t('deletion_modal.confirmation', {
                        hasToWrite: t('deletion_modal.has_to_write'),
                    })}
                </p>
                <div>
                    <Input
                        type="text"
                        label={t('deletion_modal.has_to_write')}
                        value={confirmation}
                        onChange={(e) => setConfirmation(e.target.value)}
                    />

                    <div className="mt-4">
                        <button
                            className="btn btn-error w-full"
                            onClick={handleDelete}
                            disabled={
                                confirmation !==
                                t('deletion_modal.has_to_write')
                            }
                        >
                            {t('deletion_modal.delete')}
                        </button>
                    </div>
                </div>
            </div>
            <form method="dialog" className="modal-backdrop">
                <button>{t('actions.close')}</button>
            </form>
        </dialog>
    );
}
