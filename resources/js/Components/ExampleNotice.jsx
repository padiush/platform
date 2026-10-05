import ConfirmModal from '@/Components/ConfirmModal';
import { faFlask } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

/**
 * A slim line under the page header while the example project is open: the
 * data is invented, anything can be changed, and the example can be removed
 * in one step when it has served its purpose.
 */
export default function ExampleNotice() {
    const { t } = useTranslation();
    const [confirming, setConfirming] = useState(false);

    return (
        <div
            data-tour="example-notice"
            className="bg-info/10 text-base-content border-base-300 flex flex-wrap items-center gap-x-3 gap-y-1 border-b px-3 py-1.5 text-xs md:px-6"
        >
            <FontAwesomeIcon icon={faFlask} className="text-info shrink-0" />
            <span className="min-w-0 flex-1">{t('example.notice')}</span>
            <button
                type="button"
                className="btn btn-ghost btn-xs"
                onClick={() => setConfirming(true)}
            >
                {t('example.remove')}
            </button>

            <ConfirmModal
                open={confirming}
                title={t('example.remove_title')}
                message={t('example.remove_message')}
                confirmLabel={t('example.remove')}
                onConfirm={() => {
                    setConfirming(false);
                    router.delete(route('projects.example.destroy'));
                }}
                onClose={() => setConfirming(false)}
            />
        </div>
    );
}
