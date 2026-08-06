import { useTranslation } from 'react-i18next';

/**
 * The platform's own report output, rendered natively rather than pasted in as
 * a screenshot: it stays sharp at any size, follows the theme, and the figures
 * remain selectable text.
 *
 * The numbers are the real result of running DemoProjectSeeder — a fictional
 * study with invented informants — so nothing here exposes a real interview.
 */

const SPECIES = [
    [
        'Citrus aurantiifolia',
        '(Christm.) Swingle',
        'Rutaceae',
        21,
        '0.88',
        '1.50',
        '1.50',
    ],
    ['Psidium guajava', 'L.', 'Myrtaceae', 21, '0.88', '1.42', '1.42'],
    ['Matricaria chamomilla', 'L.', 'Asteraceae', 16, '0.67', '0.67', '0.67'],
    ['Moringa oleifera', 'Lam.', 'Moringaceae', 14, '0.58', '0.92', '0.92'],
    ['Annona muricata', 'L.', 'Annonaceae', 13, '0.54', '0.79', '0.79'],
    [
        'Zingiber officinale',
        'Roscoe',
        'Zingiberaceae',
        13,
        '0.54',
        '0.83',
        '0.83',
    ],
];

const CATEGORY_KEYS = [
    'preview_cat_medicinal',
    'preview_cat_food',
    'preview_cat_construction',
    'preview_cat_ritual',
    'preview_cat_craft',
    'preview_cat_fuel',
];

// [species, [medicinal, food, construction, ritual, craft, fuel]]
const HEATMAP = [
    ['Citrus aurantiifolia', [20, 16, 0, 0, 0, 0]],
    ['Psidium guajava', [13, 21, 0, 0, 0, 0]],
    ['Moringa oleifera', [12, 10, 0, 0, 0, 0]],
    ['Matricaria chamomilla', [16, 0, 0, 0, 0, 0]],
    ['Tagetes erecta', [3, 0, 0, 11, 0, 0]],
    ['Bursera simaruba', [5, 0, 7, 2, 0, 0]],
    ['Cedrela odorata', [0, 0, 9, 0, 4, 0]],
    ['Cecropia obtusifolia', [7, 0, 0, 0, 0, 3]],
];

const PEAK = 21;

/**
 * Fill steps for the heatmap, darkest first. Each step pairs its own text
 * colour with its fill: a continuous opacity ramp looks tidier but puts dark
 * ink on the darkest cells, which is exactly where the numbers stop being
 * readable. daisyUI guarantees primary-content reads on primary in both themes.
 */
const LEVELS = [
    { from: 0.66, className: 'bg-primary text-primary-content' },
    { from: 0.33, className: 'bg-primary/45 text-base-content' },
    { from: 0.01, className: 'bg-primary/20 text-base-content' },
];

const levelFor = (count) =>
    LEVELS.find((level) => count / PEAK >= level.from)?.className ?? '';

export default function ProductPreview() {
    const { t } = useTranslation();

    return (
        // text-base-content is required, not decorative: the hero sets
        // text-primary-content on everything inside it, which is near-white and
        // vanishes against this card's light surface.
        <figure className="bg-base-100 border-base-300 text-base-content rounded-box overflow-hidden border shadow-xl">
            <div className="bg-base-200 border-base-300 flex items-center gap-2 border-b px-4 py-2.5">
                <span className="flex gap-1.5" aria-hidden="true">
                    <span className="bg-base-content/20 h-2.5 w-2.5 rounded-full" />
                    <span className="bg-base-content/20 h-2.5 w-2.5 rounded-full" />
                    <span className="bg-base-content/20 h-2.5 w-2.5 rounded-full" />
                </span>
                <span className="text-base-content/60 ml-2 truncate text-xs font-medium">
                    {t('public.preview_title')} · {t('public.preview_project')}
                </span>
            </div>

            <div className="space-y-5 p-4 md:p-5">
                <p className="text-base-content/50 text-xs">
                    {t('public.preview_meta')}
                </p>

                <div className="overflow-x-auto">
                    <table className="table-zebra table-xs table">
                        <thead>
                            <tr>
                                <th>{t('public.preview_species')}</th>
                                <th className="hidden sm:table-cell">
                                    {t('public.preview_family')}
                                </th>
                                <th className="text-right">
                                    {t('public.preview_citations')}
                                </th>
                                <th className="text-right">RFC</th>
                                <th className="text-right">UV</th>
                                <th className="text-right">CI</th>
                            </tr>
                        </thead>
                        <tbody>
                            {SPECIES.map(
                                ([
                                    name,
                                    authority,
                                    family,
                                    citations,
                                    rfc,
                                    uv,
                                    ci,
                                ]) => (
                                    <tr key={name}>
                                        <td className="whitespace-nowrap">
                                            <em>{name}</em>{' '}
                                            <span className="text-base-content/50">
                                                {authority}
                                            </span>
                                        </td>
                                        <td className="hidden sm:table-cell">
                                            {family}
                                        </td>
                                        <td className="text-right tabular-nums">
                                            {citations}
                                        </td>
                                        <td className="text-right tabular-nums">
                                            {rfc}
                                        </td>
                                        <td className="text-right tabular-nums">
                                            {uv}
                                        </td>
                                        <td className="text-right tabular-nums">
                                            {ci}
                                        </td>
                                    </tr>
                                ),
                            )}
                        </tbody>
                    </table>
                </div>

                <div>
                    <p className="text-base-content/60 mb-2 text-xs font-semibold">
                        {t('public.preview_heatmap')}
                    </p>
                    <Heatmap t={t} />
                </div>
            </div>
        </figure>
    );
}

function Heatmap({ t }) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full min-w-[28rem] border-separate border-spacing-0.5 text-[0.625rem]">
                <thead>
                    <tr>
                        <th />
                        {CATEGORY_KEYS.map((key) => (
                            <th
                                key={key}
                                className="text-base-content/60 px-1 pb-1 font-medium"
                            >
                                {t(`public.${key}`)}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {HEATMAP.map(([species, counts]) => (
                        <tr key={species}>
                            <th className="text-base-content/70 w-36 pr-2 text-right font-normal italic">
                                {species}
                            </th>
                            {counts.map((count, index) => (
                                <td
                                    key={index}
                                    className={`border-base-300/60 rounded border text-center ${levelFor(count)}`}
                                >
                                    <span className="px-1 leading-5 tabular-nums">
                                        {count || ''}
                                    </span>
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
