import { CollectionModal } from '@/Pages/Catalog/Partials/FieldRecordModals';
import FieldRecordTable from '@/Pages/Catalog/Partials/FieldRecordTable';
import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

/**
 * The collections determined as this taxon.
 *
 * A narrower view than the project list, which is where unidentified material
 * lives — so this section links there rather than pretending to be the whole
 * picture. Recording here is the shortcut for when you already know what it is.
 */
export default function SpeciesFieldRecords({
    project,
    species,
    fieldRecords = [],
    canEdit = false,
}) {
    const { t } = useTranslation();
    const [collecting, setCollecting] = useState(false);

    const vouchered = fieldRecords.filter((s) => s.is_vouchered).length;

    return (
        <div className="space-y-3">
            {fieldRecords.length > 0 && (
                <p className="text-base-content/70 text-sm">
                    {t('catalogs.fieldRecords.coverage', {
                        vouchered,
                        total: fieldRecords.length,
                    })}
                </p>
            )}

            <FieldRecordTable
                fieldRecords={fieldRecords}
                // Read-only here on purpose: identifying and depositing act on
                // a collection rather than on a taxon, so they live on the
                // project list. Recording is the one exception, below, because
                // knowing the taxon is the reason you are on this page.
                canEdit={false}
                showDetermination={false}
                emptyTitle={t('catalogs.fieldRecords.none_for_taxon_title')}
                emptyHint={t('catalogs.fieldRecords.none_for_taxon_hint')}
            />

            <div className="flex flex-wrap items-center gap-2">
                {canEdit && (
                    <button
                        type="button"
                        className="btn btn-outline btn-sm"
                        onClick={() => setCollecting(true)}
                    >
                        {t('catalogs.fieldRecords.add_for_taxon')}
                    </button>
                )}
                <Link
                    href={route('catalogs.fieldRecords.index', {
                        project: project.id,
                    })}
                    className="btn btn-ghost btn-sm"
                >
                    {t('catalogs.fieldRecords.see_all')}
                </Link>
            </div>

            <CollectionModal
                open={collecting}
                onClose={() => setCollecting(false)}
                project={project}
                species={species}
            />
        </div>
    );
}
