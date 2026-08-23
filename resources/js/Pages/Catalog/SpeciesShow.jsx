import Card from '@/Components/Card';
import DeletionModal from '@/Components/DeletionModal';
import EmptyState from '@/Components/EmptyState';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import SpeciesFieldRecords from '@/Pages/Catalog/Partials/SpeciesFieldRecords';
import {
    faArrowLeft,
    faArrowUpRightFromSquare,
    faCircleCheck,
    faTrashCan,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, router } from '@inertiajs/react';
import axios from 'axios';
import { Fragment, useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

/**
 * One WFO name with its accepted-name status and, optionally, a "spelling
 * variant" flag. `full_name_html` already carries WFO's own italic markup.
 */
function WfoName({ name, highlight = false, canEdit = false, onAccept }) {
    const { t } = useTranslation();
    const canAccept = canEdit && name.wfo_id;

    return (
        <div
            className={
                highlight
                    ? 'border-primary/40 bg-primary/5 rounded-box border p-3'
                    : 'border-base-300 rounded-box border p-3'
            }
        >
            <div className="flex flex-wrap items-center gap-2">
                <span
                    className="text-base"
                    dangerouslySetInnerHTML={{ __html: name.full_name_html }}
                />
                {name.is_spelling_variant && (
                    <span className="badge badge-warning badge-sm">
                        {t('catalogs.wfo.spelling_variant')}
                    </span>
                )}
            </div>
            <div className="mt-1 text-sm">
                {name.is_accepted ? (
                    <span className="text-success font-medium">
                        {t('catalogs.wfo.is_accepted')}
                    </span>
                ) : name.accepted_name ? (
                    <span className="text-base-content/70">
                        {t('catalogs.wfo.accepted_as')}{' '}
                        <span
                            className="text-success"
                            dangerouslySetInnerHTML={{
                                __html: name.accepted_name.full_name_html,
                            }}
                        />
                    </span>
                ) : (
                    <span className="text-warning">
                        {t('catalogs.wfo.no_accepted')}
                    </span>
                )}
            </div>
            <div className="mt-2 flex flex-wrap gap-2">
                {name.stable_uri && (
                    <a
                        href={name.stable_uri}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="btn btn-ghost btn-xs"
                    >
                        <FontAwesomeIcon
                            icon={faArrowUpRightFromSquare}
                            className="mr-1"
                        />
                        {t('catalogs.view_on_wfo')}
                    </a>
                )}
                {canAccept && (
                    <button
                        type="button"
                        className="btn btn-outline btn-primary btn-xs"
                        onClick={() => onAccept(name.wfo_id, false)}
                    >
                        <FontAwesomeIcon
                            icon={faCircleCheck}
                            className="mr-1"
                        />
                        {t('catalogs.accept.use_this')}
                    </button>
                )}
                {canAccept && name.accepted_name && (
                    <button
                        type="button"
                        className="btn btn-ghost btn-xs"
                        onClick={() => onAccept(name.wfo_id, true)}
                    >
                        {t('catalogs.accept.use_accepted')}
                    </button>
                )}
            </div>
        </div>
    );
}

function TaxonomicMatch({ wfo, canEdit, onAccept }) {
    const { t } = useTranslation();
    const hasCandidates = wfo.candidates.length > 0;

    if (!wfo.match && !hasCandidates) {
        return <p className="text-error text-sm">{t('catalogs.not_found')}</p>;
    }

    return (
        <div className="space-y-4">
            <p className="text-base-content/60 text-sm">
                {t('catalogs.wfo.searched_for', { name: wfo.recorded })}
            </p>

            {wfo.match ? (
                <div className="space-y-2">
                    <p className="text-sm font-medium">
                        {t('catalogs.wfo.exact_match')}
                    </p>
                    <WfoName
                        name={wfo.match}
                        highlight
                        canEdit={canEdit}
                        onAccept={onAccept}
                    />
                </div>
            ) : (
                <p className="text-warning text-sm">
                    {t('catalogs.wfo.no_exact_match')}
                </p>
            )}

            {hasCandidates && (
                <div className="space-y-2">
                    <p className="text-sm font-medium">
                        {wfo.match
                            ? t('catalogs.wfo.other_names')
                            : t('catalogs.wfo.closest_names')}
                    </p>
                    <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                        {wfo.candidates.map((candidate, index) => (
                            <WfoName
                                key={candidate.wfo_id ?? index}
                                name={candidate}
                                highlight={!wfo.match && index === 0}
                                canEdit={canEdit}
                                onAccept={onAccept}
                            />
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

function AcceptNameModal({
    open,
    loading,
    error,
    preview,
    submitting,
    onConfirm,
    onCancel,
}) {
    const { t } = useTranslation();

    if (!open) return null;

    const labelFor = {
        family: 'catalogs.family',
        genus: 'catalogs.genus',
        name: 'catalogs.species',
        authority: 'catalogs.authority',
    };

    return (
        <dialog className="modal modal-open">
            <div className="modal-box">
                <h3 className="text-lg font-bold">
                    {t('catalogs.accept.title')}
                </h3>

                {loading ? (
                    <p className="text-base-content/70 py-4 text-sm">
                        {t('catalogs.loading')}
                    </p>
                ) : error ? (
                    <p className="text-error py-4 text-sm">{error}</p>
                ) : preview ? (
                    <div className="py-4">
                        <p className="text-base-content/60 mb-2 text-sm">
                            {t('catalogs.accept.summary')}
                        </p>
                        <div className="grid grid-cols-3 gap-x-2 gap-y-1 text-sm">
                            <span className="text-base-content/50 text-xs uppercase" />
                            <span className="text-base-content/50 text-xs uppercase">
                                {t('catalogs.accept.current')}
                            </span>
                            <span className="text-base-content/50 text-xs uppercase">
                                {t('catalogs.accept.proposed')}
                            </span>
                            {['family', 'genus', 'name', 'authority'].map(
                                (field) => {
                                    const current =
                                        preview.current[field] || '—';
                                    const proposed =
                                        preview.proposed[field] || '—';
                                    const changed = current !== proposed;

                                    return (
                                        <Fragment key={field}>
                                            <span className="text-base-content/60">
                                                {t(labelFor[field])}
                                            </span>
                                            <span
                                                className={
                                                    changed
                                                        ? 'text-base-content/50 line-through'
                                                        : ''
                                                }
                                            >
                                                {current}
                                            </span>
                                            <span
                                                className={
                                                    changed
                                                        ? 'text-success font-medium'
                                                        : ''
                                                }
                                            >
                                                {proposed}
                                            </span>
                                        </Fragment>
                                    );
                                },
                            )}
                        </div>
                    </div>
                ) : null}

                <div className="modal-action">
                    <button
                        type="button"
                        className="btn btn-ghost"
                        onClick={onCancel}
                        disabled={submitting}
                    >
                        {t('catalogs.accept.cancel')}
                    </button>
                    <button
                        type="button"
                        className="btn btn-primary"
                        onClick={onConfirm}
                        disabled={submitting || !preview}
                    >
                        {t('catalogs.accept.confirm')}
                    </button>
                </div>
            </div>
            <div className="modal-backdrop" onClick={onCancel} />
        </dialog>
    );
}

function LinkedRecords({ project, canViewData, linkedCount, linkedRecords }) {
    const { t, i18n } = useTranslation();

    const formatDate = (iso) => {
        if (!iso) return null;
        const date = new Date(iso);
        if (Number.isNaN(date.getTime())) return null;

        try {
            return date.toLocaleDateString(i18n.language);
        } catch {
            return date.toLocaleDateString();
        }
    };

    if (!canViewData) {
        return (
            <p className="text-base-content/70 text-sm">
                {t('catalogs.linked.count', { count: linkedCount })}
                {' — '}
                {t('catalogs.linked.gated')}
            </p>
        );
    }

    if (!linkedRecords || linkedRecords.data.length === 0) {
        return (
            <EmptyState
                title={t('catalogs.linked.none_title')}
                hint={t('catalogs.linked.none_hint')}
            />
        );
    }

    return (
        <div className="space-y-4">
            <p className="text-base-content/60 text-sm">
                {t('catalogs.linked.count', { count: linkedCount })}
            </p>

            <ul className="border-base-300 divide-base-300 rounded-box divide-y border">
                {linkedRecords.data.map((record) => {
                    const meta = [
                        record.recorder,
                        record.form?.name,
                        record.section?.name,
                        formatDate(record.recorded_at),
                    ]
                        .filter(Boolean)
                        .join(' · ');

                    return (
                        <li
                            key={record.id}
                            className="flex items-center justify-between gap-3 px-4 py-3"
                        >
                            <div className="min-w-0">
                                <span className="block truncate font-medium">
                                    {record.recorded_name}
                                </span>
                                {meta && (
                                    <span className="text-base-content/60 block truncate text-xs">
                                        {meta}
                                    </span>
                                )}
                            </div>
                            {record.form?.id && record.section?.id && (
                                <Link
                                    href={route('data.view', {
                                        project: project.id,
                                        form: record.form.id,
                                        section: record.section.id,
                                    })}
                                    className="btn btn-ghost btn-xs shrink-0"
                                >
                                    {t('catalogs.linked.view_in_data')}
                                    <FontAwesomeIcon
                                        icon={faArrowUpRightFromSquare}
                                    />
                                </Link>
                            )}
                        </li>
                    );
                })}
            </ul>

            {linkedRecords.links.length > 3 && (
                <div className="flex justify-center">
                    <div className="join">
                        {linkedRecords.links.map((link, index) => (
                            <Link
                                key={index}
                                href={link.url || '#'}
                                only={['linkedRecords']}
                                preserveState
                                preserveScroll
                                className={`join-item btn btn-sm ${
                                    link.active ? 'btn-primary' : 'btn-ghost'
                                } ${link.url ? '' : 'btn-disabled'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

function RegionChips({ label, regions, tone }) {
    if (regions.length === 0) return null;

    return (
        <div className="space-y-1">
            <p className="text-sm font-medium">
                {label} · {regions.length}
            </p>
            <div className="flex flex-wrap gap-1">
                {regions.map((region) => (
                    <span
                        key={(region.code ?? '') + region.name}
                        className={`badge badge-sm ${tone}`}
                    >
                        {region.name}
                    </span>
                ))}
            </div>
        </div>
    );
}

function DistributionCard({ distribution, loading, error, onRefresh }) {
    const { t } = useTranslation();

    if (loading) {
        return (
            <p className="text-base-content/70 text-sm">
                {t('catalogs.distribution.loading')}
            </p>
        );
    }

    if (error) {
        return <p className="text-error text-sm">{error}</p>;
    }

    if (!distribution) return null;

    const { matched, native, introduced } = distribution;
    const hasRange = matched && (native.length > 0 || introduced.length > 0);

    return (
        <div className="space-y-4">
            {!matched ? (
                <p className="text-warning text-sm">
                    {t('catalogs.distribution.no_match')}
                </p>
            ) : !hasRange ? (
                <p className="text-base-content/70 text-sm">
                    {t('catalogs.distribution.no_range')}
                </p>
            ) : (
                <>
                    <RegionChips
                        label={t('catalogs.distribution.native')}
                        regions={native}
                        tone="badge-success badge-outline"
                    />
                    <RegionChips
                        label={t('catalogs.distribution.introduced')}
                        regions={introduced}
                        tone="badge-warning badge-outline"
                    />
                </>
            )}
            <div className="flex items-center justify-between gap-2">
                <p className="text-base-content/50 text-xs">
                    {t('catalogs.distribution.source', {
                        source: distribution.source,
                    })}
                </p>
                <button
                    type="button"
                    className="btn btn-ghost btn-xs"
                    onClick={onRefresh}
                >
                    {t('catalogs.distribution.refresh')}
                </button>
            </div>
        </div>
    );
}

export default function SpeciesShow({
    species,
    project,
    linkedCount = 0,
    canViewData = false,
    linkedRecords = null,
    canEdit = false,
    distribution = null,
    fieldRecords = [],
}) {
    const { t } = useTranslation();
    const deletionModalRef = useRef();
    const [wfo, setWfo] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    // Accepting a WFO name: preview the change, then commit it.
    const [acceptTarget, setAcceptTarget] = useState(null);
    const [preview, setPreview] = useState(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [previewError, setPreviewError] = useState(null);
    const [submitting, setSubmitting] = useState(false);

    // Geographic range (WCVP via GBIF): served from cache when present, fetched
    // once on demand otherwise. Start in the loading state when there's no cache
    // so the effect's async fetch never sets state synchronously.
    const [dist, setDist] = useState(distribution);
    const [distLoading, setDistLoading] = useState(!distribution);
    const [distError, setDistError] = useState(null);

    const scientificName = `${species.genus} ${species.name}`;

    useEffect(() => {
        if (distribution) return undefined;

        let active = true;
        axios
            .post(
                route('catalogs.species.distribution', {
                    project: project.id,
                    species: species.id,
                }),
            )
            .then((response) => active && setDist(response.data))
            .catch(
                () => active && setDistError(t('catalogs.distribution.error')),
            )
            .finally(() => active && setDistLoading(false));

        return () => {
            active = false;
        };
        // Only the initial, uncached load; refresh is user-triggered.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [species.id]);

    const refreshDistribution = () => {
        setDistLoading(true);
        setDistError(null);
        axios
            .post(
                route('catalogs.species.distribution', {
                    project: project.id,
                    species: species.id,
                }),
            )
            .then((response) => setDist(response.data))
            .catch(() => setDistError(t('catalogs.distribution.error')))
            .finally(() => setDistLoading(false));
    };

    const openAccept = (wfoId, useAccepted) => {
        setAcceptTarget({ wfoId, useAccepted });
        setPreview(null);
        setPreviewError(null);
        setPreviewLoading(true);

        axios
            .post(
                route('catalogs.species.wfo-preview', {
                    project: project.id,
                    species: species.id,
                }),
                { wfo_id: wfoId, use_accepted: useAccepted },
            )
            .then((response) => setPreview(response.data))
            .catch(() => setPreviewError(t('catalogs.accept.preview_error')))
            .finally(() => setPreviewLoading(false));
    };

    const closeAccept = () => {
        setAcceptTarget(null);
        setPreview(null);
        setPreviewError(null);
    };

    const confirmAccept = () => {
        if (!acceptTarget) return;

        setSubmitting(true);
        router.patch(
            route('catalogs.species.update', {
                project: project.id,
                species: species.id,
            }),
            {
                wfo_id: acceptTarget.wfoId,
                use_accepted: acceptTarget.useAccepted,
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    setSubmitting(false);
                    closeAccept();
                },
            },
        );
    };

    useEffect(() => {
        let active = true;

        const fetchWfo = async () => {
            setLoading(true);
            setError(null);

            try {
                const response = await axios.post(route('wfo.query'), {
                    genus: species.genus,
                    name: species.name,
                    authority: species.authority ?? '',
                });

                if (active) {
                    setWfo(response.data);
                }
            } catch (err) {
                console.error('Error fetching WFO data:', err);
                if (active) {
                    setError(t('catalogs.fetch_error'));
                }
            } finally {
                if (active) {
                    setLoading(false);
                }
            }
        };

        fetchWfo();

        return () => {
            active = false;
        };
    }, [species.genus, species.name, species.authority, t]);

    return (
        <AuthenticatedLayout
            headTitle={`${scientificName} ${species.authority ?? ''}`.trim()}
            title={
                <>
                    <span className="italic">
                        {species.genus} {species.name}
                    </span>{' '}
                    {species.authority}
                </>
            }
            subtitle={`${t('catalogs.subtitle')} ${project.name}`}
            breadcrumbs={[
                {
                    label: t('navigation.catalogs'),
                    href: route('catalogs.index'),
                },
                {
                    label: project.name,
                    href: route('catalogs.show', { project: project.id }),
                },
                { label: `${species.genus} ${species.name}` },
            ]}
            action={
                <Link
                    href={route('catalogs.show', { project: project.id })}
                    className="btn btn-ghost btn-circle"
                    aria-label={t('navigation.back')}
                >
                    <FontAwesomeIcon icon={faArrowLeft} />
                </Link>
            }
            actionRight={
                <button
                    type="button"
                    className="btn btn-ghost text-error"
                    onClick={() => deletionModalRef.current.showModal()}
                >
                    <FontAwesomeIcon icon={faTrashCan} />
                    {t('actions.delete')}
                </button>
            }
        >
            <div className="space-y-4 p-4 md:pt-8 lg:pt-12">
                <div className="grid w-full grid-cols-1 gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <h2 className="card-title">
                            {t('catalogs.taxonomic_info')}
                        </h2>
                        {loading ? (
                            <p className="text-sm text-gray-500">
                                {t('catalogs.loading')}
                            </p>
                        ) : error ? (
                            <p className="text-error text-sm">{error}</p>
                        ) : wfo ? (
                            <TaxonomicMatch
                                wfo={wfo}
                                canEdit={canEdit}
                                onAccept={openAccept}
                            />
                        ) : (
                            <p className="text-error text-sm">
                                {t('catalogs.not_found')}
                            </p>
                        )}
                    </Card>

                    <Card>
                        <h2 className="card-title">
                            {t('catalogs.external_references')}
                        </h2>
                        <div className="flex flex-col gap-2">
                            <a
                                href={`http://www.worldfloraonline.org/search?query=${encodeURIComponent(
                                    scientificName,
                                )}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="btn btn-outline btn-sm justify-start"
                            >
                                <FontAwesomeIcon
                                    icon={faArrowUpRightFromSquare}
                                    className="mr-2"
                                />
                                WorldFloraOnline
                            </a>
                            <a
                                href={`https://www.tropicos.org/name/Search?name=${encodeURIComponent(
                                    scientificName,
                                )}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="btn btn-outline btn-sm justify-start"
                            >
                                <FontAwesomeIcon
                                    icon={faArrowUpRightFromSquare}
                                    className="mr-2"
                                />
                                Tropicos
                            </a>
                            <a
                                href={`https://www.gbif.org/species/search?q=${encodeURIComponent(
                                    scientificName,
                                )}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="btn btn-outline btn-sm justify-start"
                            >
                                <FontAwesomeIcon
                                    icon={faArrowUpRightFromSquare}
                                    className="mr-2"
                                />
                                GBIF
                            </a>
                        </div>
                    </Card>
                </div>

                <Card>
                    <h2 className="card-title">
                        {t('catalogs.distribution.title')}
                    </h2>
                    <DistributionCard
                        distribution={dist}
                        loading={distLoading}
                        error={distError}
                        onRefresh={refreshDistribution}
                    />
                </Card>

                <Card>
                    <h2 className="card-title">
                        {t('catalogs.fieldRecords.title')}
                    </h2>
                    <SpeciesFieldRecords
                        project={project}
                        species={species}
                        fieldRecords={fieldRecords}
                        canEdit={canEdit}
                    />
                </Card>

                <Card>
                    <h2 className="card-title">{t('catalogs.linked.title')}</h2>
                    <LinkedRecords
                        project={project}
                        canViewData={canViewData}
                        linkedCount={linkedCount}
                        linkedRecords={linkedRecords}
                    />
                </Card>
            </div>
            <DeletionModal
                modalRef={deletionModalRef}
                name={scientificName}
                url={route('catalogs.species.delete', {
                    project: project.id,
                    species: species.id,
                })}
            />
            <AcceptNameModal
                open={acceptTarget !== null}
                loading={previewLoading}
                error={previewError}
                preview={preview}
                submitting={submitting}
                onConfirm={confirmAccept}
                onCancel={closeAccept}
            />
        </AuthenticatedLayout>
    );
}
