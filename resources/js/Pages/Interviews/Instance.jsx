import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { faArrowLeft } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import SectionRender from './Partials/SectionRender';

export default function Instance({ project, form, instance, answers }) {
    const { t } = useTranslation();

    return (
        <AuthenticatedLayout
            title={t('interviews.instance.title', { id: instance.id })}
            subtitle={t('interviews.form_on_project', {
                form: form.name,
                project: project.name,
            })}
            action={
                <Link
                    href={route('interviews.index')}
                    className="btn btn-ghost"
                >
                    <FontAwesomeIcon icon={faArrowLeft} />
                </Link>
            }
        >
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 gap-4">
                        {form.sections.map((section, index) => (
                            <SectionRender
                                key={index}
                                section={section}
                                instance={instance}
                                answers={answers}
                            />
                        ))}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
