import { useTranslation } from 'react-i18next';

/**
 * A captured screenshot of the running platform, in a window frame.
 *
 * Two files are shipped per shot and CSS picks the one matching the reader's
 * theme — a bright screenshot dropped into a dark page looks like a mistake.
 * Both are decoded either way, which is the cost of doing this without
 * JavaScript; they are small WebP and lazily loaded.
 *
 * Everything pictured comes from DemoProjectSeeder, so no real interview data
 * appears here. See scripts/capture-screenshots.mjs.
 */

const SIZES = {
    reports: [2880, 1800],
    catalog: [2880, 1800],
    sankey: [2432, 1282],
};

export default function Screenshot({ name, chrome = false, className = '' }) {
    const { t } = useTranslation();
    const [width, height] = SIZES[name];
    const alt = t(`public.shot_${name}_alt`);

    return (
        <figure
            className={`bg-base-100 border-base-300 rounded-box overflow-hidden border shadow-xl ${className}`}
        >
            {chrome && (
                <div className="bg-base-200 border-base-300 flex items-center gap-1.5 border-b px-4 py-2.5">
                    <span className="bg-base-content/20 h-2.5 w-2.5 rounded-full" />
                    <span className="bg-base-content/20 h-2.5 w-2.5 rounded-full" />
                    <span className="bg-base-content/20 h-2.5 w-2.5 rounded-full" />
                </div>
            )}
            <img
                src={`/images/site/${name}-light.webp`}
                alt={alt}
                width={width}
                height={height}
                loading="lazy"
                decoding="async"
                className="block h-auto w-full [[data-theme='padiushdark']_&]:hidden"
            />
            <img
                src={`/images/site/${name}-dark.webp`}
                alt=""
                aria-hidden="true"
                width={width}
                height={height}
                loading="lazy"
                decoding="async"
                className="hidden h-auto w-full [[data-theme='padiushdark']_&]:block"
            />
        </figure>
    );
}
