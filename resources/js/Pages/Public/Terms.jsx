import PublicLayout from '@/Layouts/PublicLayout';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Terms() {
    const { t } = useTranslation();
    const { pageContent } = usePage().props;
    return (
        <PublicLayout title={t('public.terms')}>
            <div className="bg-base-300 text-base-content py-12">
                <h1 className="text-center text-5xl font-bold">
                    {t('public.terms')}
                </h1>
            </div>
            <div
                className="bg-white px-12 py-4 text-black"
                dangerouslySetInnerHTML={{ __html: pageContent }}
            />
        </PublicLayout>
    );
}
