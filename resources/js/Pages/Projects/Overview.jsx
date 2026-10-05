import Card from '@/Components/Card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatDateTime } from '@/utils/datetime';
import {
    faChevronRight,
    faCircleCheck,
    faClipboardQuestion,
    faCloudArrowUp,
    faDownload,
    faLink,
    faMagnifyingGlass,
    faPenRuler,
    faSeedling,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

/** One figure, opening the section it counts when the user can go there. */
function Stat({ label, value, href }) {
    const body = (
        <>
            <div className="stat-title whitespace-normal">{label}</div>
            <div className="stat-value text-2xl tabular-nums">{value}</div>
        </>
    );

    return href ? (
        <Link href={href} className="stat hover:bg-base-200/60 transition">
            {body}
        </Link>
    ) : (
        <div className="stat">{body}</div>
    );
}

/** Something that needs doing, with where to do it. */
function WaitingItem({ icon, children, href }) {
    const body = (
        <>
            <FontAwesomeIcon
                icon={icon}
                className="text-warning w-4 shrink-0"
                fixedWidth
            />
            <span className="min-w-0 grow">{children}</span>
            {href && (
                <FontAwesomeIcon
                    icon={faChevronRight}
                    className="text-base-content/40 shrink-0 text-xs"
                />
            )}
        </>
    );

    return (
        <li>
            {href ? (
                <Link
                    href={href}
                    className="hover:bg-base-200/60 rounded-field flex items-center gap-3 px-2 py-2 transition"
                >
                    {body}
                </Link>
            ) : (
                <div className="flex items-center gap-3 px-2 py-2">{body}</div>
            )}
        </li>
    );
}

/** How a record reads in a short list: its name, else its number. */
function recordLabel(record, t) {
    return (
        record.vernacular_name ||
        record.accession_number ||
        record.collection_number ||
        t('overview.unnamed_record')
    );
}

/**
 * A project at a glance: how much has been gathered, what was added lately,
 * and what is still waiting on someone. The sidebar's project opens here.
 *
 * Every part appears only for the roles that can act on it, so a member who
 * reads the catalog and a member who records interviews see different
 * overviews of the same project.
 */
export default function Overview({
    project,
    counts,
    waiting,
    recentInterviews = null,
    recentRecords = null,
    sections = {},
    can = {},
}) {
    const { t, i18n } = useTranslation();

    const recordsUrl = sections.records;
    const waitingItems = [
        waiting.pending_media > 0 && (
            <WaitingItem key="media" icon={faCloudArrowUp}>
                {t('overview.pending_media', { count: waiting.pending_media })}
            </WaitingItem>
        ),
        waiting.undetermined_records > 0 && (
            <WaitingItem
                key="undetermined"
                icon={faMagnifyingGlass}
                href={recordsUrl && `${recordsUrl}?filter=undetermined`}
            >
                {t('overview.undetermined_records', {
                    count: waiting.undetermined_records,
                })}
            </WaitingItem>
        ),
        waiting.unlinked_answers > 0 && (
            <WaitingItem
                key="unlinked"
                icon={faLink}
                href={
                    can.link_species
                        ? route('data.link', project.id)
                        : undefined
                }
            >
                {t('overview.unlinked_answers', {
                    count: waiting.unlinked_answers,
                })}
            </WaitingItem>
        ),
    ].filter(Boolean);

    const shortcuts = [
        sections.interviews && {
            key: 'interview',
            href: sections.interviews,
            icon: faClipboardQuestion,
            label: t('overview.new_interview'),
        },
        sections.records && {
            key: 'record',
            href: sections.records,
            icon: faSeedling,
            label: t('overview.field_records'),
        },
        sections.forms && {
            key: 'forms',
            href: sections.forms,
            icon: faPenRuler,
            label: t('overview.design_forms'),
        },
        can.link_species && {
            key: 'link',
            href: route('data.link', project.id),
            icon: faLink,
            label: t('overview.link_species'),
        },
        can.export && {
            key: 'export',
            href: route('data.export', project.id),
            icon: faDownload,
            label: t('overview.export'),
        },
    ].filter(Boolean);

    return (
        <AuthenticatedLayout
            title={project.name}
            headTitle={`${t('navigation.overview')} · ${project.name}`}
            subtitle={t('navigation.overview')}
        >
            <div className="p-4 md:pt-8">
                <div className="mx-auto flex max-w-6xl flex-col gap-4 sm:px-2 lg:px-6">
                    {project.finished && (
                        <div role="status" className="alert alert-info">
                            {t('overview.finished')}
                        </div>
                    )}

                    <div className="stats bg-base-100 border-base-300 grid w-full grid-flow-row grid-cols-2 overflow-hidden border shadow-sm lg:grid-cols-4">
                        <Stat
                            label={t('overview.interviews')}
                            value={counts.interviews}
                            href={sections.interviews}
                        />
                        <Stat
                            label={t('overview.field_records')}
                            value={counts.field_records}
                            href={sections.records}
                        />
                        <Stat
                            label={t('overview.species')}
                            value={counts.species}
                            href={sections.catalog}
                        />
                        <Stat
                            label={t('overview.unlinked')}
                            value={counts.unlinked_answers}
                            href={sections.data}
                        />
                    </div>

                    {shortcuts.length > 0 && (
                        <div className="flex flex-wrap gap-2">
                            {shortcuts.map((shortcut) => (
                                <Link
                                    key={shortcut.key}
                                    href={shortcut.href}
                                    className="btn btn-sm btn-outline"
                                >
                                    <FontAwesomeIcon icon={shortcut.icon} />
                                    {shortcut.label}
                                </Link>
                            ))}
                        </div>
                    )}

                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                        <Card
                            title={t('overview.waiting')}
                            className="lg:col-span-1"
                        >
                            {waitingItems.length > 0 ? (
                                <ul className="-mx-2 flex flex-col">
                                    {waitingItems}
                                </ul>
                            ) : (
                                <p className="text-base-content/70 flex items-center gap-2">
                                    <FontAwesomeIcon
                                        icon={faCircleCheck}
                                        className="text-success"
                                    />
                                    {t('overview.nothing_waiting')}
                                </p>
                            )}
                        </Card>

                        {recentInterviews && (
                            <Card title={t('overview.recent_interviews')}>
                                {recentInterviews.length > 0 ? (
                                    <ul className="-mx-2 flex flex-col">
                                        {recentInterviews.map((interview) => (
                                            <li key={interview.id}>
                                                <Link
                                                    href={route(
                                                        'interviews.show',
                                                        {
                                                            project: project.id,
                                                            instance:
                                                                interview.id,
                                                        },
                                                    )}
                                                    className="hover:bg-base-200/60 rounded-field block px-2 py-2 transition"
                                                >
                                                    <span className="block truncate font-medium">
                                                        {interview.form}
                                                    </span>
                                                    <span className="text-base-content/60 block truncate text-sm">
                                                        {[
                                                            formatDateTime(
                                                                interview.created_at,
                                                                i18n.language,
                                                            ),
                                                            interview.recorded_by,
                                                        ]
                                                            .filter(Boolean)
                                                            .join(' · ')}
                                                    </span>
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="text-base-content/70">
                                        {t('overview.no_interviews')}
                                    </p>
                                )}
                            </Card>
                        )}

                        {recentRecords && (
                            <Card title={t('overview.recent_records')}>
                                {recentRecords.length > 0 ? (
                                    <ul className="-mx-2 flex flex-col">
                                        {recentRecords.map((record) => (
                                            <li key={record.id}>
                                                <a
                                                    href={`${recordsUrl}#record-${record.id}`}
                                                    className="hover:bg-base-200/60 rounded-field block px-2 py-2 transition"
                                                >
                                                    <span className="block truncate font-medium">
                                                        {recordLabel(record, t)}
                                                    </span>
                                                    <span className="text-base-content/60 block truncate text-sm">
                                                        {[
                                                            record.species &&
                                                                `${record.species.genus} ${record.species.name}`,
                                                            record.was_collected
                                                                ? null
                                                                : t(
                                                                      'catalogs.fieldRecords.basis_human_observation',
                                                                  ),
                                                            record.collected_on,
                                                        ]
                                                            .filter(Boolean)
                                                            .join(' · ')}
                                                    </span>
                                                </a>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="text-base-content/70">
                                        {t('overview.no_records')}
                                    </p>
                                )}
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
