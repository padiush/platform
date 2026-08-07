import Input from '@/Components/Input';
import AuthLayout from '@/Layouts/AuthLayout';
import { Link, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function ForgotPassword({ status }) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const submit = (e) => {
        e.preventDefault();
        post(route('password.email'));
    };

    return (
        <AuthLayout
            title={t('auth.forgot_password_title')}
            heading={t('auth.forgot_password_title')}
            description={t('auth.forgot_password_prompt')}
        >
            {status && (
                <div role="status" className="alert alert-success mb-6">
                    <span>{status}</span>
                </div>
            )}

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
                <button
                    type="submit"
                    className="btn btn-primary mt-4 w-full"
                    disabled={processing}
                >
                    {t('auth.send_reset_link')}
                </button>
            </form>

            <p className="text-base-content/70 mt-6 text-sm">
                <Link href={route('login')} className="link link-primary">
                    {t('auth.back_to_login')}
                </Link>
            </p>
        </AuthLayout>
    );
}
