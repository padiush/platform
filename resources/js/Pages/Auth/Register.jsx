import Input from '@/Components/Input';
import AuthLayout from '@/Layouts/AuthLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Register({
    bgImage,
    honeypot,
    invitation = null,
    registrationUrl = null,
}) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        name: invitation?.name ?? '',
        email: invitation?.email ?? '',
        password: '',
        password_confirmation: '',
        ...(honeypot
            ? {
                  [honeypot.nameFieldName]: '',
                  [honeypot.validFromFieldName]: honeypot.encryptedValidFrom,
              }
            : {}),
    });

    const submit = (e) => {
        e.preventDefault();
        post(registrationUrl ?? route('register'));
    };

    return (
        <AuthLayout title={t('auth.register')} bgUrl={bgImage}>
            <Head title={t('auth.register')} />
            <p className="text-base-content">
                {t(
                    invitation
                        ? 'auth.invited_register_prompt'
                        : 'auth.register_prompt',
                )}
            </p>
            <form
                onSubmit={submit}
                className="mx-auto w-full px-4 pt-4 sm:w-2/3 lg:px-0"
            >
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
                    readOnly={Boolean(invitation)}
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
                    onChange={(e) =>
                        setData('password_confirmation', e.target.value)
                    }
                    error={errors.password_confirmation}
                    required
                />
                {honeypot?.enabled && (
                    <div
                        name={`${honeypot.nameFieldName}_wrap`}
                        style={{ display: 'none' }}
                    >
                        <input
                            type="text"
                            name={honeypot.nameFieldName}
                            id={honeypot.nameFieldName}
                            value={data[honeypot.nameFieldName]}
                            onChange={(e) =>
                                setData(honeypot.nameFieldName, e.target.value)
                            }
                        />
                        <input
                            type="text"
                            name={honeypot.validFromFieldName}
                            value={data[honeypot.validFromFieldName]}
                            onChange={(e) =>
                                setData(
                                    honeypot.validFromFieldName,
                                    e.target.value,
                                )
                            }
                        />
                    </div>
                )}
                <div className="px-4 pt-4 pb-2">
                    <button
                        type="submit"
                        className="btn btn-primary"
                        disabled={processing}
                    >
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
