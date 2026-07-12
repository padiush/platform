import Card from '@/Components/Card';
import DeletionModal from '@/Components/DeletionModal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { faArrowLeft, faEye } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function CatalogSpeciesIndex({ project, species }) {
    const { t } = useTranslation();
    const deletionModalRef = useRef();
    const [deletionModalOptions, setDeletionModalOptions] = useState({
        url: '',
        name: '',
    });

    const handleDelete = (sp) => {
        setDeletionModalOptions({
            name: `${sp.genus} ${sp.name}`,
            url: route('catalogs.species.delete', {
                project: project.id,
                species: sp.id,
            }),
        });

        deletionModalRef.current.showModal();
    };

    return (
        <AuthenticatedLayout
            title={t('catalogs.ethnobotanical_catalog')}
            breadcrumbs={[
                {
                    label: t('navigation.catalogs'),
                    href: route('catalogs.index'),
                },
                { label: project.name },
            ]}
            subtitle={project.name}
            action={
                <Link
                    href={route('catalogs.index')}
                    className="btn btn-ghost btn-circle"
                    aria-label={t('navigation.back')}
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
                                            <div className="join join-vertical lg:join-horizontal">
                                                <Link
                                                    href={route(
                                                        'catalogs.species.show',
                                                        {
                                                            project: project.id,
                                                            species: sp.id,
                                                        },
                                                    )}
                                                    className="btn btn-primary btn-xs join-item"
                                                >
                                                    <FontAwesomeIcon
                                                        icon={faEye}
                                                        className="mr-2"
                                                    />
                                                    {t('common.actions.view')}
                                                </Link>
                                                <div
                                                    className="btn btn-error btn-xs join-item"
                                                    onClick={() =>
                                                        handleDelete(sp)
                                                    }
                                                >
                                                    {t('actions.delete')}
                                                </div>
                                            </div>
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
                                                    ? 'btn-primary'
                                                    : 'btn-ghost'
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
            <DeletionModal
                modalRef={deletionModalRef}
                name={deletionModalOptions.name}
                url={deletionModalOptions.url}
            />
        </AuthenticatedLayout>
    );
}
