import ApplicationLogo from '@/Components/ApplicationLogo';
import ThemeToggle from '@/Components/ThemeToggle';
import TranslationToggle from '@/Components/TranslationToggle';
import { faArrowUpRightFromSquare } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

/**
 * The offer of source required by section 13 of the AGPL.
 *
 * It carries its own chrome rather than PublicLayout's, because that layout
 * links to the marketing pages and those may be switched off — this page has
 * to stand on a deployment that publishes nothing else.
 */
export default function SoftwareNotice({ sourceUrl, appName }) {
    const { t } = useTranslation();
    const user = usePage().props.auth?.user;

    return (
        <>
            <Head title={t('software.title')} />

            <div className="bg-base-200 text-base-content flex min-h-screen flex-col">
                <div className="flex items-center justify-between px-6 py-4">
                    <Link
                        href={route(user ? 'dashboard' : 'login')}
                        aria-label={appName}
                    >
                        <ApplicationLogo className="text-primary h-10 w-auto fill-current" />
                    </Link>
                    <div className="flex items-center gap-1">
                        <ThemeToggle />
                        <TranslationToggle />
                    </div>
                </div>

                <main className="mx-auto flex w-full max-w-2xl flex-1 flex-col justify-center px-6 py-12">
                    <h1 className="text-3xl font-bold md:text-4xl">
                        {t('software.title')}
                    </h1>

                    <p className="mt-6 leading-relaxed">
                        {t('software.free_software', { name: appName })}
                    </p>

                    <p className="mt-4 leading-relaxed">
                        {t('software.offer_of_source')}
                    </p>

                    <a
                        href={sourceUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="btn btn-primary mt-8 self-start"
                    >
                        {t('software.get_the_source')}
                        <FontAwesomeIcon icon={faArrowUpRightFromSquare} />
                    </a>

                    <p className="text-base-content/60 mt-8 text-sm leading-relaxed">
                        {t('software.modified_notice')}
                    </p>

                    <Link
                        href={route(user ? 'dashboard' : 'login')}
                        className="link link-hover text-primary mt-10 self-start text-sm"
                    >
                        {t('software.back')}
                    </Link>
                </main>
            </div>
        </>
    );
}
