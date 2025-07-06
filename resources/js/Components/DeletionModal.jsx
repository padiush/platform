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

    const handleDelete = async (e) => {
        if (confirmation !== t('deletion_modal.has_to_write')) return;

        e.preventDefault();

        try {
            if (useRouter) {
                await new Promise((resolve) => {
                    if (data) {
                        router.delete(url, { data, onFinish: resolve });
                    } else {
                        router.delete(url, { onFinish: resolve });
                    }
                });
            } else {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: requestHeaders(),
                });

                if (!response.ok) {
                    throw new Error('Error deleting item');
                }
            }

            setConfirmation('');
            modalRef.current.close();
            onDeleted();
        } catch (error) {
            console.error('Error deleting item:', error);
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
