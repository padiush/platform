import Card from '@/Components/Card';
import Input from '@/Components/Input';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { faArrowLeft } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function CatalogSpeciesForm({ project }) {
    const { t } = useTranslation();

    const { data, setData, post, processing } = useForm({
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
            subtitle={`${t('catalogs.catalog_for_project')} "${project.name}"`}
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
                                />
                                <Input
                                    name="genus"
                                    label={t('catalogs.genus')}
                                    value={data.genus}
                                    onChange={(e) =>
                                        setData('genus', e.target.value)
                                    }
                                    required
                                />
                                <Input
                                    name="name"
                                    label={t('catalogs.species')}
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    required
                                />
                                <Input
                                    name="authority"
                                    label={t('catalogs.authority')}
                                    value={data.authority}
                                    onChange={(e) =>
                                        setData('authority', e.target.value)
                                    }
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
