import Input from '@/Components/Input';
import AuthLayout from '@/Layouts/AuthLayout';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function ConfirmPassword() {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('password.confirm'));
    };

    return (
        <AuthLayout
            title={t('auth.confirm_password_title')}
            heading={t('auth.confirm_password_title')}
            description={t('auth.confirm_password_prompt')}
        >
            <form onSubmit={submit} className="space-y-2">
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
                <button
                    type="submit"
                    className="btn btn-primary mt-4 w-full"
                    disabled={processing}
                >
                    {t('auth.confirm')}
                </button>
            </form>
        </AuthLayout>
    );
}
