import Alert from '@/Components/Alert';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

export function useFlashMessage() {
    const { flash } = usePage().props;
    const [flashShown, setFlashShown] = useState(false);

    useEffect(() => {
        if (!flash.message) {
            return;
        }

        setFlashShown(true);

        const timeout = setTimeout(() => setFlashShown(false), 5000);

        return () => clearTimeout(timeout);
    }, [flash]);

    const FlashAlert = () => {
        const { t } = useTranslation();

        return (
            <div className="toast z-1">
                <Alert type={flash.message_type} message={t(flash.message)} />
            </div>
        );
    };

    return {
        FlashAlert,
        flashShown,
    };
}
