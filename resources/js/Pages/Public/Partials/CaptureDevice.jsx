import { useTranslation } from 'react-i18next';

/**
 * A phone-shaped rendering of the companion app's capture screen. Drawn in
 * markup rather than shipped as an image so it stays crisp, follows the theme
 * and localizes with everything else.
 */
export default function CaptureDevice() {
    const { t } = useTranslation();

    return (
        <div
            className="border-base-300 bg-base-100 mx-auto w-full max-w-[17rem] rounded-[2rem] border-8 shadow-xl"
            aria-hidden="true"
        >
            <div className="bg-primary text-primary-content flex items-center justify-between rounded-t-[1.5rem] px-4 py-3">
                <span className="text-sm font-semibold">
                    {t('public.capture_device_form')}
                </span>
                <span className="bg-primary-content/20 rounded-full px-2 py-0.5 text-[0.625rem]">
                    2 / 5
                </span>
            </div>

            <div className="space-y-4 p-4">
                <Field
                    label={t('public.capture_device_plant')}
                    value={t('public.capture_device_plant_value')}
                />
                <div>
                    <p className="text-base-content/60 mb-1.5 text-[0.6875rem]">
                        {t('public.capture_device_category')}
                    </p>
                    <div className="flex flex-wrap gap-1.5">
                        <span className="bg-primary text-primary-content rounded-full px-2.5 py-1 text-[0.6875rem]">
                            {t('public.capture_device_category_value')}
                        </span>
                        <span className="bg-base-200 text-base-content/60 rounded-full px-2.5 py-1 text-[0.6875rem]">
                            {t('public.preview_cat_food')}
                        </span>
                        <span className="bg-base-200 text-base-content/60 rounded-full px-2.5 py-1 text-[0.6875rem]">
                            {t('public.preview_cat_ritual')}
                        </span>
                    </div>
                </div>

                <div className="border-base-300 flex items-center gap-2 border-t pt-3">
                    <span className="bg-success h-2 w-2 shrink-0 rounded-full" />
                    <span className="text-base-content/70 text-[0.6875rem]">
                        {t('public.capture_device_status')}
                    </span>
                </div>
                <div className="bg-warning/15 text-base-content/80 rounded-lg px-3 py-2 text-[0.6875rem]">
                    {t('public.capture_device_pending')}
                </div>
            </div>
        </div>
    );
}

function Field({ label, value }) {
    return (
        <div>
            <p className="text-base-content/60 mb-1.5 text-[0.6875rem]">
                {label}
            </p>
            <div className="border-base-300 bg-base-200/60 rounded-lg border px-3 py-2 text-sm">
                {value}
            </div>
        </div>
    );
}
