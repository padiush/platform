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
            <article
                className="text-base-content mx-auto max-w-3xl px-6 py-10 leading-relaxed md:px-8"
                dangerouslySetInnerHTML={{ __html: pageContent }}
            />
        </PublicLayout>
    );
}
