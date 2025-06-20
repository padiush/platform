import PublicLayout from '@/Layouts/PublicLayout';
import ApplicationFullLogo from '@/Components/ApplicationFullLogo';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function About() {
    const { images } = usePage().props;
    const { t } = useTranslation();

    return (
        <PublicLayout title={t('public.about')}>
            <div className="py-12 bg-base-300 text-base-content">
                <h1 className="text-3xl md:text-5xl font-bold text-center">
                    {t('public.about')}
                </h1>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2">
                <div>
                    <img src={images.about1} alt="" className="h-full w-full object-cover" />
                </div>
                <div className="p-12 bg-white text-black">
                    <div className="flex justify-center pb-12 text-primary">
                        <ApplicationFullLogo className="h-auto w-[80vw] md:w-[50vw] lg:w-[25vw]" />
                    </div>
                    <div className="text-center text-2xl font-bold">
                        <p>{t('public.welcome')}</p>
                    </div>
                    <div className="text-justify lg:text-xl">
                        <p>{t('public.about_paragraph1')}</p>
                    </div>
                </div>
            </div>
            <div className="p-12 bg-primary text-primary-content">
                <div className="text-center">
                    <span className="text-2xl md:text-3xl">{t('public.team')}</span>
                </div>
                <div className="flex justify-center pt-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-8 lg:gap-12 md:max-w-[70vw] lg:max-w-[50vw]">
                        <Person image={images.mercedes} name="Mercedes Menéndez" title="Bióloga" linkedin="https://www.linkedin.com/in/mercedes-men%C3%A9ndez-5209381b9/" email="mercedes@padiushbio.com" />
                        <Person image={images.rodrigo} name="Rodrigo Arévalo" title="Desarrollador" linkedin="https://www.linkedin.com/in/raarevalo96/" github="https://github.com/raarevalo96" email="rodrigo@padiushbio.com" />
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}

function Person({ image, name, title, linkedin, github, email }) {
    return (
        <div className="grid grid-cols-1 gap-4">
            <img className="mask mask-circle w-full h-auto" src={image} />
            <div className="text-center">
                <span className="text-xl md:text-2xl">{name}</span>
                <div className="text-lg md:text-xl">{title}</div>
                <div className="text-xl flex justify-center gap-2">
                    {linkedin && (
                        <a href={linkedin} target="_blank" className="link link-hover">
                            <i className="fa-brands fa-linkedin" />
                        </a>
                    )}
                    {github && (
                        <a href={github} target="_blank" className="link link-hover">
                            <i className="fa-brands fa-github" />
                        </a>
                    )}
                    {email && (
                        <a href={`mailto:${email}`} target="_blank" className="link link-hover">
                            <i className="fa-solid fa-envelope" />
                        </a>
                    )}
                </div>
            </div>
        </div>
    );
}
