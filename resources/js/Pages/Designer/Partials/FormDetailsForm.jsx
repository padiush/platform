import Input from '@/Components/Input';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

/**
 * Interview-form details (name + description) create/edit body, rendered inside
 * a FormModal on the designer list. Submits via Inertia so validation errors
 * render inline and the list refreshes + flashes on success; `onClose` closes
 * the modal on cancel and on success.
 */
export default function FormDetailsForm({ project, form = null, onClose }) {
    const { t } = useTranslation();
    const isEdit = Boolean(form);

    const { data, setData, post, put, processing, errors } = useForm({
        project_id: project.id,
        name: form?.name || '',
        description: form?.description || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();

        const options = { preserveScroll: true, onSuccess: onClose };

        if (isEdit) {
            put(
                route('designer.form.update', {
                    project: project.id,
                    form: form.id,
                }),
                options,
            );
        } else {
            post(route('designer.create', { project: project.id }), options);
        }
    };

    return (
        <form onSubmit={handleSubmit}>
            <div className="grid w-full grid-cols-1 gap-4">
                <Input
                    name="name"
                    label={t('designer.create_form.form_title_label')}
                    required
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    error={errors.name}
                />
                <Input
                    name="description"
                    type="textarea"
                    label={t('designer.create_form.description_label')}
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    error={errors.description}
                />
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
                    {isEdit ? t('actions.update') : t('actions.create')}
                </button>
            </div>
        </form>
    );
}
