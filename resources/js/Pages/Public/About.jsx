import PublicLayout from '@/Layouts/PublicLayout';
import {
    faGithub,
    faLinkedin,
    faOrcid,
} from '@fortawesome/free-brands-svg-icons';
import { faBookOpen, faEnvelope } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function About() {
    const { images } = usePage().props;
    const { t } = useTranslation();

    return (
        <PublicLayout title={t('public.about')}>
            <section className="bg-primary text-primary-content">
                <div className="mx-auto max-w-3xl px-6 py-16 md:py-20">
                    <h1 className="text-3xl font-bold text-balance md:text-5xl">
                        {t('public.welcome')}
                    </h1>
                    <p className="text-primary-content/85 mt-5 text-lg md:text-xl">
                        {t('public.about_lead')}
                    </p>
                </div>
            </section>

            <section className="mx-auto max-w-3xl px-6 py-14 md:py-20">
                <div className="text-base-content/80 space-y-5 text-lg leading-relaxed">
                    <p>{t('public.about_p1')}</p>
                    <p>{t('public.about_p2')}</p>
                    <p>{t('public.about_p3')}</p>
                    {/*
                        Someone deciding whether to trust a tool with their
                        informants' data can check this one rather than take it
                        on faith, which is the point of saying so here.
                    */}
                    <p>
                        {t('public.about_open_source')}{' '}
                        <Link
                            href={route('software.notice')}
                            className="link link-primary"
                        >
                            {t('software.footer_link')}
                        </Link>
                        .
                    </p>
                </div>
            </section>

            <section className="bg-base-200">
                <div className="mx-auto max-w-7xl px-6 py-16 md:py-20">
                    <h2 className="text-base-content text-3xl font-bold md:text-4xl">
                        {t('public.about_how_title')}
                    </h2>
                    <div className="mt-10 grid gap-8 md:grid-cols-3">
                        {[1, 2, 3].map((n) => (
                            <div key={n}>
                                <h3 className="text-base-content border-primary border-l-4 pl-3 text-lg font-semibold">
                                    {t(`public.about_how_${n}_title`)}
                                </h3>
                                <p className="text-base-content/70 mt-3">
                                    {t(`public.about_how_${n}_desc`)}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-4xl px-6 py-16 md:py-20">
                <h2 className="text-base-content text-center text-3xl font-bold md:text-4xl">
                    {t('public.team')}
                </h2>
                <div className="mt-10 grid gap-10 sm:grid-cols-2">
                    <Person
                        image={images.mercedes}
                        name="Mercedes Menéndez"
                        title="Bióloga"
                        orcid="https://orcid.org/0009-0007-8862-8618"
                        linkedin="https://www.linkedin.com/in/mercedes-men%C3%A9ndez-5209381b9/"
                        publications="https://publicaciones.mercedesmenendez.com"
                        email="mercedes@padiushbio.com"
                    />
                    <Person
                        image={images.rodrigo}
                        name="Rodrigo Arévalo"
                        title="Desarrollador"
                        github="https://github.com/raarevalo96"
                        email="rodrigo@padiushbio.com"
                    />
                </div>
            </section>
        </PublicLayout>
    );
}

function Person({
    image,
    name,
    title,
    orcid,
    linkedin,
    github,
    publications,
    email,
}) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-col items-center text-center">
            <img
                className="border-base-300 h-40 w-40 rounded-full border object-cover"
                src={image}
                alt={name}
                loading="lazy"
            />
            <p className="text-base-content mt-4 text-xl font-semibold">
                {name}
            </p>
            <p className="text-base-content/60">{title}</p>
            <div className="mt-3 flex gap-4 text-xl">
                {/*
                    First, because among these it is the one that identifies a
                    researcher unambiguously — which is what a reader arriving
                    from a paper is looking for.
                */}
                {orcid && (
                    <a
                        href={orcid}
                        target="_blank"
                        className="link link-hover text-base-content/70 hover:text-primary"
                        rel="noreferrer"
                        aria-label={`ORCID — ${name}`}
                    >
                        <FontAwesomeIcon icon={faOrcid} />
                    </a>
                )}
                {linkedin && (
                    <a
                        href={linkedin}
                        target="_blank"
                        className="link link-hover text-base-content/70 hover:text-primary"
                        rel="noreferrer"
                        aria-label={`LinkedIn — ${name}`}
                    >
                        <FontAwesomeIcon icon={faLinkedin} />
                    </a>
                )}
                {github && (
                    <a
                        href={github}
                        target="_blank"
                        className="link link-hover text-base-content/70 hover:text-primary"
                        rel="noreferrer"
                        aria-label={`GitHub — ${name}`}
                    >
                        <FontAwesomeIcon icon={faGithub} />
                    </a>
                )}
                {publications && (
                    <a
                        href={publications}
                        target="_blank"
                        className="link link-hover text-base-content/70 hover:text-primary"
                        rel="noreferrer"
                        // Unlike the others this is not a brand name, so the
                        // label a screen reader announces has to be translated.
                        aria-label={`${t('public.publications')} — ${name}`}
                    >
                        <FontAwesomeIcon icon={faBookOpen} />
                    </a>
                )}
                {email && (
                    <a
                        href={`mailto:${email}`}
                        className="link link-hover text-base-content/70 hover:text-primary"
                        aria-label={`Email — ${name}`}
                    >
                        <FontAwesomeIcon icon={faEnvelope} />
                    </a>
                )}
            </div>
        </div>
    );
}
