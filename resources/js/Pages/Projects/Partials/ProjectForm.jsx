import Input from '@/Components/Input';
import { useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

/**
 * Project create/edit form body, rendered inside a FormModal. Submits via
 * Inertia (so validation errors render inline and success flashes + refreshes
 * the list); `onClose` closes the modal on cancel and on success.
 */
export default function ProjectForm({ project = null, onClose }) {
    const { t } = useTranslation();
    const { auth } = usePage().props;
    const isEdit = Boolean(project);

    const { data, setData, post, processing, errors } = useForm({
        name: project?.name || '',
        author: project?.author || auth.user.name,
        institution: project?.institution || '',
        author_email: project?.author_email || auth.user.email,
        country: project?.country || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();

        post(
            isEdit
                ? route('projects.edit', { project: project.id })
                : route('projects.create'),
            { preserveScroll: true, onSuccess: onClose },
        );
    };

    const field = (name, label, type = 'text') => (
        <Input
            name={name}
            type={type}
            label={label}
            value={data[name]}
            onChange={(e) => setData(name, e.target.value)}
            error={errors[name]}
            required={name === 'name'}
        />
    );

    return (
        <form onSubmit={handleSubmit}>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {field('name', t('projects.name'))}
                {field('author', t('projects.author'))}
                {field('institution', t('projects.institution'))}
                {field('author_email', t('projects.author_email'), 'email')}
                {field('country', t('projects.country'))}
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
