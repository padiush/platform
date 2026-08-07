import AuthLayout from '@/Layouts/AuthLayout';
import { useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function VerifyEmail({ status }) {
    const { t } = useTranslation();
    const { post, processing } = useForm({});

    const resend = (e) => {
        e.preventDefault();
        post(route('verification.send'));
    };

    const signOut = (e) => {
        e.preventDefault();
        post(route('logout'));
    };

    return (
        <AuthLayout
            title={t('auth.verify_email_title')}
            heading={t('auth.verify_email_title')}
            description={t('auth.verify_email_prompt')}
        >
            {status === 'verification-link-sent' && (
                <div role="status" className="alert alert-success mb-6">
                    <span>{t('auth.verification_link_sent')}</span>
                </div>
            )}

            <div className="flex flex-wrap items-center gap-3">
                <form onSubmit={resend}>
                    <button
                        type="submit"
                        className="btn btn-primary"
                        disabled={processing}
                    >
                        {t('auth.resend_verification')}
                    </button>
                </form>
                <form onSubmit={signOut}>
                    <button type="submit" className="btn btn-ghost">
                        {t('auth.log_out')}
                    </button>
                </form>
            </div>
        </AuthLayout>
    );
}
