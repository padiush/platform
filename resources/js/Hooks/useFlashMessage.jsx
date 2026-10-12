import Alert from '@/Components/Alert';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

export function useFlashMessage() {
    const { flash } = usePage().props;
    // Each visit brings its own flash object, so a message is shown until its
    // own timeout has passed, and a new one is shown afresh.
    const [expired, setExpired] = useState(null);
    const flashShown = Boolean(flash.message) && expired !== flash;

    useEffect(() => {
        if (!flash.message) {
            return;
        }

        const timeout = setTimeout(() => setExpired(flash), 5000);

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
