import EmptyState from '@/Components/EmptyState';
import { useTranslation } from 'react-i18next';

/**
 * How a collection is identified in print. An unvouchered one says so rather
 * than showing an empty cell — the absence is a data-quality fact, not a blank
 * to overlook.
 */
function AccessionCell({ fieldRecord }) {
    const { t } = useTranslation();

    // Nothing was taken, so there is nothing to accession. Saying "unvouchered"
    // here would read as a gap rather than a different kind of record.
    // Explicitly false, not merely absent: a record is a collection unless it
    // says otherwise, which is the default the model carries too.
    if (fieldRecord.was_collected === false) {
        return (
            <span className="badge badge-outline badge-sm">
                {t('catalogs.fieldRecords.basis_human_observation')}
            </span>
        );
    }

    if (fieldRecord.is_vouchered) {
        return (
            <span className="font-mono">{fieldRecord.accession_number}</span>
        );
    }

    return (
        <span className="badge badge-ghost badge-sm">
            {t('catalogs.fieldRecords.unvouchered')}
        </span>
    );
}

/**
 * The current opinion about what this is. Two different absences, kept
 * distinct: nobody has looked yet, or someone looked and could not name it.
 */
function DeterminationCell({ fieldRecord }) {
    const { t } = useTranslation();

    if (!fieldRecord.species) {
        return (
            <span className="badge badge-outline badge-sm">
                {fieldRecord.determiner
                    ? t('catalogs.fieldRecords.indet')
                    : t('catalogs.fieldRecords.undetermined')}
            </span>
        );
    }

    const qualifier = fieldRecord.qualifier
        ? `${t(`catalogs.fieldRecords.qualifier_${fieldRecord.qualifier}`)} `
        : '';

    return (
        <span className="italic">
            {qualifier}
            {fieldRecord.species.genus} {fieldRecord.species.name}
        </span>
    );
}

/**
 * One row per physical collection. `columns` lets the species page drop the
 * determination column, which would repeat the taxon you are already looking at.
 */
export default function FieldRecordTable({
    fieldRecords,
    canEdit = false,
    showDetermination = true,
    onEdit = () => {},
    onDetermine = () => {},
    onDeposit = () => {},
    onMedia = () => {},
    onDelete = () => {},
    emptyTitle,
    emptyHint,
}) {
    const { t } = useTranslation();

    if (fieldRecords.length === 0) {
        return <EmptyState title={emptyTitle} hint={emptyHint} />;
    }

    return (
        <div className="overflow-x-auto">
            <table className="table-sm table">
                <thead>
                    <tr>
                        <th>{t('catalogs.fieldRecords.accession_number')}</th>
                        <th>{t('catalogs.fieldRecords.collection_number')}</th>
                        <th>{t('catalogs.fieldRecords.collector')}</th>
                        <th>{t('catalogs.fieldRecords.collected_on')}</th>
                        {showDetermination && (
                            <th>{t('catalogs.fieldRecords.determination')}</th>
                        )}
                        <th>{t('catalogs.fieldRecords.repository')}</th>
                        <th>{t('catalogs.fieldRecords.permit')}</th>
                        {canEdit && <th />}
                    </tr>
                </thead>
                <tbody>
                    {fieldRecords.map((fieldRecord) => (
                        <tr key={fieldRecord.id}>
                            <td>
                                <AccessionCell fieldRecord={fieldRecord} />
                            </td>
                            <td>{fieldRecord.collection_number ?? '—'}</td>
                            <td>{fieldRecord.collector ?? '—'}</td>
                            <td>{fieldRecord.collected_on ?? '—'}</td>
                            {showDetermination && (
                                <td>
                                    <DeterminationCell
                                        fieldRecord={fieldRecord}
                                    />
                                </td>
                            )}
                            <td>{fieldRecord.repository ?? '—'}</td>
                            <td>
                                {fieldRecord.permit ??
                                    (fieldRecord.permit_exemption ? (
                                        <span className="badge badge-ghost badge-sm">
                                            {t(
                                                `catalogs.fieldRecords.exemption_${fieldRecord.permit_exemption}`,
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
                                        onClick={() => onDetermine(fieldRecord)}
                                    >
                                        {t('catalogs.fieldRecords.identify')}
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-ghost btn-xs"
                                        onClick={() => onDeposit(fieldRecord)}
                                    >
                                        {t('catalogs.fieldRecords.deposit')}
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-ghost btn-xs"
                                        onClick={() => onMedia(fieldRecord)}
                                    >
                                        {t('catalogs.fieldRecords.media.title')}
                                        {fieldRecord.media?.length
                                            ? ` (${fieldRecord.media.length})`
                                            : ''}
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-ghost btn-xs"
                                        onClick={() => onEdit(fieldRecord)}
                                    >
                                        {t('catalogs.fieldRecords.edit')}
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-ghost btn-xs text-error"
                                        onClick={() => onDelete(fieldRecord)}
                                    >
                                        {t('catalogs.fieldRecords.delete')}
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
