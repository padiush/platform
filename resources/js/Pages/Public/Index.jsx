import PublicLayout from '@/Layouts/PublicLayout';
import { primaryCtaTarget } from '@/lib/publicCta';
import { faCheck } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import CaptureDevice from './Partials/CaptureDevice';
import Screenshot from './Partials/Screenshot';

const INDICES = ['FC', 'RFC', 'NU', 'UV', 'CI', 'RI', 'CV', 'ICF', 'FL'];

export default function Index() {
    const { auth, registrationEnabled } = usePage().props;
    const { t } = useTranslation();

    const CTA = {
        dashboard: {
            href: () => route('dashboard'),
            label: 'public.enter_platform',
        },
        register: {
            href: () => route('register'),
            label: 'public.cta_create_account',
        },
        contact: {
            href: () => route('public.contact'),
            label: 'public.cta_request_access',
        },
    };

    const cta =
        CTA[
            primaryCtaTarget({
                signedIn: Boolean(auth?.user),
                registrationEnabled: Boolean(registrationEnabled),
            })
        ];
    const primaryCta = { href: cta.href(), label: t(cta.label) };

    return (
        <PublicLayout title="Padiush">
            <section className="from-primary to-primary/80 text-primary-content bg-gradient-to-br">
                <div className="mx-auto grid max-w-7xl items-center gap-10 px-6 py-16 lg:grid-cols-2 lg:gap-14 lg:py-24">
                    <div>
                        <h1 className="text-3xl leading-tight font-bold text-balance md:text-5xl">
                            {t('public.hero_title')}
                        </h1>
                        <p className="text-primary-content/85 mt-5 text-lg md:text-xl">
                            {t('public.hero_subtitle')}
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Link
                                href={primaryCta.href}
                                className="btn btn-lg [--btn-bg:var(--color-base-100)] [--btn-border:var(--color-base-100)] [--btn-fg:var(--color-base-content)]"
                            >
                                {primaryCta.label}
                            </Link>
                            <a
                                href="#how-it-works"
                                /* daisyUI's .btn sets `color: var(--btn-fg)`,
                                   which beats a plain text-* utility — so the
                                   colours have to be set through its own
                                   variables or the button silently keeps the
                                   default base-content and disappears against
                                   the primary hero. */
                                className="btn btn-lg [--btn-bg:transparent] [--btn-border:var(--color-primary-content)] [--btn-fg:var(--color-primary-content)]"
                            >
                                {t('public.cta_how_it_works')}
                            </a>
                        </div>
                        <p className="text-primary-content/70 mt-6 text-sm">
                            {t('public.hero_note')}
                        </p>
                    </div>

                    {/* min-w-0 lets the grid column shrink below the preview's
                        content width, so the wide tables scroll inside their
                        own box instead of stretching the page. */}
                    <div className="min-w-0">
                        <Screenshot name="reports" chrome />
                        <p className="text-primary-content/70 mt-3 text-center text-xs">
                            {t('public.preview_caption')}
                        </p>
                    </div>
                </div>
            </section>

            <section
                id="how-it-works"
                className="mx-auto max-w-7xl scroll-mt-20 px-6 py-16 md:py-24"
            >
                <div className="mx-auto max-w-2xl text-center">
                    <h2 className="text-base-content text-3xl font-bold md:text-4xl">
                        {t('public.workflow_title')}
                    </h2>
                    <p className="text-base-content/70 mt-4 text-lg">
                        {t('public.workflow_subtitle')}
                    </p>
                </div>

                <ol className="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    {[1, 2, 3, 4].map((step) => (
                        <li
                            key={step}
                            className="border-base-300 bg-base-100 rounded-box border p-6"
                        >
                            <span className="bg-primary text-primary-content flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold">
                                {step}
                            </span>
                            <h3 className="text-base-content mt-4 text-lg font-semibold">
                                {t(`public.workflow_${step}_title`)}
                            </h3>
                            <p className="text-base-content/70 mt-2 text-sm">
                                {t(`public.workflow_${step}_desc`)}
                            </p>
                        </li>
                    ))}
                </ol>
            </section>

            <Capability
                kicker={t('public.capture_kicker')}
                title={t('public.capture_title')}
                description={t('public.capture_desc')}
                points={[1, 2, 3, 4].map((n) => t(`public.capture_point_${n}`))}
                visual={<CaptureDevice />}
                tinted
            />

            <Capability
                kicker={t('public.taxonomy_kicker')}
                title={t('public.taxonomy_title')}
                description={t('public.taxonomy_desc')}
                points={[1, 2, 3, 4].map((n) =>
                    t(`public.taxonomy_point_${n}`),
                )}
                visual={<Screenshot name="catalog" chrome />}
                reversed
            />

            <Capability
                kicker={t('public.analysis_kicker')}
                title={t('public.analysis_title')}
                description={t('public.analysis_desc')}
                points={[1, 2, 3, 4].map((n) =>
                    t(`public.analysis_point_${n}`),
                )}
                tinted
                visual={<Screenshot name="sankey" />}
                extra={
                    <div className="mt-6">
                        <p className="text-base-content/50 text-[0.6875rem] font-semibold tracking-[0.16em] uppercase">
                            {t('public.analysis_indices_label')}
                        </p>
                        <div className="mt-3 flex flex-wrap gap-2">
                            {INDICES.map((index) => (
                                <span
                                    key={index}
                                    className="border-primary/30 bg-primary/10 text-base-content rounded-full border px-3 py-1 font-mono text-sm"
                                >
                                    {index}
                                </span>
                            ))}
                        </div>
                    </div>
                }
            />

            <section className="mx-auto max-w-7xl px-6 py-16 md:py-24">
                <h2 className="text-base-content text-center text-3xl font-bold md:text-4xl">
                    {t('public.trust_title')}
                </h2>
                <div className="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    {[1, 2, 3, 4].map((n) => (
                        <div key={n}>
                            <h3 className="text-base-content border-primary border-l-4 pl-3 text-lg font-semibold">
                                {t(`public.trust_${n}_title`)}
                            </h3>
                            <p className="text-base-content/70 mt-3 text-sm">
                                {t(`public.trust_${n}_desc`)}
                            </p>
                        </div>
                    ))}
                </div>
            </section>

            <section className="bg-primary text-primary-content">
                <div className="mx-auto flex max-w-3xl flex-col items-center gap-5 px-6 py-16 text-center md:py-20">
                    <h2 className="text-3xl font-bold md:text-4xl">
                        {t('public.final_cta_title')}
                    </h2>
                    <p className="text-primary-content/85 text-lg">
                        {t('public.final_cta_desc')}
                    </p>
                    <Link
                        href={primaryCta.href}
                        className="btn btn-lg bg-base-100 text-base-content hover:bg-base-200 border-none"
                    >
                        {primaryCta.label}
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}

