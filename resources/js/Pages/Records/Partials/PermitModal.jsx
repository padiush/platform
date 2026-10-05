import FormModal from '@/Components/FormModal';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

function Field({ label, hint = null, error = null, children }) {
    return (
        <label className="form-control w-full">
            <div className="label">
                <span className="label-text">{label}</span>
            </div>
            {children}
            {(hint || error) && (
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

/**
 * Recording an authorisation. A reference record — nothing here is checked
 * against anything, and the dates are what the permit says rather than a
 * judgement about whether it still holds.
 */
export default function PermitModal({ open, onClose, project, permit = null }) {
    const { t } = useTranslation();
    const editing = permit !== null;

    const { data, setData, post, patch, processing, errors, reset } = useForm({
        authority: permit?.authority ?? '',
        reference: permit?.reference ?? '',
        issued_on: permit?.issued_on ?? '',
        expires_on: permit?.expires_on ?? '',
        notes: permit?.notes ?? '',
    });

    function submit(event) {
        event.preventDefault();

        const payload = Object.fromEntries(
            Object.entries(data).map(([k, v]) => [k, v === '' ? null : v]),
        );

        const options = {
            preserveScroll: true,
            data: payload,
            onSuccess: () => {
                reset();
                onClose();
            },
        };

        if (editing) {
            patch(
                route('catalogs.permits.update', {
                    project: project.id,
                    permit: permit.id,
                }),
                options,
            );
        } else {
            post(
                route('catalogs.permits.store', { project: project.id }),
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
                    ? t('catalogs.permits.edit_permit')
                    : t('catalogs.permits.add')
            }
        >
            <form
                onSubmit={submit}
                className="space-y-2"
                data-testid="permit-form"
            >
                <Field
                    label={t('catalogs.permits.authority')}
                    hint={t('catalogs.permits.authority_hint')}
                    error={errors.authority}
                >
                    <input
                        type="text"
                        className="input input-bordered w-full"
                        value={data.authority}
                        onChange={(e) => setData('authority', e.target.value)}
                    />
                </Field>

                <Field
                    label={t('catalogs.permits.reference')}
                    hint={t('catalogs.permits.reference_hint')}
                    error={errors.reference}
                >
                    <input
                        type="text"
                        className="input input-bordered w-full font-mono"
                        value={data.reference}
                        onChange={(e) => setData('reference', e.target.value)}
                    />
                </Field>

                <div className="grid gap-3 sm:grid-cols-2">
                    <Field label={t('catalogs.permits.issued_on')}>
                        <input
                            type="date"
                            className="input input-bordered w-full"
                            value={data.issued_on ?? ''}
                            onChange={(e) =>
                                setData('issued_on', e.target.value)
                            }
                        />
                    </Field>
                    <Field
                        label={t('catalogs.permits.expires_on')}
                        error={errors.expires_on}
                    >
                        <input
                            type="date"
                            className="input input-bordered w-full"
                            value={data.expires_on ?? ''}
                            onChange={(e) =>
                                setData('expires_on', e.target.value)
                            }
                        />
                    </Field>
                </div>

                <Field
                    label={t('catalogs.permits.notes')}
                    hint={t('catalogs.permits.notes_hint')}
                >
                    <textarea
                        className="textarea textarea-bordered w-full"
                        rows={3}
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                    />
                </Field>

                <div className="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        className="btn btn-ghost btn-sm"
                        onClick={onClose}
                    >
                        {t('catalogs.permits.cancel')}
                    </button>
                    <button
                        type="submit"
                        className="btn btn-primary btn-sm"
                        disabled={processing}
                    >
                        {t('catalogs.permits.save')}
                    </button>
                </div>
            </form>
        </FormModal>
    );
}
