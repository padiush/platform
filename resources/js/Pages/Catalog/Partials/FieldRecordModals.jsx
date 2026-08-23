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
                {t('catalogs.fieldRecords.cancel')}
            </button>
            <button
                type="submit"
                className="btn btn-primary btn-sm"
                disabled={processing}
            >
                {t('catalogs.fieldRecords.save')}
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
    fieldRecord = null,
    permits = [],
    exemptions = [],
    bases = [],
}) {
    const { t } = useTranslation();
    const editing = fieldRecord !== null;

    const { data, setData, post, patch, processing, errors, reset } = useForm({
        basis_of_record: fieldRecord?.basis_of_record ?? 'preserved_specimen',
        vernacular_name: fieldRecord?.vernacular_name ?? '',
        collection_number: fieldRecord?.collection_number ?? '',
        collector: fieldRecord?.collector ?? '',
        collected_on: fieldRecord?.collected_on ?? '',
        locality: fieldRecord?.locality ?? '',
        collecting_permit_id: fieldRecord?.collecting_permit_id ?? '',
        permit_exemption: fieldRecord?.permit_exemption ?? '',
        notes: fieldRecord?.notes ?? '',
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
                route('catalogs.fieldRecords.update', {
                    project: project.id,
                    fieldRecord: fieldRecord.id,
                }),
                options,
            );
        } else if (species) {
            post(
                route('catalogs.fieldRecords.store-for-species', {
                    project: project.id,
                    species: species.id,
                }),
                options,
            );
        } else {
            post(
                route('catalogs.fieldRecords.store', { project: project.id }),
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
                    ? t('catalogs.fieldRecords.edit_collection')
                    : t('catalogs.fieldRecords.add')
            }
        >
            <form
                onSubmit={submit}
                className="space-y-2"
                data-testid="collection-form"
            >
                {/*
                    Whether anything was taken. A walk with an informant
                    produces real records with nothing collected, and those can
                    never carry a voucher — so the form asks first, and the
                    collection number stops being the obvious next question.
                */}
                <Field
                    label={t('catalogs.fieldRecords.basis')}
                    hint={t(
                        `catalogs.fieldRecords.basis_hint_${data.basis_of_record}`,
                    )}
                >
                    <select
                        className="select select-bordered w-full"
                        value={data.basis_of_record}
                        onChange={(e) =>
                            setData('basis_of_record', e.target.value)
                        }
                    >
                        {bases.map((basis) => (
                            <option key={basis} value={basis}>
                                {t(`catalogs.fieldRecords.basis_${basis}`)}
                            </option>
                        ))}
                    </select>
                </Field>

                <Field
                    label={t('catalogs.fieldRecords.vernacular_name')}
                    hint={t('catalogs.fieldRecords.vernacular_name_hint')}
                >
                    <input
                        type="text"
                        className="input input-bordered w-full"
                        value={data.vernacular_name}
                        onChange={(e) =>
                            setData('vernacular_name', e.target.value)
                        }
                    />
                </Field>

                <div className="grid gap-3 sm:grid-cols-2">
                    <Field
                        label={t('catalogs.fieldRecords.collection_number')}
                        hint={t('catalogs.fieldRecords.collection_number_hint')}
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
                    <Field label={t('catalogs.fieldRecords.collector')}>
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

                <Field label={t('catalogs.fieldRecords.collected_on')}>
                    <input
                        type="date"
                        className="input input-bordered w-full"
                        value={data.collected_on ?? ''}
                        onChange={(e) =>
                            setData('collected_on', e.target.value)
                        }
                    />
                </Field>

                <Field label={t('catalogs.fieldRecords.locality')}>
                    <input
                        type="text"
                        className="input input-bordered w-full"
                        value={data.locality}
                        onChange={(e) => setData('locality', e.target.value)}
                    />
                </Field>

                {/*
                    A permit is held before the fieldwork, so unlike a voucher
                    it is known when the collection is recorded. Choosing one
                    clears the exemption and vice versa: the pair has no
                    meaning together, and the server refuses it.
                */}
                <div className="grid gap-3 sm:grid-cols-2">
                    <Field
                        label={t('catalogs.fieldRecords.permit')}
                        hint={
                            permits.length === 0
                                ? t('catalogs.fieldRecords.no_permits_yet')
                                : null
                        }
                        error={errors.collecting_permit_id}
                    >
                        <select
                            className="select select-bordered w-full"
                            value={data.collecting_permit_id ?? ''}
                            disabled={permits.length === 0}
                            onChange={(e) => {
                                setData('collecting_permit_id', e.target.value);
                                if (e.target.value)
                                    setData('permit_exemption', '');
                            }}
                        >
                            <option value="">
                                {t('catalogs.fieldRecords.permit_none')}
                            </option>
                            {permits.map((permit) => (
                                <option key={permit.id} value={permit.id}>
                                    {permit.label}
                                </option>
                            ))}
                        </select>
                    </Field>

                    <Field
                        label={t('catalogs.fieldRecords.permit_exemption')}
                        hint={t('catalogs.fieldRecords.permit_exemption_hint')}
                        error={errors.permit_exemption}
                    >
                        <select
                            className="select select-bordered w-full"
                            value={data.permit_exemption ?? ''}
                            onChange={(e) => {
                                setData('permit_exemption', e.target.value);
                                if (e.target.value)
                                    setData('collecting_permit_id', '');
                            }}
                        >
                            <option value="">
                                {t('catalogs.fieldRecords.exemption_none')}
                            </option>
                            {exemptions.map((reason) => (
                                <option key={reason} value={reason}>
                                    {t(
                                        `catalogs.fieldRecords.exemption_${reason}`,
                                    )}
                                </option>
                            ))}
                        </select>
                    </Field>
                </div>

                <Field label={t('catalogs.fieldRecords.notes')}>
                    <textarea
                        className="textarea textarea-bordered w-full"
                        rows={2}
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                    />
                </Field>

                {species && !editing && (
                    <Field label={t('catalogs.fieldRecords.determiner')}>
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
export function DetermineModal({
    open,
    onClose,
    project,
    fieldRecord,
    catalog,
}) {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors, reset } = useForm({
        catalog_species_id: fieldRecord?.species?.id ?? '',
        determiner: fieldRecord?.determiner ?? '',
        determined_on: fieldRecord?.determined_on ?? '',
        qualifier: fieldRecord?.qualifier ?? '',
    });

    function submit(event) {
        event.preventDefault();

        post(
            route('catalogs.fieldRecords.determine', {
                project: project.id,
                fieldRecord: fieldRecord.id,
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
            title={t('catalogs.fieldRecords.identify')}
        >
            <form
                onSubmit={submit}
                className="space-y-2"
                data-testid="determine-form"
            >
                <Field
                    label={t('catalogs.fieldRecords.taxon')}
                    hint={t('catalogs.fieldRecords.taxon_hint')}
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
                            {t('catalogs.fieldRecords.indet')}
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
                    <Field label={t('catalogs.fieldRecords.determiner')}>
                        <input
                            type="text"
                            className="input input-bordered w-full"
                            value={data.determiner}
                            onChange={(e) =>
                                setData('determiner', e.target.value)
                            }
                        />
                    </Field>
                    <Field label={t('catalogs.fieldRecords.determined_on')}>
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
                    label={t('catalogs.fieldRecords.qualifier')}
                    hint={t('catalogs.fieldRecords.qualifier_hint')}
                >
                    <select
                        className="select select-bordered w-full"
                        value={data.qualifier ?? ''}
                        onChange={(e) => setData('qualifier', e.target.value)}
                    >
                        <option value="">
                            {t('catalogs.fieldRecords.qualifier_none')}
                        </option>
                        {QUALIFIERS.map((q) => (
                            <option key={q} value={q}>
                                {t(`catalogs.fieldRecords.qualifier_${q}`)}
                            </option>
                        ))}
                    </select>
                </Field>

                <p className="text-base-content/60 text-xs">
                    {t('catalogs.fieldRecords.supersedes_note')}
                </p>

                <Actions onClose={onClose} processing={processing} />
            </form>
        </FormModal>
    );
}

/** Where the fieldRecord went, and under what number. */
export function DepositModal({
    open,
    onClose,
    project,
    fieldRecord,
    nextAccessionNumber,
}) {
    const { t } = useTranslation();
    const alreadyVouchered = fieldRecord?.is_vouchered ?? false;

    const { data, setData, post, processing, errors, reset } = useForm({
        accession_number: fieldRecord?.accession_number ?? '',
        repository: fieldRecord?.repository ?? '',
        mint_accession: false,
    });

    function submit(event) {
        event.preventDefault();

        post(
            route('catalogs.fieldRecords.deposit', {
                project: project.id,
                fieldRecord: fieldRecord.id,
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
            title={t('catalogs.fieldRecords.deposit')}
        >
            <form
                onSubmit={submit}
                className="space-y-2"
                data-testid="deposit-form"
            >
                <Field
                    label={t('catalogs.fieldRecords.repository')}
                    hint={t('catalogs.fieldRecords.repository_hint')}
                >
                    <input
                        type="text"
                        className="input input-bordered w-full"
                        value={data.repository}
                        onChange={(e) => setData('repository', e.target.value)}
                    />
                </Field>

                <Field
                    label={t('catalogs.fieldRecords.accession_number')}
                    hint={
                        // Nothing to advise when the field is disabled: the
                        // note below already says why.
                        alreadyVouchered
                            ? null
                            : minting
                              ? t('catalogs.fieldRecords.will_be_issued', {
                                    number: nextAccessionNumber,
                                })
                              : t('catalogs.fieldRecords.accession_hint')
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
                            {t('catalogs.fieldRecords.mint')}
                        </span>
                    </label>
                )}

                {alreadyVouchered && (
                    <p className="text-base-content/60 text-xs">
                        {t('catalogs.fieldRecords.already_vouchered_note')}
                    </p>
                )}

                <Actions onClose={onClose} processing={processing} />
            </form>
        </FormModal>
    );
}
