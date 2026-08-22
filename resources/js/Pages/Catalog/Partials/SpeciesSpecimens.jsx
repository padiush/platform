import { CollectionModal } from '@/Pages/Catalog/Partials/SpecimenModals';
import SpecimenTable from '@/Pages/Catalog/Partials/SpecimenTable';
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
export default function SpeciesSpecimens({
    project,
    species,
    specimens = [],
    canEdit = false,
}) {
    const { t } = useTranslation();
    const [collecting, setCollecting] = useState(false);

    const vouchered = specimens.filter((s) => s.is_vouchered).length;

    return (
        <div className="space-y-3">
            {specimens.length > 0 && (
                <p className="text-base-content/70 text-sm">
                    {t('catalogs.specimens.coverage', {
                        vouchered,
                        total: specimens.length,
                    })}
                </p>
            )}

            <SpecimenTable
                specimens={specimens}
                canEdit={false}
                showDetermination={false}
                emptyTitle={t('catalogs.specimens.none_for_taxon_title')}
                emptyHint={t('catalogs.specimens.none_for_taxon_hint')}
            />

            <div className="flex flex-wrap items-center gap-2">
                {canEdit && (
                    <button
                        type="button"
                        className="btn btn-outline btn-sm"
                        onClick={() => setCollecting(true)}
                    >
                        {t('catalogs.specimens.add_for_taxon')}
                    </button>
                )}
                <Link
                    href={route('catalogs.specimens.index', {
                        project: project.id,
                    })}
                    className="btn btn-ghost btn-sm"
                >
                    {t('catalogs.specimens.see_all')}
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
