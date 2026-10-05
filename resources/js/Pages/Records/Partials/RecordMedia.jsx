import ConfirmModal from '@/Components/ConfirmModal';
import { router, useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

/**
 * The photographs and audio on a field record.
 *
 * For a record of something that was never collected these are the whole of
 * the evidence — there is no pressed material to go back to — so the empty
 * state says what to do rather than merely noting an absence.
 * See docs/decisions/0010-field-records-and-basis.md.
 */
export default function RecordMedia({ project, fieldRecord, canEdit = false }) {
    const { t } = useTranslation();
    const fileRef = useRef(null);
    const [pendingDelete, setPendingDelete] = useState(null);
    const media = fieldRecord.media ?? [];

    const { setData, post, processing, errors, reset } = useForm({
        file: null,
    });

    function upload(event) {
        const file = event.target.files?.[0];
        if (!file) return;

        setData('file', file);

        post(
            route('catalogs.fieldRecords.media.store', {
                project: project.id,
                fieldRecord: fieldRecord.id,
            }),
            {
                data: { file },
                forceFormData: true,
                preserveScroll: true,
                onFinish: () => {
                    reset();
                    if (fileRef.current) fileRef.current.value = '';
                },
            },
        );
    }

    // The bytes go with the row, so this is not a detach — and where nothing
    // was collected it may be the only evidence the record has.
    function remove() {
        router.delete(
            route('catalogs.fieldRecords.media.destroy', {
                project: project.id,
                fieldRecord: fieldRecord.id,
                medium: pendingDelete.id,
            }),
            { preserveScroll: true, onFinish: () => setPendingDelete(null) },
        );
    }

    return (
        <div className="space-y-3">
            {media.length === 0 ? (
                <p className="text-base-content/60 text-sm">
                    {fieldRecord.was_collected === false
                        ? t('catalogs.fieldRecords.media.none_observation')
                        : t('catalogs.fieldRecords.media.none')}
                </p>
            ) : (
                <div className="flex flex-wrap gap-3">
                    {media.map((medium) => (
                        <figure
                            key={medium.id}
                            className="border-base-300 rounded-box border p-2"
                        >
                            {medium.kind === 'photo' ? (
                                <img
                                    src={medium.url}
                                    alt={t(
                                        'catalogs.fieldRecords.media.photo_alt',
                                    )}
                                    className="h-32 w-32 rounded object-cover"
                                />
                            ) : (
                                <audio
                                    controls
                                    src={medium.url}
                                    className="w-64"
                                >
                                    <track kind="captions" />
                                </audio>
                            )}
                            {canEdit && (
                                <figcaption className="mt-1 text-center">
                                    <button
                                        type="button"
                                        className="btn btn-ghost btn-xs text-error"
                                        onClick={() => setPendingDelete(medium)}
                                    >
                                        {t(
                                            'catalogs.fieldRecords.media.remove',
                                        )}
                                    </button>
                                </figcaption>
                            )}
                        </figure>
                    ))}
                </div>
            )}

            {errors.file && <p className="text-error text-sm">{errors.file}</p>}

            <ConfirmModal
                open={pendingDelete !== null}
                title={t('catalogs.fieldRecords.media.confirm_delete_title')}
                message={
                    fieldRecord.was_collected === false
                        ? t(
                              'catalogs.fieldRecords.media.confirm_delete_only_evidence',
                          )
                        : t('catalogs.fieldRecords.media.confirm_delete')
                }
                onConfirm={remove}
                onClose={() => setPendingDelete(null)}
            />

            {canEdit && (
                <label className="btn btn-outline btn-sm">
                    {t('catalogs.fieldRecords.media.add')}
                    <input
                        ref={fileRef}
                        type="file"
                        className="hidden"
                        accept="image/jpeg,image/png,image/webp,audio/*"
                        disabled={processing}
                        onChange={upload}
                    />
                </label>
            )}
        </div>
    );
}
