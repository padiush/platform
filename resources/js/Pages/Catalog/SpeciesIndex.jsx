import Card from '@/Components/Card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    faArrowLeft,
    faChevronRight,
} from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function CatalogSpeciesIndex({ project, species }) {
    const { t } = useTranslation();
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
                        <ul className="border-base-300 divide-base-300 rounded-box divide-y border">
                            {species.data.map((sp) => (
                                <li key={sp.id}>
                                    <Link
                                        href={route('catalogs.species.show', {
                                            project: project.id,
                                            species: sp.id,
                                        })}
                                        className="hover:bg-base-200 group flex items-center gap-3 px-4 py-3 transition"
                                    >
                                        <span className="min-w-0 grow">
                                            <span className="block truncate font-medium">
                                                <span className="italic">
                                                    {sp.genus} {sp.name}
                                                </span>{' '}
                                                <span className="text-base-content/60 font-normal">
                                                    {sp.authority}
                                                </span>
                                            </span>
                                            <span className="text-base-content/60 mt-0.5 block truncate text-xs">
                                                {[
                                                    sp.family,
                                                    t(
                                                        'designer.summary.answers',
                                                        {
                                                            count: sp.answers
                                                                .length,
                                                        },
                                                    ),
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ')}
                                            </span>
                                        </span>
                                        <FontAwesomeIcon
                                            icon={faChevronRight}
                                            className="text-base-content/30 group-hover:text-base-content/60 shrink-0 transition"
                                        />
                                    </Link>
                                </li>
                            ))}
                        </ul>

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
        </AuthenticatedLayout>
    );
}
