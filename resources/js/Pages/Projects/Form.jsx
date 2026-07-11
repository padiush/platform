import Card from '@/Components/Card';
import Input from '@/Components/Input';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { faArrowLeft } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function ProjectForm({ project = null, auth }) {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors } = useForm({
        name: project?.name || '',
        author: project?.author || auth.user.name,
        institution: project?.institution || '',
        author_email: project?.author_email || auth.user.email,
        country: project?.country || '',
    });

    const isEdit = Boolean(project);
    const handleSubmit = (e) => {
        e.preventDefault();

        const targetRoute = isEdit
            ? route('projects.edit', { project: project.id })
            : route('projects.create');

        post(targetRoute);
    };

    return (
        <AuthenticatedLayout
            title={
                isEdit
                    ? t('projects.edit_project_details')
                    : t('projects.create_project')
            }
            action={
                <Link href={route('projects.index')} className="btn btn-ghost">
                    <FontAwesomeIcon icon={faArrowLeft} />
                </Link>
            }
            subtitle={isEdit ? project.name : ''}
        >
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <Card title={t('projects.project_details')}>
                        <form onSubmit={handleSubmit}>
                            <div className="grid w-full grid-cols-1 gap-4 lg:grid-cols-2">
                                <Input
                                    name="name"
                                    label={t('projects.name')}
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    error={errors.name}
                                    required
                                />
                                <Input
                                    name="author"
                                    label={t('projects.author')}
                                    value={data.author}
                                    onChange={(e) =>
                                        setData('author', e.target.value)
                                    }
                                    error={errors.author}
                                />
                                <Input
                                    name="institution"
                                    label={t('projects.institution')}
                                    value={data.institution}
                                    onChange={(e) =>
                                        setData('institution', e.target.value)
                                    }
                                    error={errors.institution}
                                />
                                <Input
                                    name="author_email"
                                    type="email"
                                    label={t('projects.author_email')}
                                    value={data.author_email}
                                    onChange={(e) =>
                                        setData('author_email', e.target.value)
                                    }
                                    error={errors.author_email}
                                />
                                <Input
                                    name="country"
                                    label={t('projects.country')}
                                    value={data.country}
                                    onChange={(e) =>
                                        setData('country', e.target.value)
                                    }
                                    error={errors.country}
                                />
                            </div>
                            <div className="mt-4 flex gap-2">
                                <Link
                                    href={route('projects.index')}
                                    className="btn btn-outline"
                                >
                                    {t('actions.cancel')}
                                </Link>
                                <button
                                    type="submit"
                                    className="btn btn-primary"
                                    disabled={processing}
                                >
                                    {isEdit
                                        ? t('actions.update')
                                        : t('actions.create')}
                                </button>
                            </div>
                        </form>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
