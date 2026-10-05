import Card from '@/Components/Card';
import EmptyState from '@/Components/EmptyState';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

/**
 * A project's interviews: each active form, to start an interview on or to
 * list the ones it has taken. A form that is switched off takes no new
 * interviews, so it is left out; its interviews stay in the data views.
 */
export default function InterviewOverview({ project, forms = [] }) {
    const { t } = useTranslation();

    return (
        <AuthenticatedLayout title={t('interviews.title')}>
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <Card>
                        {forms.length > 0 ? (
                            <ul className="divide-base-300 divide-y">
                                {forms.map((form) => (
                                    // Side by side where there is room; on a
                                    // phone the actions go under the name.
                                    <li
                                        key={form.id}
                                        className="flex flex-col gap-3 py-3 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div className="min-w-0">
                                            <div className="font-medium break-words">
                                                {form.name}
                                            </div>
                                            <div className="text-base-content/60 text-xs">
                                                {t(
                                                    'designer.interviews_recorded',
                                                    {
                                                        count: form.instances_count,
                                                    },
                                                )}
                                            </div>
                                        </div>
                                        <div className="flex shrink-0 flex-wrap gap-2">
                                            <Link
                                                href={route(
                                                    'interviews.create',
                                                    {
                                                        project: project.id,
                                                        form: form.id,
                                                    },
                                                )}
                                                className="btn btn-sm btn-primary"
                                            >
                                                {t('interviews.new_interview')}
                                            </Link>
                                            <Link
                                                href={route(
                                                    'interviews.instances',
                                                    {
                                                        project: project.id,
                                                        form: form.id,
                                                    },
                                                )}
                                                className="btn btn-sm btn-ghost"
                                            >
                                                {t('interviews.view_existing')}
                                            </Link>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState
                                title={t('interviews.no_interviews')}
                                hint={t('interviews.no_active_forms_hint')}
                            />
                        )}
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
