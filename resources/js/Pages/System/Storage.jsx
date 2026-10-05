import Card from '@/Components/Card';
import { formatBytes } from '@/utils/bytes';
import { useTranslation } from 'react-i18next';
import Checks from './Partials/Checks';
import SystemPage from './Partials/SystemPage';
import UsageBar from './Partials/UsageBar';

/**
 * Where the storage goes: the total by kind, by the person who owns the
 * projects, the largest projects by size, and the upkeep that keeps the
 * bucket honest. Names and sizes only.
 */
export default function SystemStorage({ storage, owners, largest, checks }) {
    const { t, i18n } = useTranslation();
    const size = (bytes) => formatBytes(bytes, i18n.language);
    const number = (value) =>
        new Intl.NumberFormat(i18n.language).format(value);
    const share = (part) =>
        storage.total > 0 ? Math.round((part / storage.total) * 100) : 0;

    return (
        <SystemPage tab="storage">
            <Card>
                <div className="flex flex-wrap items-end justify-between gap-6">
                    <div>
                        <h2 className="text-base-content/70 text-xs font-semibold tracking-[0.14em] uppercase">
                            {t('system.storage.in_use')}
                        </h2>
                        <p className="text-4xl font-extrabold">
                            {size(storage.total)}
                        </p>
                        <p className="text-base-content/70 text-sm">
                            {t('system.storage.files_recent', {
                                files: number(storage.files),
                                recent: size(storage.recent),
                            })}
                        </p>
                    </div>
                    <div className="w-full max-w-xl min-w-0 flex-1">
                        <div
                            className="bg-base-300 flex h-4 overflow-hidden rounded-full"
                            aria-hidden="true"
                        >
                            <div
                                className="bg-primary"
                                style={{ width: `${share(storage.audio)}%` }}
                            />
                            <div
                                className="bg-info"
                                style={{ width: `${share(storage.photo)}%` }}
                            />
                        </div>
                        <div className="mt-2 flex flex-wrap gap-4 text-sm">
                            <span className="inline-flex items-center gap-2">
                                <span
                                    className="bg-primary size-2.5 rounded-sm"
                                    aria-hidden="true"
                                />
                                {t('system.storage.audio', {
                                    size: size(storage.audio),
                                })}
                            </span>
                            <span className="inline-flex items-center gap-2">
                                <span
                                    className="bg-info size-2.5 rounded-sm"
                                    aria-hidden="true"
                                />
                                {t('system.storage.photo', {
                                    size: size(storage.photo),
                                })}
                            </span>
                        </div>
                    </div>
                </div>
            </Card>

            <Card
                title={t('system.storage.by_person')}
                description={t('system.storage.by_person_note')}
            >
                {owners.length === 0 ? (
                    <p className="text-base-content/70 text-sm">
                        {t('system.storage.empty')}
                    </p>
                ) : (
                    <>
                        {/* Narrow screens: the total first, the split beneath. */}
                        <ul className="divide-base-300 divide-y lg:hidden">
                            {owners.map((owner) => (
                                <li key={owner.id} className="space-y-1.5 py-3">
                                    <div className="flex items-baseline justify-between gap-3">
                                        <span className="min-w-0 font-semibold break-words">
                                            {owner.name}
                                        </span>
                                        <span className="font-bold whitespace-nowrap">
                                            {size(owner.total)}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-2 text-sm">
                                        <UsageBar
                                            value={owner.total}
                                            max={storage.total}
                                            className="flex-1"
                                        />
                                        <span className="whitespace-nowrap">
                                            {share(owner.total)} %
                                        </span>
                                    </div>
                                    <p className="text-base-content/70 text-sm">
                                        {t('system.storage.owner_line', {
                                            count: owner.projects,
                                            audio: size(owner.audio),
                                            photo: size(owner.photo),
                                        })}
                                        {' · '}
                                        {t('system.storage.no_limit')}
                                    </p>
                                </li>
                            ))}
                        </ul>
                        <div className="hidden overflow-x-auto lg:block">
                            <table className="table">
                                <thead>
                                    <tr>
                                        <th scope="col">
                                            {t('system.users.col_person')}
                                        </th>
                                        <th scope="col" className="text-right">
                                            {t('system.users.col_projects')}
                                        </th>
                                        <th scope="col" className="text-right">
                                            {t('system.storage.col_audio')}
                                        </th>
                                        <th scope="col" className="text-right">
                                            {t('system.storage.col_photo')}
                                        </th>
                                        <th scope="col" className="text-right">
                                            {t('system.storage.col_total')}
                                        </th>
                                        <th scope="col">
                                            {t('system.storage.col_share')}
                                        </th>
                                        <th scope="col">
                                            {t('system.storage.col_limit')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {owners.map((owner) => (
                                        <tr key={owner.id}>
                                            <td className="font-semibold">
                                                {owner.name}
                                            </td>
                                            <td className="text-right">
                                                {number(owner.projects)}
                                            </td>
                                            <td className="text-right whitespace-nowrap">
                                                {size(owner.audio)}
                                            </td>
                                            <td className="text-right whitespace-nowrap">
                                                {size(owner.photo)}
                                            </td>
                                            <td className="text-right font-bold whitespace-nowrap">
                                                {size(owner.total)}
                                            </td>
                                            <td>
                                                <div className="flex items-center gap-2">
                                                    <UsageBar
                                                        value={owner.total}
                                                        max={storage.total}
                                                        className="w-24"
                                                    />
                                                    <span className="whitespace-nowrap">
                                                        {share(owner.total)} %
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="text-base-content/70 whitespace-nowrap">
                                                {t('system.storage.no_limit')}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}
                <p className="text-base-content/70 text-sm">
                    {t('system.storage.limits_note')}
                </p>
            </Card>

            <div className="grid grid-cols-1 items-start gap-4 lg:grid-cols-2">
                <Card title={t('system.storage.largest')}>
                    {largest.length === 0 ? (
                        <p className="text-base-content/70 text-sm">
                            {t('system.storage.empty')}
                        </p>
                    ) : (
                        <ul className="divide-base-300 divide-y">
                            {largest.map((project) => (
                                <li
                                    key={project.id}
                                    className="flex items-center gap-3 py-2.5"
                                >
                                    <div className="min-w-0 flex-1">
                                        <div className="text-sm font-semibold break-words">
                                            {project.name}
                                        </div>
                                        <div className="text-base-content/70 text-sm">
                                            {t('system.storage.largest_line', {
                                                owner: project.owner,
                                                count: project.files,
                                                files: number(project.files),
                                            })}
                                        </div>
                                    </div>
                                    <span className="text-sm font-bold whitespace-nowrap">
                                        {size(project.bytes)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                    <p className="text-base-content/70 text-sm">
                        {t('system.storage.largest_note')}
                    </p>
                </Card>

                <Card title={t('system.storage.upkeep')}>
                    <Checks
                        checks={checks}
                        only={['orphans', 'uploads', 'waiting']}
                    />
                </Card>
            </div>
        </SystemPage>
    );
}
