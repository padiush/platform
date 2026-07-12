import Card from '@/Components/Card';
import EmptyState from '@/Components/EmptyState';
import FieldSummaryChart from '@/Components/FieldSummaryChart';
import Input from '@/Components/Input';
import Pagination from '@/Components/Pagination';
import Select from '@/Components/Select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatDateTime } from '@/utils/datetime';
import { faArrowLeft, faDownload } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

function Cell({ cell }) {
    if (!cell) {
        return <span className="text-base-content/30">—</span>;
    }

    if (cell.kind === 'multi') {
        return (
            <span className="flex flex-wrap gap-1">
                {cell.values.map((value, index) => (
                    <span key={index} className="badge badge-ghost badge-sm">
                        {value}
                    </span>
                ))}
            </span>
        );
    }

    if (cell.kind === 'species') {
        return <span className="italic">{cell.value}</span>;
    }

    return cell.value;
}

export default function DataView({
    project,
    forms,
    structure,
    rows,
    filters,
    interviewers,
    summary,
}) {
    const { t, i18n } = useTranslation();
    const { csrf_token } = usePage().props;

    const tab = filters.tab || 'table';

    const visit = (overrides = {}) => {
        const next = {
            form: filters.form,
            section: filters.section,
            interviewer: filters.interviewer,
            from: filters.from,
            to: filters.to,
            tab,
            ...overrides,
        };

        const params = { project: project.id };
        if (next.form) params.form = next.form;
        if (next.section) params.section = next.section;
        if (next.interviewer) params.interviewer = next.interviewer;
        if (next.from) params.from = next.from;
        if (next.to) params.to = next.to;
        if (next.tab && next.tab !== 'table') params.tab = next.tab;

        const only = ['rows', 'structure', 'filters', 'interviewers'];
        if (next.tab === 'summary') {
            only.push('summary');
        }

        router.get(
            route('data.view', params),
            {},
            {
                preserveScroll: true,
                replace: true,
                only,
            },
        );
    };

    const backAction = (
        <Link
            className="btn btn-ghost btn-circle"
            href={route('data.index')}
            aria-label={t('navigation.back')}
        >
            <FontAwesomeIcon icon={faArrowLeft} />
        </Link>
    );

    const layoutProps = {
        title: t('data.view.title'),
        breadcrumbs: [
            { label: t('navigation.data'), href: route('data.index') },
            { label: project.name },
        ],
        subtitle: project.name,
        action: backAction,
    };

    if (!structure) {
        return (
            <AuthenticatedLayout {...layoutProps}>
                <div className="p-4 md:pt-8 lg:pt-12">
                    <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <EmptyState
                            title={t('data.view.no_sections_title')}
                            hint={t('data.view.no_sections_hint')}
                        />
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    const section = structure.section;

    return (
        <AuthenticatedLayout {...layoutProps}>
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <Card title={t('data.view.title')}>
                        <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            {forms.length > 1 && (
                                <Select
                                    label={t('data.view.form')}
                                    value={filters.form}
                                    onChange={(e) =>
                                        visit({
                                            form: Number(e.target.value),
                                            section: null,
                                        })
                                    }
                                >
                                    {forms.map((form) => (
                                        <option key={form.id} value={form.id}>
                                            {form.name}
                                        </option>
                                    ))}
                                </Select>
                            )}
                            <Select
                                label={t('data.view.section')}
                                value={filters.section}
                                onChange={(e) =>
                                    visit({ section: Number(e.target.value) })
                                }
                            >
                                {structure.sections.map((s) => (
                                    <option key={s.id} value={s.id}>
                                        {s.name}
                                        {s.repeatable
                                            ? ` (${t('data.repeatable')})`
                                            : ''}
                                    </option>
                                ))}
                            </Select>
                        </div>

                        <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div className="join">
                                <button
                                    type="button"
                                    className={`btn btn-sm join-item ${tab === 'table' ? 'btn-primary' : 'btn-ghost'}`}
                                    onClick={() => visit({ tab: 'table' })}
                                >
                                    {t('data.view.tab_table')}
                                </button>
                                <button
                                    type="button"
                                    className={`btn btn-sm join-item ${tab === 'summary' ? 'btn-primary' : 'btn-ghost'}`}
                                    onClick={() => visit({ tab: 'summary' })}
                                >
                                    {t('data.view.tab_summary')}
                                </button>
                            </div>

                            <form
                                method="post"
                                action={route('data.custom', {
                                    project: project.id,
                                })}
                            >
                                <input
                                    type="hidden"
                                    name="_token"
                                    value={csrf_token}
                                />
                                <input
                                    type="hidden"
                                    name="form_id"
                                    value={structure.form_id}
                                />
                                <input
                                    type="hidden"
                                    name="selected_fields"
                                    value={JSON.stringify(
                                        section.items.map((item) => item.id),
                                    )}
                                />
                                <button
                                    type="submit"
                                    className="btn btn-outline btn-sm"
                                >
                                    <FontAwesomeIcon
                                        icon={faDownload}
                                        className="sm:mr-2"
                                    />
                                    <span className="hidden sm:inline">
                                        {t('data.view.export')}
                                    </span>
                                </button>
                            </form>
                        </div>

                        {tab === 'table' ? (
                            <>
                                <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <Select
                                        label={t('data.view.interviewer')}
                                        value={filters.interviewer ?? ''}
                                        onChange={(e) =>
                                            visit({
                                                interviewer:
                                                    e.target.value || null,
                                            })
                                        }
                                    >
                                        <option value="">
                                            {t('data.view.all_interviewers')}
                                        </option>
                                        {interviewers.map((user) => (
                                            <option
                                                key={user.id}
                                                value={user.id}
                                            >
                                                {user.name}
                                            </option>
                                        ))}
                                    </Select>
                                    <Input
                                        type="date"
                                        label={t('data.view.from')}
                                        value={filters.from ?? ''}
                                        onChange={(e) =>
                                            visit({
                                                from: e.target.value || null,
                                            })
                                        }
                                    />
                                    <Input
                                        type="date"
                                        label={t('data.view.to')}
                                        value={filters.to ?? ''}
                                        onChange={(e) =>
                                            visit({
                                                to: e.target.value || null,
                                            })
                                        }
                                    />
                                </div>

                                {rows.data.length > 0 ? (
                                    <>
                                        <div className="overflow-x-auto">
                                            <table className="table-sm table">
                                                <thead>
                                                    <tr>
                                                        <th>
                                                            {t(
                                                                'data.view.col_date',
                                                            )}
                                                        </th>
                                                        <th>
                                                            {t(
                                                                'data.view.col_recorder',
                                                            )}
                                                        </th>
                                                        {section.repeatable && (
                                                            <th>
                                                                {t(
                                                                    'data.view.col_record',
                                                                )}
                                                            </th>
                                                        )}
                                                        {section.items.map(
                                                            (item) => (
                                                                <th
                                                                    key={
                                                                        item.id
                                                                    }
                                                                >
                                                                    {item.label}
                                                                </th>
                                                            ),
                                                        )}
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {rows.data.map((row) => (
                                                        <tr key={row.key}>
                                                            <td className="whitespace-nowrap">
                                                                {formatDateTime(
                                                                    row
                                                                        .interview
                                                                        .recorded_at,
                                                                    i18n.language,
                                                                )}
                                                            </td>
                                                            <td className="whitespace-nowrap">
                                                                {row.interview
                                                                    .recorder ??
                                                                    '—'}
                                                            </td>
                                                            {section.repeatable && (
                                                                <td className="tabular-nums">
                                                                    {(row.record_index ??
                                                                        0) + 1}
                                                                </td>
                                                            )}
                                                            {section.items.map(
                                                                (item) => (
                                                                    <td
                                                                        key={
                                                                            item.id
                                                                        }
                                                                    >
                                                                        <Cell
                                                                            cell={
                                                                                row
                                                                                    .cells[
                                                                                    item
                                                                                        .id
                                                                                ]
                                                                            }
                                                                        />
                                                                    </td>
                                                                ),
                                                            )}
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                        <Pagination
                                            links={rows.links}
                                            className="mt-4"
                                        />
                                    </>
                                ) : (
                                    <EmptyState
                                        title={t('data.view.no_rows_title')}
                                        hint={t('data.view.no_rows_hint')}
                                    />
                                )}
                            </>
                        ) : summary === undefined ? (
                            <div className="text-base-content/50 flex h-40 items-center justify-center">
                                {t('data.view.loading')}
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                {summary.map((field) => (
                                    <Card
                                        key={field.item_id}
                                        title={field.label}
                                    >
                                        <FieldSummaryChart field={field} />
                                    </Card>
                                ))}
                            </div>
                        )}
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
