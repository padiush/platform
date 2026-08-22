import FormModal from '@/Components/FormModal';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

const QUALIFIERS = ['cf', 'aff', 'sp'];

/** Null rather than '' so a cleared field is stored as absent, not empty text. */
function blanksToNull(values) {
    return Object.fromEntries(
        Object.entries(values).map(([k, v]) => [k, v === '' ? null : v]),
    );
}

function Field({ label, hint = null, error = null, children }) {
    return (
        <label className="form-control w-full">
            <div className="label">
                <span className="label-text">{label}</span>
            </div>
            {children}
            {(hint || error) && (
                // Not daisyUI's .label here: it is a flex row that clips a hint
                // longer than the field, and these hints are sentences.
                <p
                    className={`mt-1 text-xs whitespace-normal ${
                        error ? 'text-error' : 'text-base-content/60'
                    }`}
                >
                    {error ?? hint}
                </p>
            )}
        </label>
    );
}

function Actions({ onClose, processing }) {
    const { t } = useTranslation();

    return (
        <div className="flex justify-end gap-2 pt-2">
            <button
                type="button"
                className="btn btn-ghost btn-sm"
                onClick={onClose}
            >
                {t('catalogs.specimens.cancel')}
            </button>
            <button
                type="submit"
                className="btn btn-primary btn-sm"
                disabled={processing}
            >
                {t('catalogs.specimens.save')}
            </button>
        </div>
    );
}

/**
 * Recording a collection. Asks only what is known in the field — a voucher
 * number and a repository do not exist yet, and identification usually happens
 * later, so neither appears here.
 */
export function CollectionModal({
    open,
    onClose,
    project,
    species = null,
    specimen = null,
}) {
    const { t } = useTranslation();
    const editing = specimen !== null;

    const { data, setData, post, patch, processing, errors, reset } = useForm({
        collection_number: specimen?.collection_number ?? '',
        collector: specimen?.collector ?? '',
        collected_on: specimen?.collected_on ?? '',
        locality: specimen?.locality ?? '',
        notes: specimen?.notes ?? '',
        // Only the species-page shortcut carries these: there, the taxon is the
        // reason you are on the page.
        ...(species && !editing
            ? { determiner: '', determined_on: '', qualifier: '' }
            : {}),
    });

    function submit(event) {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            data: blanksToNull(data),
            onSuccess: () => {
                reset();
                onClose();
            },
        };

        if (editing) {
            patch(
                route('catalogs.specimens.update', {
                    project: project.id,
                    specimen: specimen.id,
                }),
                options,
            );
        } else if (species) {
            post(
                route('catalogs.specimens.store-for-species', {
                    project: project.id,
                    species: species.id,
                }),
                options,
            );
        } else {
            post(
                route('catalogs.specimens.store', { project: project.id }),
                options,
            );
        }
    }

    return (
        <FormModal
            open={open}
            onClose={onClose}
            title={
                editing
                    ? t('catalogs.specimens.edit_collection')
                    : t('catalogs.specimens.add')
            }
        >
            <form
                onSubmit={submit}
                className="space-y-2"
                data-testid="collection-form"
            >
                <div className="grid gap-3 sm:grid-cols-2">
                    <Field
                        label={t('catalogs.specimens.collection_number')}
                        hint={t('catalogs.specimens.collection_number_hint')}
                    >
                        <input
                            type="text"
                            className="input input-bordered w-full"
                            value={data.collection_number}
                            onChange={(e) =>
                                setData('collection_number', e.target.value)
                            }
                        />
                    </Field>
                    <Field label={t('catalogs.specimens.collector')}>
                        <input
                            type="text"
                            className="input input-bordered w-full"
                            value={data.collector}
                            onChange={(e) =>
                                setData('collector', e.target.value)
                            }
                        />
                    </Field>
                </div>

                <Field label={t('catalogs.specimens.collected_on')}>
                    <input
                        type="date"
                        className="input input-bordered w-full"
                        value={data.collected_on ?? ''}
                        onChange={(e) =>
                            setData('collected_on', e.target.value)
                        }
                    />
                </Field>

                <Field label={t('catalogs.specimens.locality')}>
                    <input
                        type="text"
                        className="input input-bordered w-full"
                        value={data.locality}
                        onChange={(e) => setData('locality', e.target.value)}
                    />
                </Field>

                <Field label={t('catalogs.specimens.notes')}>
                    <textarea
                        className="textarea textarea-bordered w-full"
                        rows={2}
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                    />
                </Field>

                {species && !editing && (
                    <Field label={t('catalogs.specimens.determiner')}>
                        <input
                            type="text"
                            className="input input-bordered w-full"
                            value={data.determiner}
                            onChange={(e) =>
                                setData('determiner', e.target.value)
                            }
                        />
                    </Field>
                )}

                {errors.collection_number && (
                    <p className="text-error text-sm">
                        {errors.collection_number}
                    </p>
                )}

                <Actions onClose={onClose} processing={processing} />
            </form>
        </FormModal>
    );
}

/**
 * Identifying a collection, or revising an identification. Leaving the taxon
 * empty is a real answer — it records that someone examined this and could not
 * name it, which is not the same as nobody having looked.
 */
