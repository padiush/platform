import AuthLayout from '@/Layouts/AuthLayout';
import Input from '@/Components/Input';
import { Head, Link, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Register({ bgImage }) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('register'));
    };

    return (
        <AuthLayout title={t('auth.register')} bgUrl={bgImage}>
            <Head title={t('auth.register')} />
            <p className="text-base-content">{t('auth.register_prompt')}</p>
            <form onSubmit={submit} className="sm:w-2/3 w-full px-4 lg:px-0 mx-auto pt-4">
                <Input
                    name="name"
                    label={t('auth.name')}
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    error={errors.name}
                    required
                />
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
                    name="password_confirmation"
                    type="password"
                    label={t('auth.confirm_password')}
                    value={data.password_confirmation}
                    onChange={(e) => setData('password_confirmation', e.target.value)}
                    error={errors.password_confirmation}
                    required
                />
                <div className="px-4 pb-2 pt-4">
                    <button type="submit" className="btn btn-primary" disabled={processing}>
                        {t('auth.register')}
                    </button>
                </div>
            </form>
            <div className="pt-2">
                <Link href={route('login')} className="link link-hover text-sm">
                    {t('auth.already_registered')}
                </Link>
            </div>
        </AuthLayout>
    );
}

