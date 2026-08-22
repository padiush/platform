import EmptyState from '@/Components/EmptyState';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

const QUALIFIERS = ['cf', 'aff', 'sp'];

const BLANK = {
    accession_number: '',
    mint_accession: false,
    collection_number: '',
    collector: '',
    collected_on: '',
    locality: '',
    location_lat: '',
    location_lng: '',
    repository: '',
    notes: '',
    determiner: '',
    determined_on: '',
    qualifier: '',
};

/** Null rather than '' so a cleared field is stored as absent, not as empty text. */
function blankToNull(values) {
    return Object.fromEntries(
        Object.entries(values).map(([key, value]) => [
            key,
            value === '' ? null : value,
        ]),
    );
}

function Field({ label, children, hint = null }) {
    return (
        <label className="form-control w-full">
            <div className="label">
                <span className="label-text">{label}</span>
            </div>
            {children}
            {hint && (
                <div className="label">
                    <span className="label-text-alt text-base-content/60">
                        {hint}
                    </span>
                </div>
            )}
        </label>
    );
}

/**
 * How the specimen is identified in print. An unvouchered collection says so
 * rather than showing an empty cell — the absence is a data-quality fact, not a
 * blank to overlook.
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

function DeterminationCell({ specimen }) {
    const { t } = useTranslation();
    const parts = [];

    if (specimen.qualifier) {
        parts.push(t(`catalogs.specimens.qualifier_${specimen.qualifier}`));
    }
    if (specimen.determiner) parts.push(specimen.determiner);
    if (specimen.determined_on) parts.push(specimen.determined_on);

    if (parts.length === 0) {
        return <span className="text-base-content/50">—</span>;
    }

    return <span className="text-sm">{parts.join(' · ')}</span>;
}

function SpecimenForm({
    project,
    species,
    editing,
    nextAccessionNumber,
    onDone,
}) {
    const { t } = useTranslation();

    const { data, setData, post, patch, processing, errors, reset } = useForm(
        editing
            ? {
                  ...BLANK,
                  ...Object.fromEntries(
                      Object.entries(editing).map(([k, v]) => [k, v ?? '']),
                  ),
                  mint_accession: false,
              }
            : BLANK,
    );

    function submit(event) {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onDone();
            },
        };

        if (editing) {
            patch(
                route('catalogs.specimens.update', {
                    project: project.id,
                    specimen: editing.id,
                }),
                { ...options, data: blankToNull(data) },
            );
        } else {
            post(
                route('catalogs.specimens.store', {
                    project: project.id,
                    species: species.id,
                }),
                { ...options, data: blankToNull(data) },
            );
        }
    }

    // Minting and typing a number are alternatives, not a combination: the
    // sequence would otherwise silently overwrite what was typed.
    const minting = data.mint_accession;

    return (
        <form
            onSubmit={submit}
            className="space-y-4"
            data-testid="specimen-form"
        >
            <div className="grid gap-3 sm:grid-cols-2">
                <Field
                    label={t('catalogs.specimens.accession_number')}
                    hint={
                        minting
                            ? t('catalogs.specimens.will_be_issued', {
                                  number: nextAccessionNumber,
                              })
                            : t('catalogs.specimens.accession_hint')
                    }
                >
                    <input
                        type="text"
                        className="input input-bordered w-full font-mono"
                        value={minting ? '' : data.accession_number}
                        disabled={minting}
                        onChange={(e) =>
                            setData('accession_number', e.target.value)
                        }
                    />
                </Field>

                <Field label={t('catalogs.specimens.collection_number')}>
                    <input
                        type="text"
                        className="input input-bordered w-full"
                        value={data.collection_number}
                        onChange={(e) =>
                            setData('collection_number', e.target.value)
                        }
                    />
                </Field>
            </div>

            {!editing || !editing.is_vouchered ? (
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
            ) : null}

            {errors.accession_number && (
                <p className="text-error text-sm">{errors.accession_number}</p>
            )}

            <div className="grid gap-3 sm:grid-cols-2">
                <Field label={t('catalogs.specimens.collector')}>
                    <input
                        type="text"
                        className="input input-bordered w-full"
                        value={data.collector}
                        onChange={(e) => setData('collector', e.target.value)}
                    />
                </Field>
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
            </div>

            <Field label={t('catalogs.specimens.locality')}>
                <input
                    type="text"
                    className="input input-bordered w-full"
                    value={data.locality}
                    onChange={(e) => setData('locality', e.target.value)}
                />
            </Field>

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

            <div className="grid gap-3 sm:grid-cols-3">
                <Field label={t('catalogs.specimens.determiner')}>
                    <input
                        type="text"
                        className="input input-bordered w-full"
                        value={data.determiner}
                        onChange={(e) => setData('determiner', e.target.value)}
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
            </div>

            <Field label={t('catalogs.specimens.notes')}>
                <textarea
                    className="textarea textarea-bordered w-full"
                    rows={2}
                    value={data.notes}
                    onChange={(e) => setData('notes', e.target.value)}
                />
            </Field>

            <div className="flex justify-end gap-2">
                <button
                    type="button"
                    className="btn btn-ghost btn-sm"
                    onClick={onDone}
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
        </form>
    );
}

export default function Specimens({
    project,
    species,
    specimens = [],
    canEdit = false,
    nextAccessionNumber = null,
}) {
    const { t } = useTranslation();
    const [adding, setAdding] = useState(false);
    const [editing, setEditing] = useState(null);
    const { delete: destroy } = useForm({});

    const vouchered = specimens.filter((s) => s.is_vouchered).length;

    function remove(specimen) {
        if (!window.confirm(t('catalogs.specimens.confirm_delete'))) return;

        destroy(
            route('catalogs.specimens.destroy', {
                project: project.id,
                specimen: specimen.id,
            }),
            { preserveScroll: true },
        );
    }

    if (adding || editing) {
        return (
            <SpecimenForm
                project={project}
                species={species}
                editing={editing}
                nextAccessionNumber={nextAccessionNumber}
                onDone={() => {
                    setAdding(false);
                    setEditing(null);
                }}
            />
        );
    }

    return (
        <div className="space-y-3">
            {specimens.length === 0 ? (
                <EmptyState
                    title={t('catalogs.specimens.none_title')}
                    hint={t('catalogs.specimens.none_hint')}
                />
            ) : (
                <>
                    <p className="text-base-content/70 text-sm">
                        {t('catalogs.specimens.coverage', {
                            vouchered,
                            total: specimens.length,
                        })}
                    </p>
                    <div className="overflow-x-auto">
                        <table className="table-sm table">
                            <thead>
                                <tr>
                                    <th>
                                        {t(
                                            'catalogs.specimens.accession_number',
                                        )}
                                    </th>
                                    <th>
                                        {t(
                                            'catalogs.specimens.collection_number',
                                        )}
                                    </th>
                                    <th>{t('catalogs.specimens.collector')}</th>
                                    <th>
                                        {t('catalogs.specimens.collected_on')}
                                    </th>
                                    <th>
                                        {t('catalogs.specimens.repository')}
                                    </th>
                                    <th>
                                        {t('catalogs.specimens.determination')}
                                    </th>
                                    {canEdit && <th />}
                                </tr>
                            </thead>
                            <tbody>
                                {specimens.map((specimen) => (
                                    <tr key={specimen.id}>
                                        <td>
                                            <AccessionCell
                                                specimen={specimen}
                                            />
                                        </td>
                                        <td>
                                            {specimen.collection_number ?? '—'}
                                        </td>
                                        <td>{specimen.collector ?? '—'}</td>
                                        <td>{specimen.collected_on ?? '—'}</td>
                                        <td>{specimen.repository ?? '—'}</td>
                                        <td>
                                            <DeterminationCell
                                                specimen={specimen}
                                            />
                                        </td>
                                        {canEdit && (
                                            <td className="text-right">
                                                <button
                                                    type="button"
                                                    className="btn btn-ghost btn-xs"
                                                    onClick={() =>
                                                        setEditing(specimen)
                                                    }
                                                >
                                                    {t(
                                                        'catalogs.specimens.edit',
                                                    )}
                                                </button>
                                                <button
                                                    type="button"
                                                    className="btn btn-ghost btn-xs text-error"
                                                    onClick={() =>
                                                        remove(specimen)
                                                    }
                                                >
                                                    {t(
                                                        'catalogs.specimens.delete',
                                                    )}
                                                </button>
                                            </td>
                                        )}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </>
            )}

            {canEdit && (
                <button
                    type="button"
                    className="btn btn-outline btn-sm"
                    onClick={() => setAdding(true)}
                >
                    {t('catalogs.specimens.add')}
                </button>
            )}
        </div>
    );
}
