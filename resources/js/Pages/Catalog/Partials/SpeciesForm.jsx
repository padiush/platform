import Input from '@/Components/Input';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

/**
 * Catalog species register form body, rendered inside a FormModal on the
 * catalog hub. Submits via Inertia so validation errors render inline and the
 * hub refreshes + flashes on success; `onClose` closes the modal on cancel and
 * on success.
 */
export default function SpeciesForm({ project, onClose }) {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors } = useForm({
        family: '',
        genus: '',
        name: '',
        authority: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();

        post(route('catalogs.species.register', { project: project.id }), {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    const field = (name, label, required = false) => (
        <Input
            name={name}
            label={label}
            value={data[name]}
            onChange={(e) => setData(name, e.target.value)}
            error={errors[name]}
            required={required}
        />
    );

    return (
        <form onSubmit={handleSubmit}>
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                {field('family', t('catalogs.family'))}
                {field('genus', t('catalogs.genus'), true)}
                {field('name', t('catalogs.species'), true)}
                {field('authority', t('catalogs.authority'))}
            </div>
            <div className="mt-4 flex justify-end gap-2">
                <button
                    type="button"
                    className="btn btn-ghost"
                    onClick={onClose}
                >
                    {t('actions.cancel')}
                </button>
                <button
                    type="submit"
                    className="btn btn-primary"
                    disabled={processing}
                >
                    {t('catalogs.register_species')}
                </button>
            </div>
        </form>
    );
}
