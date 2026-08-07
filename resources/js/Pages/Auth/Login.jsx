import Input from '@/Components/Input';
import AuthLayout from '@/Layouts/AuthLayout';
import { Link, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Login({ canResetPassword, canRegister }) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('login'));
    };

    return (
        <AuthLayout
            title={t('auth.login')}
            heading={t('auth.login')}
            description={t('auth.login_prompt')}
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
                    autoComplete="current-password"
                    label={t('auth.password')}
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                    required
                />
                <div className="flex flex-wrap items-center justify-between gap-2 pt-2">
                    <Input
                        type="checkbox"
                        label={t('auth.remember_me')}
                        checked={data.remember}
                        onChange={(e) => setData('remember', e.target.checked)}
                    />
                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="link link-primary text-sm"
                        >
                            {t('auth.forgot_password')}
                        </Link>
                    )}
                </div>
                <button
                    type="submit"
                    className="btn btn-primary mt-4 w-full"
                    disabled={processing}
                >
                    {t('auth.login')}
                </button>
            </form>

            {canRegister && (
                <p className="text-base-content/70 mt-6 text-sm">
                    {t('auth.no_account')}{' '}
                    <Link
                        href={route('register')}
                        className="link link-primary"
                    >
                        {t('auth.register')}
                    </Link>
                </p>
            )}
        </AuthLayout>
    );
}
