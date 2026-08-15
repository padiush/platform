import ApplicationFullLogo from '@/Components/ApplicationFullLogo';
import ThemeToggle from '@/Components/ThemeToggle';
import TranslationToggle from '@/Components/TranslationToggle';
import { useFlashMessage } from '@/Hooks/useFlashMessage';
import { faArrowLeft } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

/**
 * The shell every authentication screen shares.
 *
 * The brand panel is drawn with the theme's own colours rather than a
 * photograph: the previous version pulled a background image through a
 * presigned URL that expired after five minutes and could never be cached,
 * and its caption inherited base-content — dark ink over a dark overlay.
 */
export default function AuthLayout({ children, title, heading, description }) {
    const { t } = useTranslation();
    const { FlashAlert, flashShown } = useFlashMessage();

    return (
        <div className="bg-base-100 text-base-content flex min-h-screen flex-col lg:flex-row">
            <Head title={title} />

            <aside className="from-primary to-primary/80 text-primary-content flex flex-col justify-between bg-gradient-to-br px-6 py-8 lg:w-2/5 lg:px-12 lg:py-16">
                <Link
                    href={route('public.index')}
                    className="inline-block self-start"
                    aria-label="Padiush"
                >
                    <ApplicationFullLogo className="h-9 w-auto fill-current lg:h-11" />
                </Link>
                <p className="hidden max-w-sm text-2xl leading-snug text-balance lg:block">
                    {t('public.tagline')}
                </p>
                <Link
                    href={route('public.index')}
                    className="text-primary-content/80 hover:text-primary-content hidden items-center gap-2 self-start text-sm lg:inline-flex"
                >
                    <FontAwesomeIcon icon={faArrowLeft} />
                    {t('auth.back_to_site')}
                </Link>
            </aside>

            <main className="flex flex-1 flex-col px-6 py-8 lg:px-12 lg:py-16">
                <div className="flex justify-end gap-1">
                    <ThemeToggle />
                    <TranslationToggle />
                </div>

                <div className="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-8">
                    {flashShown && (
                        <div className="mb-6">
                            <FlashAlert />
                        </div>
                    )}

                    <h1 className="text-3xl font-bold">{heading}</h1>
                    {description && (
                        <p className="text-base-content/70 mt-3">
                            {description}
                        </p>
                    )}

                    <div className="mt-8">{children}</div>
                </div>

                <Link
                    href={route('public.index')}
                    className="text-base-content/60 hover:text-base-content inline-flex items-center gap-2 self-start text-sm lg:hidden"
                >
                    <FontAwesomeIcon icon={faArrowLeft} />
                    {t('auth.back_to_site')}
                </Link>
            </main>
        </div>
    );
}
