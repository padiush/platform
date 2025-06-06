import Card from '@/Components/Card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { faArrowLeft, faEye } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function CatalogSpeciesIndex({ project, species }) {
    const { t } = useTranslation();

    return (
        <AuthenticatedLayout
            title={t('catalogs.ethnobotanical_catalog')}
            subtitle={project.name}
            action={
                <Link
                    href={route('catalogs.index')}
                    className="btn btn-ghost btn-circle"
                >
                    <FontAwesomeIcon icon={faArrowLeft} />
                </Link>
            }
        >
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <Card title={t('catalogs.species_list')}>
                        <table className="table-compact table w-full table-fixed">
                            <thead>
                                <tr>
                                    <th className="lg:w-1/6">
                                        {t('catalogs.family')}
                                    </th>
                                    <th className="lg:w-2/3">
                                        {t('catalogs.species')}
                                    </th>
                                    <th className="lg:w-1/6">
                                        {t('catalogs.reports')}
                                    </th>
                                    <th className="lg:w-1/6">
                                        {t('projects.actions')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {species.data.map((sp) => (
                                    <tr key={sp.id}>
                                        <td className="text-wrap">
                                            {sp.family}
                                        </td>
                                        <td className="text-wrap">
                                            <span className="italic">
                                                {sp.genus} {sp.name}
                                            </span>{' '}
                                            {sp.authority}
                                        </td>
                                        <td className="text-wrap">
                                            {sp.answers.length}
                                        </td>
                                        <td>
                                            <Link
                                                href={route(
                                                    'catalogs.species.show',
                                                    {
                                                        project: project.id,
                                                        species: sp.id,
                                                    },
                                                )}
                                                className="btn btn-primary btn-xs"
                                            >
                                                <FontAwesomeIcon
                                                    icon={faEye}
                                                    className="mr-2"
                                                />
                                                {t('common.actions.view')}
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* Pagination */}
                        <div className="mt-4 flex justify-center">
                            {species.links && (
                                <div className="join">
                                    {species.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`join-item btn btn-sm ${
                                                link.active
                                                    ? 'btn-ghost'
                                                    : 'btn-primary'
                                            }`}
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
