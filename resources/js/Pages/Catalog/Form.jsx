import Card from '@/Components/Card';
import Input from '@/Components/Input';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { faArrowLeft } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function CatalogSpeciesForm({ project }) {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors } = useForm({
        family: '',
        genus: '',
        name: '',
        authority: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('catalogs.species.register', { project: project.id }));
    };

    return (
        <AuthenticatedLayout
            title={t('catalogs.register_species')}
            breadcrumbs={[
                {
                    label: t('navigation.catalogs'),
                    href: route('catalogs.index'),
                },
                { label: project.name },
                { label: t('catalogs.register_species') },
            ]}
            subtitle={`${t('catalogs.catalog_for_project')} "${project.name}"`}
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
                    <Card title={t('catalogs.species_info')}>
                        <form onSubmit={handleSubmit}>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <Input
                                    name="family"
                                    label={t('catalogs.family')}
                                    value={data.family}
                                    onChange={(e) =>
                                        setData('family', e.target.value)
                                    }
                                    error={errors.family}
                                />
                                <Input
                                    name="genus"
                                    label={t('catalogs.genus')}
                                    value={data.genus}
                                    onChange={(e) =>
                                        setData('genus', e.target.value)
                                    }
                                    error={errors.genus}
                                    required
                                />
                                <Input
                                    name="name"
                                    label={t('catalogs.species')}
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    error={errors.name}
                                    required
                                />
                                <Input
                                    name="authority"
                                    label={t('catalogs.authority')}
                                    value={data.authority}
                                    onChange={(e) =>
                                        setData('authority', e.target.value)
                                    }
                                    error={errors.authority}
                                />
                            </div>

                            <div className="mt-4">
                                <button
                                    type="submit"
                                    className="btn btn-primary w-full"
                                    disabled={processing}
                                >
                                    {t('catalogs.register_species')}
                                </button>
                            </div>
                        </form>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
