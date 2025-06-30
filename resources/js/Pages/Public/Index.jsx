import PublicLayout from '@/Layouts/PublicLayout';
import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Index() {
    const { images, auth } = usePage().props;
    const { t } = useTranslation();

    return (
        <PublicLayout title="Padiush">
            <div
                className="hero min-h-[calc(100vh-4rem)]"
                style={{ backgroundImage: `url(${images.hero})` }}
            >
                <div className="hero-overlay bg-opacity-60" />
                <div className="hero-content text-neutral-content text-center">
                    <div className="max-w-md lg:max-w-[60vw]">
                        <h1 className="mb-5 text-5xl font-bold">
                            {t('public.hero_title')}
                        </h1>
                        <p className="mb-5">{t('public.hero_subtitle')}</p>
                        {auth?.user ? (
                            <Link
                                href={route('dashboard')}
                                className="btn btn-primary"
                            >
                                {t('public.enter_platform')}
                            </Link>
                        ) : (
                            <Link
                                href={route('register')}
                                className="btn btn-primary"
                            >
                                {t('public.try_now')}
                            </Link>
                        )}
                    </div>
                </div>
            </div>
            <div className="p-12">
                <div className="grid w-full grid-cols-1 gap-8 md:grid-cols-3">
                    <FeatureCard
                        image={images.collab}
                        title={t('public.feature_collab_title')}
                    >
                        {t('public.feature_collab_desc')}
                    </FeatureCard>
                    <FeatureCard
                        image={images.custom}
                        title={t('public.feature_custom_title')}
                    >
                        {t('public.feature_custom_desc')}
                    </FeatureCard>
                    <FeatureCard
                        image={images.catalog}
                        title={t('public.feature_catalog_title')}
                    >
                        {t('public.feature_catalog_desc')}
                    </FeatureCard>
                    <FeatureCard
                        image={images.usage}
                        title={t('public.feature_usage_title')}
                    >
                        {t('public.feature_usage_desc')}
                    </FeatureCard>
                    <FeatureCard
                        image={images.data}
                        title={t('public.feature_data_title')}
                    >
                        {t('public.feature_data_desc')}
                    </FeatureCard>
                    <FeatureCard
                        image={images.community}
                        title={t('public.feature_community_title')}
                    >
                        {t('public.feature_community_desc')}
                    </FeatureCard>
                </div>
            </div>
        </PublicLayout>
    );
}

function FeatureCard({ image, title, children }) {
    return (
        <div className="card bg-base-200 text-base-content shadow-xl">
            <figure>
                <img src={image} alt="" className="h-48 w-full object-cover" />
            </figure>
            <div className="card-body">
                <h2 className="card-title">{title}</h2>
                <p>{children}</p>
            </div>
        </div>
    );
}
