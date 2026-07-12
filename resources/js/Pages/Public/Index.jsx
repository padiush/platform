import PublicLayout from '@/Layouts/PublicLayout';
import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Index() {
    const { images, auth } = usePage().props;
    const { t } = useTranslation();

    const primaryCta = auth?.user ? (
        <Link href={route('dashboard')} className="btn btn-primary btn-lg">
            {t('public.enter_platform')}
        </Link>
    ) : (
        <Link href={route('register')} className="btn btn-primary btn-lg">
            {t('public.try_now')}
        </Link>
    );

    return (
        <PublicLayout title="Padiush">
            <div
                className="hero min-h-[calc(100vh-4rem)]"
                style={{ backgroundImage: `url(${images.hero})` }}
            >
                <div className="hero-overlay bg-black/60" />
                <div className="hero-content text-center">
                    <div className="max-w-2xl lg:max-w-3xl">
                        <h1 className="mb-6 text-4xl font-bold text-white md:text-6xl">
                            {t('public.hero_title')}
                        </h1>
                        <p className="mb-8 text-lg text-white/90 md:text-xl">
                            {t('public.hero_subtitle')}
                        </p>
                        <div className="flex flex-wrap justify-center gap-3">
                            {primaryCta}
                            <Link
                                href={route('public.about')}
                                className="btn btn-outline btn-lg border-white text-white hover:border-white hover:bg-white hover:text-neutral-950"
                            >
                                {t('public.learn_more')}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <section className="mx-auto max-w-7xl px-6 py-16 md:py-24">
                <div className="mx-auto mb-12 max-w-2xl text-center md:mb-16">
                    <h2 className="text-base-content text-3xl font-bold md:text-4xl">
                        {t('public.features_title')}
                    </h2>
                    <p className="text-base-content/70 mt-4 text-lg">
                        {t('public.features_subtitle')}
                    </p>
                </div>
                <div className="grid w-full grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
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
            </section>

            <section className="bg-primary text-primary-content">
                <div className="mx-auto flex max-w-4xl flex-col items-center gap-6 px-6 py-16 text-center md:py-20">
                    <h2 className="text-3xl font-bold md:text-4xl">
                        {t('public.cta_title')}
                    </h2>
                    {auth?.user ? (
                        <Link
                            href={route('dashboard')}
                            className="btn btn-lg bg-base-100 text-base-content hover:bg-base-200 border-none"
                        >
                            {t('public.enter_platform')}
                        </Link>
                    ) : (
                        <Link
                            href={route('register')}
                            className="btn btn-lg bg-base-100 text-base-content hover:bg-base-200 border-none"
                        >
                            {t('public.try_now')}
                        </Link>
                    )}
                </div>
            </section>
        </PublicLayout>
    );
}

function FeatureCard({ image, title, children }) {
    return (
        <div className="card bg-base-200 text-base-content shadow-md transition-all duration-200 hover:-translate-y-1 hover:shadow-xl">
            <figure>
                <img src={image} alt="" className="h-48 w-full object-cover" />
            </figure>
            <div className="card-body">
                <h3 className="card-title">{title}</h3>
                <p>{children}</p>
            </div>
        </div>
    );
}
