import FormModal from '@/Components/FormModal';
import Input from '@/Components/Input';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

/** Invite someone to create an account while registration is closed. */
export default function InviteModal({ open, onClose }) {
    const { t } = useTranslation();
    const form = useForm({ name: '', email: '' });

    const submit = (event) => {
        event.preventDefault();
        form.post(route('system.registration-invites.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onClose();
            },
        });
    };

    return (
        <FormModal
            open={open}
            onClose={onClose}
            title={t('system.invite.title')}
        >
            <form className="space-y-3" onSubmit={submit}>
                <p className="text-base-content/70 text-sm">
                    {t('system.invite.description')}
                </p>
                <Input
                    name="name"
                    label={t('auth.name')}
                    value={form.data.name}
                    onChange={(event) =>
                        form.setData('name', event.target.value)
                    }
                    error={form.errors.name}
                    required
                />
                <Input
                    name="email"
                    type="email"
                    label={t('auth.email')}
                    value={form.data.email}
                    onChange={(event) =>
                        form.setData('email', event.target.value)
                    }
                    error={form.errors.email}
                    required
                />
                <div className="flex justify-end gap-2 pt-2">
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
                        disabled={form.processing}
                    >
                        {t('system.invite.send')}
                    </button>
                </div>
            </form>
        </FormModal>
    );
}
