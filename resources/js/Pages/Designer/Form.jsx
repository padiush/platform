import Card from '@/Components/Card';
import Input from '@/Components/Input';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { faArrowLeft } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function DesignerForm({ project, form = null }) {
    const { t } = useTranslation();

    const isEdit = Boolean(form);
    const { data, setData, post, put, processing, errors } = useForm({
        project_id: project.id,
        name: form?.name || '',
        description: form?.description || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(
                route('designer.form.update', {
                    project: project.id,
                    form: form.id,
                }),
            );
        } else {
            post(route('designer.create', { project: project.id }));
        }
    };

    return (
        <AuthenticatedLayout
            title={
                isEdit
                    ? t('designer.create_form.edit_title')
                    : t('designer.create_form.title')
            }
            subtitle={project.name}
            action={
                <Link
                    className="btn btn-ghost"
                    href={route('designer.index')}
                    aria-label={t('navigation.back')}
                >
                    <FontAwesomeIcon icon={faArrowLeft} />
                </Link>
            }
        >
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <Card title={t('designer.create_form.form_details')}>
                        <form onSubmit={handleSubmit}>
                            <input
                                type="hidden"
                                name="project_id"
                                value={data.project_id}
                            />

                            <div className="grid w-full grid-cols-1 gap-4 lg:grid-cols-2">
                                <Input
                                    name="name"
                                    label={t(
                                        'designer.create_form.form_title_label',
                                    )}
                                    required
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    error={errors.name}
                                    className="lg:col-span-2"
                                />

                                <Input
                                    name="description"
                                    type="textarea"
                                    label={t(
                                        'designer.create_form.description_label',
                                    )}
                                    value={data.description}
                                    onChange={(e) =>
                                        setData('description', e.target.value)
                                    }
                                    error={errors.description}
                                    className="lg:col-span-2"
                                />
                            </div>

                            <div className="mt-4 flex w-full gap-2">
                                <Link
                                    href={route('designer.index')}
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
