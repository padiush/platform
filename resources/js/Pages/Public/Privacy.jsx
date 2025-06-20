import PublicLayout from '@/Layouts/PublicLayout';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';

export default function Privacy() {
    const { t } = useTranslation();
    const { pageContent } = usePage().props;
    return (
        <PublicLayout title={t('public.privacy')}>
            <div className="py-12 bg-base-300 text-base-content">
                <h1 className="text-5xl font-bold text-center">{t('public.privacy')}</h1>
            </div>
            <div className="px-12 py-4 bg-white text-black" dangerouslySetInnerHTML={{ __html: pageContent }} />
        </PublicLayout>
    );
}
