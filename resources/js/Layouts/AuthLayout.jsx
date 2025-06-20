import ApplicationIsotype from '@/Components/ApplicationIsotype';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function AuthLayout({ children, title, bgUrl }) {
    const { t } = useTranslation();
    return (
        <div className="min-h-screen flex items-stretch text-base-content">
            <Head title={title} />
            <div
                className="lg:flex w-1/2 hidden bg-base-200 bg-no-repeat bg-cover relative items-center"
                style={{ backgroundImage: `url(${bgUrl})` }}
            >
                <div className="absolute bg-black opacity-60 inset-0 z-0" />
                <div className="w-full px-24 z-10">
                    <h1 className="text-5xl font-bold text-left tracking-wide">
                        Padiush
                    </h1>
                    <p className="text-3xl my-4">{t('public.hero_subtitle')}</p>
                </div>
            </div>
            <div className="lg:w-1/2 w-full flex items-center justify-center text-center md:px-16 px-0 z-0 bg-base-200">
                <div
                    className="absolute lg:hidden z-10 inset-0 bg-black bg-no-repeat bg-cover items-center"
                    style={{ backgroundImage: `url(${bgUrl})` }}
                >
                    <div className="absolute bg-base-200 opacity-60 inset-0 z-0" />
                </div>
                <div className="w-full py-6 z-20">
                    <div className="flex justify-center">
                        <ApplicationIsotype className="w-48 pb-4" />
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}

