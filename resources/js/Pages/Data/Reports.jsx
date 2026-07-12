import Card from '@/Components/Card';
import EmptyState from '@/Components/EmptyState';
import MetricCard from '@/Components/MetricCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { faArrowLeft, faDownload } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

const ratio = (value) => Number(value).toFixed(2);
const percent = (value) => `${Number(value).toFixed(1)}%`;

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
        <div className="dropdown">
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
                <div className="flex items-center gap-2">
                    {hasData && download}
                    <Link
                        href={route('data.index')}
                        className="btn btn-ghost btn-circle"
                        aria-label={t('navigation.back')}
                    >
                        <FontAwesomeIcon icon={faArrowLeft} />
                    </Link>
                </div>
            }
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
                                                        {ratio(entry.rfc)}
                                                    </td>
                                                    <td className="text-right tabular-nums">
                                                        {ratio(entry.uv)}
                                                    </td>
                                                    <td className="text-right tabular-nums">
                                                        {ratio(entry.ci)}
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
                        </>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
