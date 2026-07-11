import ApplicationFullLogo from '@/Components/ApplicationFullLogo';
import PublicLayout from '@/Layouts/PublicLayout';
import { faGithub, faLinkedin } from '@fortawesome/free-brands-svg-icons';
import { faEnvelope } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function About() {
    const { images } = usePage().props;
    const { t } = useTranslation();

    return (
        <PublicLayout title={t('public.about')}>
            <div className="bg-base-300 text-base-content py-12">
                <h1 className="text-center text-3xl font-bold md:text-5xl">
                    {t('public.about')}
                </h1>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2">
                <div>
                    <img
                        src={images.about1}
                        alt=""
                        className="h-full w-full object-cover"
                    />
                </div>
                <div className="bg-white p-12 text-black">
                    <div className="text-primary flex justify-center pb-12">
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
            <div className="bg-primary text-primary-content p-12">
                <div className="text-center">
                    <span className="text-2xl md:text-3xl">
                        {t('public.team')}
                    </span>
                </div>
                <div className="flex justify-center pt-4">
                    <div className="grid grid-cols-1 gap-4 md:max-w-[70vw] md:grid-cols-2 md:gap-8 lg:max-w-[50vw] lg:gap-12">
                        <Person
                            image={images.mercedes}
                            name="Mercedes Menéndez"
                            title="Bióloga"
                            linkedin="https://www.linkedin.com/in/mercedes-men%C3%A9ndez-5209381b9/"
                            email="mercedes@padiushbio.com"
                        />
                        <Person
                            image={images.rodrigo}
                            name="Rodrigo Arévalo"
                            title="Desarrollador"
                            linkedin="https://www.linkedin.com/in/raarevalo96/"
                            github="https://github.com/raarevalo96"
                            email="rodrigo@padiushbio.com"
                        />
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}

function Person({ image, name, title, linkedin, github, email }) {
    return (
        <div className="grid grid-cols-1 gap-4">
            <img className="mask h-auto w-full mask-circle" src={image} />
            <div className="text-center">
                <span className="text-xl md:text-2xl">{name}</span>
                <div className="text-lg md:text-xl">{title}</div>
                <div className="flex justify-center gap-2 text-xl">
                    {linkedin && (
                        <a
                            href={linkedin}
                            target="_blank"
                            className="link link-hover"
                            rel="noreferrer"
                        >
                            <FontAwesomeIcon icon={faLinkedin} />
                        </a>
                    )}
                    {github && (
                        <a
                            href={github}
                            target="_blank"
                            className="link link-hover"
                            rel="noreferrer"
                        >
                            <FontAwesomeIcon icon={faGithub} />
                        </a>
                    )}
                    {email && (
                        <a
                            href={`mailto:${email}`}
                            target="_blank"
                            className="link link-hover"
                            rel="noreferrer"
                        >
                            <FontAwesomeIcon icon={faEnvelope} />
                        </a>
                    )}
                </div>
            </div>
        </div>
    );
}
