import Input from '@/Components/Input';
import AuthLayout from '@/Layouts/AuthLayout';
import { Link, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Register({
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
        <AuthLayout
            title={t('auth.register')}
            heading={t('auth.register')}
            description={t(
                invitation
                    ? 'auth.invited_register_prompt'
                    : 'auth.register_prompt',
            )}
        >
            <form onSubmit={submit} className="space-y-2">
                <Input
                    name="name"
                    autoComplete="name"
                    label={t('auth.name')}
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    error={errors.name}
                    required
                />
                <Input
                    name="email"
                    type="email"
                    autoComplete="email"
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
                    autoComplete="new-password"
                    label={t('auth.password')}
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

                {honeypot?.enabled && (
                    <div style={{ display: 'none' }}>
                        <input
                            type="text"
                            name={honeypot.nameFieldName}
                            id={honeypot.nameFieldName}
                            value={data[honeypot.nameFieldName]}
                            onChange={(e) =>
                                setData(honeypot.nameFieldName, e.target.value)
                            }
                            tabIndex={-1}
                            autoComplete="off"
                        />
                        <input
                            type="text"
                            name={honeypot.validFromFieldName}
                            value={data[honeypot.validFromFieldName]}
                            readOnly
                        />
                    </div>
                )}

                <button
                    type="submit"
                    className="btn btn-primary mt-4 w-full"
                    disabled={processing}
                >
                    {t('auth.register')}
                </button>
            </form>

            <p className="text-base-content/70 mt-6 text-sm">
                {t('auth.already_registered')}{' '}
                <Link href={route('login')} className="link link-primary">
                    {t('auth.login')}
                </Link>
            </p>
        </AuthLayout>
    );
}
