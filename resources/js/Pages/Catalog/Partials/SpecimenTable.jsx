import EmptyState from '@/Components/EmptyState';
import { useTranslation } from 'react-i18next';

/**
 * How a collection is identified in print. An unvouchered one says so rather
 * than showing an empty cell — the absence is a data-quality fact, not a blank
 * to overlook.
 */
function AccessionCell({ specimen }) {
    const { t } = useTranslation();

    if (specimen.is_vouchered) {
        return <span className="font-mono">{specimen.accession_number}</span>;
    }

    return (
        <span className="badge badge-ghost badge-sm">
            {t('catalogs.specimens.unvouchered')}
        </span>
    );
}

/**
 * The current opinion about what this is. Two different absences, kept
 * distinct: nobody has looked yet, or someone looked and could not name it.
 */
function DeterminationCell({ specimen }) {
    const { t } = useTranslation();

    if (!specimen.species) {
        return (
            <span className="badge badge-outline badge-sm">
                {specimen.determiner
                    ? t('catalogs.specimens.indet')
                    : t('catalogs.specimens.undetermined')}
            </span>
        );
    }

    const qualifier = specimen.qualifier
        ? `${t(`catalogs.specimens.qualifier_${specimen.qualifier}`)} `
        : '';

    return (
        <span className="italic">
            {qualifier}
            {specimen.species.genus} {specimen.species.name}
        </span>
    );
}

/**
 * One row per physical collection. `columns` lets the species page drop the
 * determination column, which would repeat the taxon you are already looking at.
 */
export default function SpecimenTable({
    specimens,
    canEdit = false,
    showDetermination = true,
    onEdit = () => {},
    onDetermine = () => {},
    onDeposit = () => {},
    onDelete = () => {},
    emptyTitle,
    emptyHint,
}) {
    const { t } = useTranslation();

    if (specimens.length === 0) {
        return <EmptyState title={emptyTitle} hint={emptyHint} />;
    }

    return (
        <div className="overflow-x-auto">
            <table className="table-sm table">
                <thead>
                    <tr>
                        <th>{t('catalogs.specimens.accession_number')}</th>
                        <th>{t('catalogs.specimens.collection_number')}</th>
                        <th>{t('catalogs.specimens.collector')}</th>
                        <th>{t('catalogs.specimens.collected_on')}</th>
                        {showDetermination && (
                            <th>{t('catalogs.specimens.determination')}</th>
                        )}
                        <th>{t('catalogs.specimens.repository')}</th>
                        <th>{t('catalogs.specimens.permit')}</th>
                        {canEdit && <th />}
                    </tr>
                </thead>
                <tbody>
                    {specimens.map((specimen) => (
                        <tr key={specimen.id}>
                            <td>
                                <AccessionCell specimen={specimen} />
                            </td>
                            <td>{specimen.collection_number ?? '—'}</td>
                            <td>{specimen.collector ?? '—'}</td>
                            <td>{specimen.collected_on ?? '—'}</td>
                            {showDetermination && (
                                <td>
                                    <DeterminationCell specimen={specimen} />
                                </td>
                            )}
                            <td>{specimen.repository ?? '—'}</td>
                            <td>
                                {specimen.permit ??
                                    (specimen.permit_exemption ? (
                                        <span className="badge badge-ghost badge-sm">
                                            {t(
                                                `catalogs.specimens.exemption_${specimen.permit_exemption}`,
                                            )}
                                        </span>
                                    ) : (
                                        '—'
                                    ))}
                            </td>
                            {canEdit && (
                                <td className="text-right whitespace-nowrap">
                                    <button
                                        type="button"
                                        className="btn btn-ghost btn-xs"
                                        onClick={() => onDetermine(specimen)}
                                    >
                                        {t('catalogs.specimens.identify')}
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-ghost btn-xs"
                                        onClick={() => onDeposit(specimen)}
                                    >
                                        {t('catalogs.specimens.deposit')}
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-ghost btn-xs"
                                        onClick={() => onEdit(specimen)}
                                    >
                                        {t('catalogs.specimens.edit')}
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-ghost btn-xs text-error"
                                        onClick={() => onDelete(specimen)}
                                    >
                                        {t('catalogs.specimens.delete')}
                                    </button>
                                </td>
                            )}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
