import Card from '@/Components/Card';
import EmptyState from '@/Components/EmptyState';
import Input from '@/Components/Input';
import Pagination from '@/Components/Pagination';
import Select from '@/Components/Select';
import SpeciesPickerModal from '@/Components/SpeciesPickerModal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatDateTime } from '@/utils/datetime';
import {
    faArrowLeft,
    faChevronDown,
    faChevronRight,
    faMagnifyingGlass,
} from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, router, usePage } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

// Shared column template so every row type — groups, expanded members and
// flat rows — lines up on the same boundaries. Fixed tracks (not fractions)
// keep columns aligned across rows, which each render as their own grid.
// Columns: gutter/chevron · reported name · status · species · action.
// The species column is dropped below `sm`, where the action track narrows.
const ROW_GRID =
    'grid grid-cols-[1.5rem_minmax(0,1fr)_3rem_7rem] items-center gap-3 sm:grid-cols-[1.5rem_minmax(0,1fr)_3rem_14rem_8rem]';

function StatusBadge({ linked, total, className = '' }) {
    // Group badge shows linked/total; a single row shows a linked/unlinked pill.
    if (total !== undefined) {
        return (
            <span
                className={`badge tabular-nums ${
                    linked >= total ? 'badge-success' : 'badge-ghost'
                } ${className}`}
            >
                {linked}/{total}
            </span>
        );
    }

    return (
        <span
            className={`badge ${linked ? 'badge-success' : 'badge-ghost'} ${className}`}
        >
            {linked ? '✓' : '—'}
        </span>
    );
}

function SpeciesLabel({ species, mixed, t }) {
    if (mixed) {
        return <span className="text-warning">{t('data.mixed')}</span>;
    }
    if (!species) {
        return <span className="text-base-content/40">—</span>;
    }

    return (
        <span className="truncate">
            <span className="italic">
                {species.genus} {species.name}
            </span>{' '}
            <span className="text-base-content/60">{species.authority}</span>
        </span>
    );
}

