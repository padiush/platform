import Input from '@/Components/Input';
import AuthLayout from '@/Layouts/AuthLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Login({ canResetPassword, bgImage }) {
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
        <AuthLayout title={t('auth.login')} bgUrl={bgImage}>
            <Head title={t('auth.login')} />
            <p className="text-base-content">{t('auth.login_prompt')}</p>
            <form
                onSubmit={submit}
                className="mx-auto w-full px-4 pt-4 sm:w-2/3 lg:px-0"
            >
                <Input
                    name="email"
                    type="email"
                    label={t('auth.email')}
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                    required
                />
                <Input
                    name="password"
                    type="password"
                    label={t('auth.password')}
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                    required
                />
                <Input
                    className="pt-2"
                    type="checkbox"
                    label={t('auth.remember_me')}
                    checked={data.remember}
                    onChange={(e) => setData('remember', e.target.checked)}
                />
                <div className="px-4 pt-4 pb-2">
                    <button
                        type="submit"
                        className="btn btn-primary"
                        disabled={processing}
                    >
                        {t('auth.login')}
                    </button>
                </div>
            </form>
            {canResetPassword && (
                <Link
                    href={route('password.request')}
                    className="link link-hover text-sm"
                >
                    {t('auth.forgot_password')}
                </Link>
            )}
        </AuthLayout>
    );
}
