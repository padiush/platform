import ApplicationLogo from '@/Components/ApplicationLogo';
import ThemeToggle from '@/Components/ThemeToggle';
import TranslationToggle from '@/Components/TranslationToggle';
import { faArrowLeft } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

/**
 * Attribution for the open-source packages that reach the browser.
 *
 * Generated from the dependency tree at build time, so it cannot drift from
 * what is actually shipped. Packages sharing an identical notice are grouped —
 * most of these are the same MIT text differing only in a copyright line, and
 * a hundred separate blocks would be unreadable.
 */
export default function SoftwareLicences({ licences }) {
    const { t } = useTranslation();
    const user = usePage().props.auth?.user;

    return (
        <>
            <Head title={t('software.licences_title')} />

            <div className="bg-base-200 text-base-content flex min-h-screen flex-col">
                <div className="flex items-center justify-between px-6 py-4">
                    <Link href={route(user ? 'dashboard' : 'login')}>
                        <ApplicationLogo className="text-primary h-10 w-auto fill-current" />
                    </Link>
                    <div className="flex items-center gap-1">
                        <ThemeToggle />
                        <TranslationToggle />
                    </div>
                </div>

                <main className="mx-auto w-full max-w-3xl flex-1 px-6 py-10">
                    <Link
                        href={route('software.notice')}
                        className="link link-hover text-primary inline-flex items-center gap-2 text-sm"
                    >
                        <FontAwesomeIcon icon={faArrowLeft} />
                        {t('software.title')}
                    </Link>

                    <h1 className="mt-6 text-3xl font-bold md:text-4xl">
                        {t('software.licences_title')}
                    </h1>

                    <p className="mt-4 leading-relaxed">
                        {t('software.licences_intro', {
                            count: licences.packageCount,
                        })}
                    </p>

                    <div className="mt-10 flex flex-col gap-8">
                        {licences.groups.map((group, index) => (
                            <section
                                key={index}
                                className="border-base-300 bg-base-100 rounded-box border p-5"
                            >
                                <h2 className="text-lg font-bold">
                                    {group.licenses.join(' · ')}
                                </h2>

                                <ul className="text-base-content/70 mt-3 flex flex-wrap gap-x-3 gap-y-1 text-sm">
                                    {group.packages.map((pkg) => (
                                        <li key={pkg.name}>
                                            <code className="text-xs">
                                                {pkg.name}
                                            </code>
                                            {pkg.version && (
                                                <span className="text-base-content/40 text-xs">
                                                    {' '}
                                                    {pkg.version}
                                                </span>
                                            )}
                                        </li>
                                    ))}
                                </ul>

                                {group.text && (
                                    <pre className="border-base-300 bg-base-200 mt-4 max-h-64 overflow-auto rounded border p-3 text-xs leading-relaxed whitespace-pre-wrap">
                                        {group.text}
                                    </pre>
                                )}
                            </section>
                        ))}
                    </div>

                    {licences.missingText.length > 0 && (
                        <section className="border-base-300 bg-base-100 rounded-box mt-8 border p-5">
                            <h2 className="text-lg font-bold">
                                {t('software.licences_no_notice')}
                            </h2>
                            <p className="text-base-content/70 mt-2 text-sm leading-relaxed">
                                {t('software.licences_no_notice_body')}
                            </p>
                            <ul className="text-base-content/70 mt-3 flex flex-wrap gap-x-3 gap-y-1 text-sm">
                                {licences.missingText.map((name) => (
                                    <li key={name}>
                                        <code className="text-xs">{name}</code>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}
                </main>
            </div>
        </>
    );
}
