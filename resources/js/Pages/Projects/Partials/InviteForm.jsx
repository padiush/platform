import Input from '@/Components/Input';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

/**
 * Invite-user form body, rendered inside a FormModal on the project access
 * page. Submits via Inertia so field validation errors render inline (the
 * modal stays open) while business rejections — duplicate invite, already a
 * member — keep flashing as toasts; `onClose` closes on cancel and on success.
 */
export default function InviteForm({ project, capabilities, onClose }) {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        capability_id: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();

        post(route('projects.accesses.invite', { project: project.id }), {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <form onSubmit={handleSubmit}>
            <Input
                name="name"
                label={t('projects.access_form.name')}
                value={data.name}
                onChange={(e) => setData('name', e.target.value)}
                error={errors.name}
            />
            <Input
                name="email"
                type="email"
                label={t('projects.access_form.email')}
                value={data.email}
                onChange={(e) => setData('email', e.target.value)}
                error={errors.email}
            />
            <div className="form-control w-full">
                <fieldset className="fieldset w-full">
                    <legend className="fieldset-legend">
                        {t('projects.access_form.permission')}
                    </legend>
                    <select
                        name="capability_id"
                        value={data.capability_id}
                        onChange={(e) =>
                            setData('capability_id', e.target.value)
                        }
                        className={`select select-bordered w-full ${
                            errors.capability_id ? 'select-error' : ''
                        }`}
                        required
                    >
                        <option value="" disabled>
                            {t('projects.access_form.select_permission')}
                        </option>
                        {capabilities.map((capability) => (
                            <option key={capability.id} value={capability.id}>
                                {capability.name}
                            </option>
                        ))}
                    </select>
                    {errors.capability_id && (
                        <p className="text-error mt-1 text-sm">
                            {errors.capability_id}
                        </p>
                    )}
                </fieldset>
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
                    {t('actions.invite')}
                </button>
            </div>
        </form>
    );
}
