import { faArrowDown } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useTranslation } from 'react-i18next';

/**
 * The reconciliation step made concrete: what the interviewee said on top, the
 * accepted taxon below, and the provenance of the change alongside. The point
 * of the visual is that the reported name survives the reconciliation.
 */
export default function TaxonomyCard() {
    const { t } = useTranslation();

    return (
        <div
            className="bg-base-100 border-base-300 rounded-box mx-auto w-full max-w-md space-y-4 border p-5 shadow-xl"
            aria-hidden="true"
        >
            <div>
                <p className="text-base-content/50 text-[0.6875rem] font-semibold tracking-[0.16em] uppercase">
                    {t('public.taxonomy_recorded')}
                </p>
                <p className="mt-1 text-lg">
                    “{t('public.taxonomy_recorded_value')}”
                </p>
            </div>

            <div className="text-primary flex justify-center">
                <FontAwesomeIcon icon={faArrowDown} />
            </div>

            <div className="border-primary/40 bg-primary/5 rounded-box border-l-4 px-4 py-3">
                <p className="text-base-content/50 text-[0.6875rem] font-semibold tracking-[0.16em] uppercase">
                    {t('public.taxonomy_accepted')}
                </p>
                <p className="mt-1 text-lg">
                    <em>Cecropia obtusifolia</em>{' '}
                    <span className="text-base-content/60">Bertol.</span>
                </p>
                <p className="text-base-content/60 text-sm">Urticaceae</p>
            </div>

            <dl className="space-y-2 text-sm">
                <div className="flex flex-wrap justify-between gap-2">
                    <dt className="text-base-content/60">
                        {t('public.taxonomy_source')}
                    </dt>
                    <dd className="font-medium">
                        {t('public.taxonomy_source_value')}
                    </dd>
                </div>
                <div className="flex flex-wrap justify-between gap-2">
                    <dt className="text-base-content/60">
                        {t('public.taxonomy_range')}
                    </dt>
                    <dd className="text-right font-medium">
                        {t('public.taxonomy_range_value')}
                    </dd>
                </div>
            </dl>
        </div>
    );
}
