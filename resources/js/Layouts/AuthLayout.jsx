import ApplicationIsotype from '@/Components/ApplicationIsotype';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function AuthLayout({ children, title, bgUrl }) {
    const { t } = useTranslation();
    return (
        <div className="text-base-content flex min-h-screen items-stretch">
            <Head title={title} />
            <div
                className="bg-base-200 relative hidden w-1/2 items-center bg-cover bg-no-repeat lg:flex"
                style={{ backgroundImage: `url(${bgUrl})` }}
            >
                <div className="absolute inset-0 z-0 bg-black opacity-60" />
                <div className="z-10 w-full px-24">
                    <h1 className="text-left text-5xl font-bold tracking-wide">
                        Padiush
                    </h1>
                    <p className="my-4 text-3xl">{t('public.hero_subtitle')}</p>
                </div>
            </div>
            <div className="bg-base-200 z-0 flex w-full items-center justify-center px-0 text-center md:px-16 lg:w-1/2">
                <div
                    className="absolute inset-0 z-10 items-center bg-black bg-cover bg-no-repeat lg:hidden"
                    style={{ backgroundImage: `url(${bgUrl})` }}
                >
                    <div className="bg-base-200 absolute inset-0 z-0 opacity-60" />
                </div>
                <div className="z-20 w-full py-6">
                    <div className="flex justify-center">
                        <ApplicationIsotype className="w-48 pb-4" />
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
