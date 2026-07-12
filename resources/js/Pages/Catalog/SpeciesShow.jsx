import Card from '@/Components/Card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    faArrowLeft,
    faArrowUpRightFromSquare,
} from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function SpeciesShow({ species, project }) {
    const { t } = useTranslation();
    const [wfoData, setWfoData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const scientificName = `${species.genus} ${species.name}`;

    useEffect(() => {
        const fetchWFOData = async () => {
            try {
                const response = await axios.post(route('wfo.query'), {
                    input: scientificName,
                });

                const suggestions = response.data.data?.taxonNameSuggestion;

                if (!suggestions || suggestions.length === 0) {
                    setWfoData(null);
                    return;
                }

                // Optionally, pick the first match or filter as needed
                setWfoData(suggestions); // or set all if you want a list
            } catch (err) {
                console.error('Error fetching WFO data:', err);
                setError(t('catalogs.fetch_error'));
            } finally {
                setLoading(false);
            }
        };

        if (scientificName?.length > 0) {
            setLoading(true);
            fetchWFOData();
        }
    }, [scientificName, t]);

    return (
        <AuthenticatedLayout
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
        >
            <div className="p-4 md:pt-8 lg:pt-12">
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
                        ) : wfoData && Array.isArray(wfoData) ? (
                            <div className="space-y-4">
                                <p className="text-sm">
                                    {t('catalogs.wfo_query_info', {
                                        name: scientificName,
                                    })}
                                </p>

                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    {wfoData.map((item, index) => (
                                        <div
                                            key={index}
                                            className="border-b pb-3"
                                        >
                                            <div
                                                className="text-lg italic"
                                                dangerouslySetInnerHTML={{
                                                    __html: item.fullNameStringHtml,
                                                }}
                                            />
                                            {item.currentPreferredUsage
                                                ?.hasName ? (
                                                <div
                                                    className="text-success text-sm"
                                                    dangerouslySetInnerHTML={{
                                                        __html:
                                                            `<span class="font-medium">${t('catalogs.accepted_as')}:</span> ` +
                                                            item
                                                                .currentPreferredUsage
                                                                .hasName
                                                                .fullNameStringHtml,
                                                    }}
                                                />
                                            ) : (
                                                <p className="text-warning text-sm">
                                                    {t(
                                                        'catalogs.no_preferred_usage',
                                                    )}
                                                </p>
                                            )}
                                            <div className="mt-2">
                                                <a
                                                    href={item.stableUri}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="btn btn-primary btn-xs"
                                                >
                                                    <FontAwesomeIcon
                                                        icon={
                                                            faArrowUpRightFromSquare
                                                        }
                                                        className="mr-2"
                                                    />
                                                    {t('catalogs.view_on_wfo')}
                                                </a>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
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
                                className="btn btn-primary"
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
                                className="btn btn-primary"
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
                                className="btn btn-primary"
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
            </div>
        </AuthenticatedLayout>
    );
}
