import Input from '@/Components/Input';
import AuthLayout from '@/Layouts/AuthLayout';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function ResetPassword({ token, email }) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        token,
        email: email ?? '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('password.update'));
    };

    return (
        <AuthLayout
            title={t('auth.reset_password_title')}
            heading={t('auth.reset_password_title')}
            description={t('auth.reset_password_prompt')}
        >
            <form onSubmit={submit} className="space-y-2">
                <Input
                    name="email"
                    type="email"
                    autoComplete="email"
                    label={t('auth.email')}
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                    required
                />
                <Input
                    name="password"
                    type="password"
                    autoComplete="new-password"
                    label={t('auth.new_password')}
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                    required
                />
                <Input
                    name="password_confirmation"
                    type="password"
                    autoComplete="new-password"
                    label={t('auth.confirm_password')}
                    value={data.password_confirmation}
                    onChange={(e) =>
                        setData('password_confirmation', e.target.value)
                    }
                    error={errors.password_confirmation}
                    required
                />
                <button
                    type="submit"
                    className="btn btn-primary mt-4 w-full"
                    disabled={processing}
                >
                    {t('auth.reset_password_action')}
                </button>
            </form>
        </AuthLayout>
    );
}
