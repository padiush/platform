import { useTranslation } from 'react-i18next';

const LANGUAGES = ['es', 'en', 'pt'];

/** Whether a deployment has any store listing to show. */
export function hasStoreBadges(stores) {
    return Boolean(
        stores?.app_store ||
        stores?.play_store ||
        stores?.play_store_coming_soon,
    );
}

/**
 * The field app's store badges, as the public site shows them: Apple's badge
 * artwork, unaltered and in the reader's language (black on the light theme,
 * white on the dark one, as Apple allows), and Google's once the Play listing
 * is public. Until then a release can only be announced in plain text, since
 * Google's badge may only point at a live listing.
 *
 * Renders nothing for a deployment that publishes no store build.
 *
 * @param {{stores: {app_store: ?string, play_store: ?string, play_store_coming_soon: boolean} | null, className?: string}} props
 */
export default function StoreBadges({ stores, className = '' }) {
    const { t, i18n } = useTranslation();
    const language = LANGUAGES.includes(i18n.language) ? i18n.language : 'es';

    if (!hasStoreBadges(stores)) {
        return null;
    }

    const appStore = stores.app_store;
    const playStore = stores.play_store;
    const playSoon = !playStore && stores.play_store_coming_soon;

    return (
        <div className={`flex flex-wrap items-center gap-3 ${className}`}>
            {appStore && (
                <a href={appStore} rel="noopener" className="inline-block">
                    <img
                        src={`/images/badges/app-store-${language}-black.svg`}
                        alt={t('public.app_store')}
                        width="120"
                        height="40"
                        className="block h-10 w-auto [[data-theme='padiushdark']_&]:hidden"
                    />
                    <img
                        src={`/images/badges/app-store-${language}-white.svg`}
                        alt=""
                        aria-hidden="true"
                        width="120"
                        height="40"
                        className="hidden h-10 w-auto [[data-theme='padiushdark']_&]:block"
                    />
                </a>
            )}
            {playStore && (
                // Google's artwork carries its own clear space, so it is drawn
                // taller to give the badge itself the App Store's 40px.
                <a href={playStore} rel="noopener" className="inline-block">
                    <img
                        src={`/images/badges/google-play-${language}.png`}
                        alt={t('public.google_play')}
                        width="138"
                        height="53"
                        className="-my-1.5 block h-[53px] w-auto"
                    />
                </a>
            )}
            {playSoon && (
                <span className="border-base-content/30 text-base-content/70 inline-flex h-10 items-center rounded-[0.4rem] border border-dashed px-3 text-sm font-medium">
                    {t('public.play_soon')}
                </span>
            )}
        </div>
    );
}
