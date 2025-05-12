import Alert from '@/Components/Alert';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

export function useFlashMessage() {
    const { flash } = usePage().props;
    const [flashShown, setFlashShown] = useState(false);
    const [toastOpacity, setToastOpacity] = useState(0);

    useEffect(() => {
        if (flash.message) {
            setToastOpacity(1);
            setFlashShown(true);
        }
    }, [flash]);

    useEffect(() => {
        if (flashShown) {
            setTimeout(() => {
                setFlashShown(false);
            }, 5000);

            setTimeout(() => {
                setToastOpacity(0);
            }, 3000);
        }
    });

    const FlashAlert = () => {
        const { t } = useTranslation();

        return (
            <div className="toast z-1">
                <Alert type={flash.type} message={t(flash.message)} />
            </div>
        );
    };

    return {
        FlashAlert,
        flashShown,
    };
}