export function DetermineModal({ open, onClose, project, specimen, catalog }) {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors, reset } = useForm({
        catalog_species_id: specimen?.species?.id ?? '',
        determiner: specimen?.determiner ?? '',
        determined_on: specimen?.determined_on ?? '',
        qualifier: specimen?.qualifier ?? '',
    });

    function submit(event) {
        event.preventDefault();

        post(
            route('catalogs.specimens.determine', {
                project: project.id,
                specimen: specimen.id,
            }),
            {
                preserveScroll: true,
                data: blanksToNull(data),
                onSuccess: () => {
                    reset();
                    onClose();
                },
            },
        );
    }

    return (
        <FormModal
            open={open}
            onClose={onClose}
            title={t('catalogs.specimens.identify')}
        >
            <form
                onSubmit={submit}
                className="space-y-2"
                data-testid="determine-form"
            >
                <Field
                    label={t('catalogs.specimens.taxon')}
                    hint={t('catalogs.specimens.taxon_hint')}
                    error={errors.catalog_species_id}
                >
                    <select
                        className="select select-bordered w-full"
                        value={data.catalog_species_id ?? ''}
                        onChange={(e) =>
                            setData('catalog_species_id', e.target.value)
                        }
                    >
                        <option value="">
                            {t('catalogs.specimens.indet')}
                        </option>
                        {catalog.map((sp) => (
                            <option key={sp.id} value={sp.id}>
                                {sp.genus} {sp.name}
                                {sp.authority ? ` ${sp.authority}` : ''}
                            </option>
                        ))}
                    </select>
                </Field>

                <div className="grid gap-3 sm:grid-cols-2">
                    <Field label={t('catalogs.specimens.determiner')}>
                        <input
                            type="text"
                            className="input input-bordered w-full"
                            value={data.determiner}
                            onChange={(e) =>
                                setData('determiner', e.target.value)
                            }
                        />
                    </Field>
                    <Field label={t('catalogs.specimens.determined_on')}>
                        <input
                            type="date"
                            className="input input-bordered w-full"
                            value={data.determined_on ?? ''}
                            onChange={(e) =>
                                setData('determined_on', e.target.value)
                            }
                        />
                    </Field>
                </div>

                <Field
                    label={t('catalogs.specimens.qualifier')}
                    hint={t('catalogs.specimens.qualifier_hint')}
                >
                    <select
                        className="select select-bordered w-full"
                        value={data.qualifier ?? ''}
                        onChange={(e) => setData('qualifier', e.target.value)}
                    >
                        <option value="">
                            {t('catalogs.specimens.qualifier_none')}
                        </option>
                        {QUALIFIERS.map((q) => (
                            <option key={q} value={q}>
                                {t(`catalogs.specimens.qualifier_${q}`)}
                            </option>
                        ))}
                    </select>
                </Field>

                <p className="text-base-content/60 text-xs">
                    {t('catalogs.specimens.supersedes_note')}
                </p>

                <Actions onClose={onClose} processing={processing} />
            </form>
        </FormModal>
    );
}

/** Where the specimen went, and under what number. */
export function DepositModal({
    open,
    onClose,
    project,
    specimen,
    nextAccessionNumber,
}) {
    const { t } = useTranslation();
    const alreadyVouchered = specimen?.is_vouchered ?? false;

    const { data, setData, post, processing, errors, reset } = useForm({
        accession_number: specimen?.accession_number ?? '',
        repository: specimen?.repository ?? '',
        mint_accession: false,
    });

    function submit(event) {
        event.preventDefault();

        post(
            route('catalogs.specimens.deposit', {
                project: project.id,
                specimen: specimen.id,
            }),
            {
                preserveScroll: true,
                data: blanksToNull(data),
                onSuccess: () => {
                    reset();
                    onClose();
                },
            },
        );
    }

    const minting = data.mint_accession;

    return (
        <FormModal
            open={open}
            onClose={onClose}
            title={t('catalogs.specimens.deposit')}
        >
            <form
                onSubmit={submit}
                className="space-y-2"
                data-testid="deposit-form"
            >
                <Field
                    label={t('catalogs.specimens.repository')}
                    hint={t('catalogs.specimens.repository_hint')}
                >
                    <input
                        type="text"
                        className="input input-bordered w-full"
                        value={data.repository}
                        onChange={(e) => setData('repository', e.target.value)}
                    />
                </Field>

                <Field
                    label={t('catalogs.specimens.accession_number')}
                    hint={
                        // Nothing to advise when the field is disabled: the
                        // note below already says why.
                        alreadyVouchered
                            ? null
                            : minting
                              ? t('catalogs.specimens.will_be_issued', {
                                    number: nextAccessionNumber,
                                })
                              : t('catalogs.specimens.accession_hint')
                    }
                    error={errors.accession_number}
                >
                    <input
                        type="text"
                        className="input input-bordered w-full font-mono"
                        value={minting ? '' : data.accession_number}
                        disabled={minting || alreadyVouchered}
                        onChange={(e) =>
                            setData('accession_number', e.target.value)
                        }
                    />
                </Field>

                {!alreadyVouchered && (
                    <label className="label cursor-pointer justify-start gap-3">
                        <input
                            type="checkbox"
                            className="checkbox checkbox-sm"
                            checked={data.mint_accession}
                            onChange={(e) =>
                                setData('mint_accession', e.target.checked)
                            }
                        />
                        <span className="label-text">
                            {t('catalogs.specimens.mint')}
                        </span>
                    </label>
                )}

                {alreadyVouchered && (
                    <p className="text-base-content/60 text-xs">
                        {t('catalogs.specimens.already_vouchered_note')}
                    </p>
                )}

                <Actions onClose={onClose} processing={processing} />
            </form>
        </FormModal>
    );
}