function Capability({
    kicker,
    title,
    description,
    points,
    visual,
    extra = null,
    reversed = false,
    tinted = false,
}) {
    return (
        <section className={tinted ? 'bg-base-200' : ''}>
            <div className="mx-auto grid max-w-7xl items-center gap-10 px-6 py-16 md:py-24 lg:grid-cols-2 lg:gap-16">
                <div className={reversed ? 'lg:order-2' : ''}>
                    <p className="text-primary text-xs font-semibold tracking-[0.18em] uppercase">
                        {kicker}
                    </p>
                    <h2 className="text-base-content mt-3 text-3xl font-bold md:text-4xl">
                        {title}
                    </h2>
                    <p className="text-base-content/70 mt-4 text-lg">
                        {description}
                    </p>
                    <ul className="mt-6 space-y-3">
                        {points.map((point) => (
                            <li key={point} className="flex gap-3">
                                <FontAwesomeIcon
                                    icon={faCheck}
                                    className="text-primary mt-1 shrink-0"
                                />
                                <span className="text-base-content/80">
                                    {point}
                                </span>
                            </li>
                        ))}
                    </ul>
                    {extra}
                </div>
                <div className={`min-w-0 ${reversed ? 'lg:order-1' : ''}`}>
                    {visual}
                </div>
            </div>
        </section>
    );
}
