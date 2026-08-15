import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatDateTime } from '@/utils/datetime';
import { faArrowLeft } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import SectionRender from './Partials/SectionRender';

const answerKey = (itemId, repeatableIndex) =>
    `${itemId}:${repeatableIndex ?? 'base'}`;

function hasStoredContent(value) {
    return value !== null && value !== '' && value !== '[]';
}

export default function Instance({ project, form, instance, answers }) {
    const { t, i18n } = useTranslation();

    // Humanized identity: when it was recorded and by whom — never the
    // raw instance id.
    const recordedAt = formatDateTime(instance.created_at, i18n.language);
    const title = [t('interviews.instance_label'), recordedAt]
        .filter(Boolean)
        .join(' · ');

    // Live registry of answered fields: seeded from the stored answers and
    // kept current as fields save, so section progress updates in place.
    const [answeredKeys, setAnsweredKeys] = useState(
        () =>
            new Set(
                answers
                    .filter((answer) => hasStoredContent(answer.value))
                    .map((answer) =>
                        answerKey(answer.item_id, answer.repeatable_index),
                    ),
            ),
    );

    const handleAnswered = (itemId, repeatableIndex, filled) => {
        setAnsweredKeys((previous) => {
            const next = new Set(previous);
            const key = answerKey(itemId, repeatableIndex);

            if (filled) {
                next.add(key);
            } else {
                next.delete(key);
            }

            return next;
        });
    };

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
            actionRight={
                <span className="badge badge-outline whitespace-nowrap tabular-nums">
                    {t('designer.summary.answers', {
                        count: answeredKeys.size,
                    })}
                </span>
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
                                answeredKeys={answeredKeys}
                                onAnswered={handleAnswered}
                            />
                        ))}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