export default function LinkSpecies({ project, rows, filters, totals }) {
    const { t, i18n } = useTranslation();
    const { csrf_token } = usePage().props;

    const [q, setQ] = useState(filters.q || '');
    const [status, setStatus] = useState(filters.status || 'all');
    // `group` drives the row shape, so it reads from filters (which arrives
    // with the matching `rows` payload) rather than optimistic local state —
    // otherwise a toggle re-renders the new mode against the old data shape.
    const grouped = filters.group ?? true;
    const [expanded, setExpanded] = useState(() => new Set());

    const modalRef = useRef(null);
    const [target, setTarget] = useState(null);

    const visit = (next) => {
        const params = { project: project.id };
        if (next.q) params.q = next.q;
        if (next.status && next.status !== 'all') params.status = next.status;
        if (next.group === false) params.group = 0;

        router.get(
            route('data.link', params),
            {},
            {
                preserveScroll: true,
                replace: true,
                preserveState: true,
                only: ['rows', 'filters', 'totals'],
            },
        );
    };

    // Debounce free-text search; selects/toggle reload immediately on change.
    const [debouncedQ, setDebouncedQ] = useState(q);
    useEffect(() => {
        const timeout = setTimeout(() => {
            if (debouncedQ !== q) {
                setDebouncedQ(q);
                visit({ q, status, group: grouped });
            }
        }, 400);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [q, debouncedQ, status, grouped]);

    const toggleExpanded = (key) => {
        setExpanded((prev) => {
            const nextSet = new Set(prev);
            if (nextSet.has(key)) {
                nextSet.delete(key);
            } else {
                nextSet.add(key);
            }
            return nextSet;
        });
    };

    const openPicker = (nextTarget) => {
        setTarget(nextTarget);
        modalRef.current?.showModal();
    };

    const submitLink = async (species) => {
        const catalog_species_id = species ? species.id : null;
        const headers = { 'X-CSRF-TOKEN': csrf_token };

        try {
            if (target?.type === 'group') {
                await axios.post(
                    route('data.link.bulk', { project: project.id }),
                    {
                        catalog_species_id,
                        targets: target.members.map((m) => ({
                            interview_instance_id: m.instance_id,
                            interview_section_id: m.section_id,
                            repeatable_index: m.repeatable_index,
                        })),
                    },
                    { headers },
                );
            } else if (target?.type === 'row') {
                const row = target.row;
                await axios.post(
                    route('data.link.handle', { project: project.id }),
                    {
                        interview_instance_id: row.instance_id,
                        interview_section_id: row.section_id,
                        repeatable_index: row.repeatable_index,
                        catalog_species_id,
                    },
                    { headers },
                );
            }
            router.reload({ only: ['rows', 'totals'] });
        } catch (err) {
            console.error('Error linking species:', err);
            alert(t('data.link_error'));
        }
    };

    const interviewLabel = (interview) =>
        [
            t('interviews.instance_label'),
            formatDateTime(interview.recorded_at, i18n.language),
            interview.recorder,
        ]
            .filter(Boolean)
            .join(' · ');

    const renderMemberRow = (member) => (
        <div
            key={member.key}
            className={`border-base-300 border-t px-4 py-2 ${ROW_GRID}`}
        >
            <span aria-hidden="true" />
            <span className="text-base-content/70 min-w-0 truncate text-sm">
                {interviewLabel(member.interview)}
            </span>
            <StatusBadge
                linked={member.linked}
                className="justify-self-center"
            />
            <span className="hidden min-w-0 truncate text-sm sm:block">
                <SpeciesLabel species={member.species} t={t} />
            </span>
            <button
                type="button"
                className="btn btn-ghost btn-xs w-full"
                onClick={() =>
                    openPicker({
                        type: 'row',
                        row: member,
                        currentSpeciesId: member.species?.id ?? null,
                    })
                }
            >
                {member.linked ? t('data.change') : t('data.link_action')}
            </button>
        </div>
    );

    const renderGroup = (item) => {
        const isExpanded = expanded.has(item.key);
        const expandable = item.total > 1;

        return (
            <li key={item.key}>
                <div className={`px-4 py-3 ${ROW_GRID}`}>
                    {expandable ? (
                        <button
                            type="button"
                            className="btn btn-ghost btn-xs btn-circle justify-self-center"
                            aria-label={t('data.toggle_members')}
                            aria-expanded={isExpanded}
                            onClick={() => toggleExpanded(item.key)}
                        >
                            <FontAwesomeIcon
                                icon={
                                    isExpanded ? faChevronDown : faChevronRight
                                }
                            />
                        </button>
                    ) : (
                        <span aria-hidden="true" />
                    )}

                    <span className="min-w-0">
                        <span className="block truncate font-medium">
                            {item.name}
                        </span>
                        <span className="text-base-content/60 block truncate text-xs">
                            {expandable
                                ? t('data.interview_count', {
                                      count: item.total,
                                  })
                                : interviewLabel(item.members[0].interview)}
                        </span>
                    </span>

                    <StatusBadge
                        linked={item.linked_count}
                        total={item.total}
                        className="justify-self-center"
                    />

                    <span className="hidden min-w-0 truncate sm:block">
                        <SpeciesLabel
                            species={item.species}
                            mixed={item.mixed}
                            t={t}
                        />
                    </span>

                    <button
                        type="button"
                        className="btn btn-primary btn-sm w-full"
                        onClick={() =>
                            openPicker({
                                type: 'group',
                                members: item.members,
                                currentSpeciesId: item.species?.id ?? null,
                            })
                        }
                    >
                        {expandable
                            ? t('data.link_all')
                            : t('data.link_action')}
                    </button>
                </div>

                {expandable && isExpanded && (
                    <div className="bg-base-200/40">
                        {item.members.map(renderMemberRow)}
                    </div>
                )}
            </li>
        );
    };

    const renderFlatRow = (row) => (
        <li key={row.key}>
            <div className={`px-4 py-3 ${ROW_GRID}`}>
                <span aria-hidden="true" />
                <span className="min-w-0">
                    <span className="block truncate font-medium">
                        {row.reported_name}
                    </span>
                    <span className="text-base-content/60 block truncate text-xs">
                        {interviewLabel(row.interview)}
                    </span>
                </span>
                <StatusBadge
                    linked={row.linked}
                    className="justify-self-center"
                />
                <span className="hidden min-w-0 truncate sm:block">
                    <SpeciesLabel species={row.species} t={t} />
                </span>
                <button
                    type="button"
                    className="btn btn-primary btn-sm w-full"
                    onClick={() =>
                        openPicker({
                            type: 'row',
                            row,
                            currentSpeciesId: row.species?.id ?? null,
                        })
                    }
                >
                    {row.linked ? t('data.change') : t('data.link_action')}
                </button>
            </div>
        </li>
    );

    return (
        <AuthenticatedLayout
            title={t('data.title')}
            breadcrumbs={[
                { label: t('navigation.data'), href: route('data.index') },
                { label: project.name },
            ]}
            subtitle={project.name}
            action={
                <Link
                    className="btn btn-ghost btn-circle"
                    href={route('data.index')}
                    aria-label={t('navigation.back')}
                >
                    <FontAwesomeIcon icon={faArrowLeft} />
                </Link>
            }
        >
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <Card
                        title={t('data.title')}
                        actions={
                            <span className="flex items-center gap-2 text-sm">
                                <StatusBadge
                                    linked={totals.linked}
                                    total={totals.total}
                                />
                                <span className="text-base-content/60">
                                    {t('data.linked_of_total')}
                                </span>
                            </span>
                        }
                    >
                        <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div className="sm:col-span-2">
                                <Input
                                    name="q"
                                    value={q}
                                    placeholder={t('data.search_placeholder')}
                                    aria-label={t('data.search_placeholder')}
                                    leftAddon={
                                        <span className="bg-base-200 border-base-300 join-item flex items-center border px-3">
                                            <FontAwesomeIcon
                                                icon={faMagnifyingGlass}
                                                className="text-base-content/50"
                                            />
                                        </span>
                                    }
                                    onChange={(e) => setQ(e.target.value)}
                                />
                            </div>

                            <Select
                                label={t('data.status.label')}
                                value={status}
                                onChange={(e) => {
                                    setStatus(e.target.value);
                                    visit({
                                        q,
                                        status: e.target.value,
                                        group: grouped,
                                    });
                                }}
                            >
                                <option value="all">
                                    {t('data.status.all')}
                                </option>
                                <option value="unlinked">
                                    {t('data.status.unlinked')}
                                </option>
                                <option value="linked">
                                    {t('data.status.linked')}
                                </option>
                            </Select>

                            <div className="flex items-end pb-1">
                                <Input
                                    type="checkbox"
                                    label={t('data.group_by_name')}
                                    checked={grouped}
                                    onChange={(e) =>
                                        visit({
                                            q,
                                            status,
                                            group: e.target.checked,
                                        })
                                    }
                                />
                            </div>
                        </div>

                        {rows.data.length > 0 ? (
                            <>
                                <ul className="border-base-300 divide-base-300 rounded-box divide-y border">
                                    {grouped
                                        ? rows.data.map(renderGroup)
                                        : rows.data.map(renderFlatRow)}
                                </ul>
                                <Pagination
                                    links={rows.links}
                                    className="mt-4"
                                />
                            </>
                        ) : (
                            <EmptyState
                                title={t('data.no_matches_title')}
                                hint={t('data.no_matches_hint')}
                            />
                        )}
                    </Card>
                </div>
            </div>

            <SpeciesPickerModal
                modalRef={modalRef}
                project={project}
                currentSpeciesId={target?.currentSpeciesId ?? null}
                onPick={submitLink}
            />
        </AuthenticatedLayout>
    );
}
