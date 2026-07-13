import Card from '@/Components/Card';
import ChartCard from '@/Components/ChartCard';
import EmptyState from '@/Components/EmptyState';
import MetricCard from '@/Components/MetricCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { faArrowLeft, faDownload } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import IndexBarChart from './Partials/IndexBarChart';
import UseHeatmap from './Partials/UseHeatmap';
import UsesSankey from './Partials/UsesSankey';

const ratio = (value) => Number(value).toFixed(2);
const percent = (value) => `${Number(value).toFixed(1)}%`;

const ETHNOBOTANYR_URL = 'https://CRAN.R-project.org/package=ethnobotanyR';

// The primary paper that defines each index — language-independent citations, so
// they live here rather than in the locale files (the framing prose is i18n'd).
const CITATIONS = [
    {
        abbr: 'RFC',
        nameKey: 'data.reports.rfc_full',
        source: 'Tardío, J. & Pardo-de-Santayana, M. (2008). Cultural importance indices: a comparative analysis. Economic Botany 62(1), 24–39.',
    },
    {
        abbr: 'NU',
        nameKey: 'data.reports.nu_full',
        source: 'Prance, G. T., Balée, W., Boom, B. M. & Carneiro, R. L. (1987). Quantitative ethnobotany and the case for conservation in Amazonia. Conservation Biology 1(4), 296–310.',
    },
    {
        abbr: 'UV',
        nameKey: 'data.reports.uv_full',
        source: 'Phillips, O. & Gentry, A. H. (1993). The useful plants of Tambopata, Peru. Economic Botany 47(1), 15–32.',
    },
    {
        abbr: 'CI',
        nameKey: 'data.reports.ci_full',
        source: 'Tardío, J. & Pardo-de-Santayana, M. (2008). Cultural importance indices: a comparative analysis. Economic Botany 62(1), 24–39.',
    },
    {
        abbr: 'RI',
        nameKey: 'data.reports.ri_full',
        source: 'Tardío, J. & Pardo-de-Santayana, M. (2008). Cultural importance indices: a comparative analysis. Economic Botany 62(1), 24–39.',
    },
    {
        abbr: 'CV',
        nameKey: 'data.reports.cv_full',
        source: 'Reyes-García, V., Huanca, T., Vadez, V. & Leonard, W. (2006). Cultural, practical and economic value of wild plants: a quantitative study in the Bolivian Amazon. Economic Botany 60(1), 62–74.',
    },
    {
        abbr: 'ICF',
        nameKey: 'data.reports.icf_full',
        source: 'Trotter, R. T. & Logan, M. H. (1986). Informant consensus. In: Plants in Indigenous Medicine and Diet. Redgrave.',
    },
    {
        abbr: 'FL',
        nameKey: 'data.reports.fl_full',
        source: 'Friedman, J., Yaniv, Z., Dafni, A. & Palewitch, D. (1986). A preliminary classification of the healing potential of medicinal plants. Journal of Ethnopharmacology 16, 275–287.',
    },
];

/** Italic binomial + plain authority. */
function ScientificName({ species }) {
    return (
        <span>
            <em>
                {species.genus} {species.name}
            </em>
            {species.authority ? (
                <span className="opacity-70"> {species.authority}</span>
            ) : null}
        </span>
    );
}

