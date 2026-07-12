import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatDateTime } from '@/utils/datetime';
import { faArrowLeft } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import SectionRender from './Partials/SectionRender';

export default function Instance({ project, form, instance, answers }) {
    const { t, i18n } = useTranslation();

    // Humanized identity: when it was recorded and by whom — never the
    // raw instance id.
    const recordedAt = formatDateTime(instance.created_at, i18n.language);
    const title = [t('interviews.instance_label'), recordedAt]
        .filter(Boolean)
        .join(' · ');

    return (
        <AuthenticatedLayout
            title={title}
            breadcrumbs={[
                {
                    label: t('navigation.interview'),
                    href: route('interviews.index'),
                },
                {
                    label: form.name,
                    href: route('interviews.instances', { form: form.id }),
                },
                { label: t('interviews.instance_label') },
            ]}
            subtitle={[
                t('interviews.form_on_project', {
                    form: form.name,
                    project: project.name,
                }),
                instance.user?.name,
            ]
                .filter(Boolean)
                .join(' · ')}
            action={
                <Link
                    href={route('interviews.instances', { form: form.id })}
                    className="btn btn-ghost"
                    aria-label={t('navigation.back')}
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