export default function Reports({ project, indices }) {
    const { t } = useTranslation();

    const {
        informants,
        species,
        use_categories: useCategories,
        unlinked_citations: unlinked,
    } = indices;

    const hasData = informants > 0 && species.length > 0;

    const fidelityRows = species.flatMap((entry) =>
        entry.fidelity.map((fidelity) => ({
            species: entry.species,
            ...fidelity,
        })),
    );

    const download = (
        <div className="dropdown dropdown-end">
            <div tabIndex={0} role="button" className="btn btn-primary btn-sm">
                <FontAwesomeIcon icon={faDownload} />
                {t('data.reports.download')}
            </div>
            <ul
                tabIndex={0}
                className="dropdown-content menu bg-base-200 rounded-box z-10 w-52 p-2 shadow"
            >
                <li>
                    <a
                        href={route('data.reports.download', {
                            project: project.id,
                            format: 'xlsx',
                        })}
                    >
                        {t('data.reports.format_xlsx')}
                    </a>
                </li>
                <li>
                    <a
                        href={route('data.reports.download', {
                            project: project.id,
                            format: 'csv',
                        })}
                    >
                        {t('data.reports.format_csv')}
                    </a>
                </li>
            </ul>
        </div>
    );

    return (
        <AuthenticatedLayout
            title={t('data.reports.title')}
            breadcrumbs={[
                { label: t('navigation.data'), href: route('data.index') },
                { label: project.name },
            ]}
            subtitle={t('data.reports.subtitle')}
            action={
                <Link
                    href={route('data.index')}
                    className="btn btn-ghost btn-circle"
                    aria-label={t('navigation.back')}
                >
                    <FontAwesomeIcon icon={faArrowLeft} />
                </Link>
            }
            actionRight={hasData && download}
        >
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <MetricCard
                            label={t('data.reports.informants')}
                            value={informants}
                            tone="primary"
                        />
                        <MetricCard
                            label={t('data.reports.unlinked')}
                            value={unlinked}
                            tone={unlinked > 0 ? 'warning' : 'default'}
                        />
                    </div>

                    {!hasData && (
                        <EmptyState
                            title={t('data.reports.empty.title')}
                            hint={t('data.reports.empty.hint')}
                        />
                    )}

                    {hasData && (
                        <>
                            <Card title={t('data.reports.species_indices')}>
                                <div className="overflow-x-auto">
                                    <table className="table w-full">
                                        <thead>
                                            <tr>
                                                <th>
                                                    {t('data.reports.species')}
                                                </th>
                                                <th className="hidden md:table-cell">
                                                    {t('data.reports.family')}
                                                </th>
                                                <th
                                                    className="text-right"
                                                    title={t(
                                                        'data.reports.fc_full',
                                                    )}
                                                >
                                                    {t('data.reports.fc')}
                                                </th>
                                                <th
                                                    className="text-right"
                                                    title={t(
                                                        'data.reports.nu_full',
                                                    )}
                                                >
                                                    NU
                                                </th>
                                                <th
                                                    className="text-right"
                                                    title={t(
                                                        'data.reports.rfc_full',
                                                    )}
                                                >
                                                    RFC
                                                </th>
                                                <th
                                                    className="text-right"
                                                    title={t(
                                                        'data.reports.uv_full',
                                                    )}
                                                >
                                                    UV
                                                </th>
                                                <th
                                                    className="text-right"
                                                    title={t(
                                                        'data.reports.ci_full',
                                                    )}
                                                >
                                                    CI
                                                </th>
                                                <th
                                                    className="text-right"
                                                    title={t(
                                                        'data.reports.ri_full',
                                                    )}
                                                >
                                                    RI
                                                </th>
                                                <th
                                                    className="text-right"
                                                    title={t(
                                                        'data.reports.cv_full',
                                                    )}
                                                >
                                                    CV
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {species.map((entry) => (
                                                <tr key={entry.species.id}>
                                                    <td className="text-wrap">
                                                        <ScientificName
                                                            species={
                                                                entry.species
                                                            }
                                                        />
                                                    </td>
                                                    <td className="hidden md:table-cell">
                                                        {entry.species.family}
                                                    </td>
                                                    <td className="text-right tabular-nums">
                                                        {entry.fc}
                                                    </td>
                                                    <td className="text-right tabular-nums">
                                                        {entry.nu}
                                                    </td>
                                                    <td className="text-right tabular-nums">
                                                        {ratio(entry.rfc)}
                                                    </td>
                                                    <td className="text-right tabular-nums">
                                                        {ratio(entry.uv)}
                                                    </td>
                                                    <td className="text-right tabular-nums">
                                                        {ratio(entry.ci)}
                                                    </td>
                                                    <td className="text-right tabular-nums">
                                                        {ratio(entry.ri)}
                                                    </td>
                                                    <td className="text-right tabular-nums">
                                                        {ratio(entry.cv)}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </Card>

                            <Card title={t('data.reports.use_categories')}>
                                <div className="overflow-x-auto">
                                    <table className="table w-full">
                                        <thead>
                                            <tr>
                                                <th>
                                                    {t(
                                                        'data.reports.use_category',
                                                    )}
                                                </th>
                                                <th
                                                    className="text-right"
                                                    title={t(
                                                        'data.reports.n_ur_full',
                                                    )}
                                                >
                                                    {t('data.reports.n_ur')}
                                                </th>
                                                <th className="text-right">
                                                    {t('data.reports.n_taxa')}
                                                </th>
                                                <th
                                                    className="text-right"
                                                    title={t(
                                                        'data.reports.icf_full',
                                                    )}
                                                >
                                                    ICF
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {useCategories.map((entry) => (
                                                <tr key={entry.use_category}>
                                                    <td className="text-wrap">
                                                        {entry.use_category}
                                                    </td>
                                                    <td className="text-right tabular-nums">
                                                        {entry.n_ur}
                                                    </td>
                                                    <td className="text-right tabular-nums">
                                                        {entry.n_taxa}
                                                    </td>
                                                    <td className="text-right tabular-nums">
                                                        {entry.icf === null
                                                            ? '—'
                                                            : ratio(entry.icf)}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </Card>

                            {fidelityRows.length > 0 && (
                                <Card title={t('data.reports.fidelity')}>
                                    <div className="overflow-x-auto">
                                        <table className="table w-full">
                                            <thead>
                                                <tr>
                                                    <th>
                                                        {t(
                                                            'data.reports.species',
                                                        )}
                                                    </th>
                                                    <th>
                                                        {t(
                                                            'data.reports.use_category',
                                                        )}
                                                    </th>
                                                    <th
                                                        className="text-right"
                                                        title={t(
                                                            'data.reports.fl_full',
                                                        )}
                                                    >
                                                        FL
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {fidelityRows.map((row) => (
                                                    <tr
                                                        key={`${row.species.id}-${row.use_category}`}
                                                    >
                                                        <td className="text-wrap">
                                                            <ScientificName
                                                                species={
                                                                    row.species
                                                                }
                                                            />
                                                        </td>
                                                        <td className="text-wrap">
                                                            {row.use_category}
                                                        </td>
                                                        <td className="text-right tabular-nums">
                                                            {percent(row.value)}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </Card>
                            )}

                            <ChartCard
                                title={t('data.reports.charts.by_index')}
                                filename="species-by-index"
                            >
                                <IndexBarChart species={species} />
                            </ChartCard>

                            <ChartCard
                                title={t('data.reports.charts.heatmap')}
                                filename="species-use-heatmap"
                            >
                                <UseHeatmap
                                    species={species}
                                    useCategories={useCategories}
                                />
                            </ChartCard>

                            <ChartCard
                                title={t('data.reports.charts.sankey')}
                                filename="species-use-flows"
                            >
                                <UsesSankey
                                    species={species}
                                    useCategories={useCategories}
                                />
                            </ChartCard>

                            <Card title={t('data.reports.references')}>
                                <p className="text-sm opacity-80">
                                    {t('data.reports.references_intro')}
                                </p>
                                <ul className="mt-3 space-y-3 text-sm">
                                    {CITATIONS.map((citation) => (
                                        <li key={citation.abbr}>
                                            <span className="font-semibold">
                                                {citation.abbr}
                                            </span>
                                            <span className="opacity-70">
                                                {' '}
                                                — {t(citation.nameKey)}
                                            </span>
                                            <div className="opacity-80">
                                                {citation.source}
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                                <p className="mt-4 text-sm opacity-80">
                                    <a
                                        href={ETHNOBOTANYR_URL}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="link font-medium"
                                    >
                                        ethnobotanyR
                                    </a>
                                    {' — '}
                                    {t('data.reports.ethnobotanyr_note')}
                                </p>
                            </Card>
                        </>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
