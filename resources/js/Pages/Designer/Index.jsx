import Card from '@/Components/Card';
import DeletionModal from '@/Components/DeletionModal';
import EmptyState from '@/Components/EmptyState';
import FormModal from '@/Components/FormModal';
import IconButton from '@/Components/IconButton';
import useQueryModal from '@/Hooks/useQueryModal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    faPenToSquare,
    faPlus,
    faPowerOff,
    faTrashCan,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import FormDetailsForm from './Partials/FormDetailsForm';

/**
 * A project's interview forms: design each one, edit its details, switch it
 * on for interviews or off, and add new ones.
 */
export default function DesignerIndex({ project, forms = [] }) {
    const { t } = useTranslation();
    const deletionModalRef = useRef();
    const [deletionModalOptions, setDeletionModalOptions] = useState({
        url: '',
        name: '',
    });

    const [createParam, setCreate] = useQueryModal('create');
    const [editParam, setEdit] = useQueryModal('edit');

    const creating = createParam !== null;
    const editingForm = editParam
        ? forms.find((f) => f.id === Number(editParam))
        : null;

    const handleDelete = (form) => {
        setDeletionModalOptions({
            name: form.name,
            url: route('designer.form.delete', {
                project: project.id,
                form: form.id,
            }),
        });

        deletionModalRef.current.showModal();
    };

    return (
        <AuthenticatedLayout tour="forms" title={t('designer.title')}>
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <Card
                        actions={
                            <button
                                type="button"
                                onClick={() => setCreate(1)}
                                className="btn btn-primary btn-sm"
                                data-tour="forms-new"
                            >
                                <FontAwesomeIcon icon={faPlus} />
                                {t('designer.index.create')}
                            </button>
                        }
                    >
                        {forms.length > 0 ? (
                            <ul
                                className="divide-base-300 divide-y"
                                data-tour="forms-list"
                            >
                                {forms.map((form, index) => (
                                    // Side by side where there is room; on a
                                    // phone the actions go under the form.
                                    <li
                                        key={form.id}
                                        className="flex flex-col gap-3 py-3 lg:flex-row lg:items-center lg:justify-between"
                                    >
                                        <div className="min-w-0">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="font-medium break-words">
                                                    {form.name}
                                                </span>
                                                <span
                                                    className={`badge badge-sm ${
                                                        form.is_active
                                                            ? 'badge-success badge-soft'
                                                            : 'badge-ghost'
                                                    }`}
                                                >
                                                    {form.is_active
                                                        ? t(
                                                              'designer.index.status_on',
                                                          )
                                                        : t(
                                                              'designer.index.status_off',
                                                          )}
                                                </span>
                                            </div>
                                            {form.description && (
                                                <div className="text-base-content/70 mt-0.5 text-sm break-words">
                                                    {form.description}
                                                </div>
                                            )}
                                            <div className="text-base-content/60 mt-0.5 text-xs">
                                                {form.instances_count > 0 ? (
                                                    <Link
                                                        className="link link-hover"
                                                        href={route(
                                                            'interviews.instances',
                                                            {
                                                                project:
                                                                    project.id,
                                                                form: form.id,
                                                            },
                                                        )}
                                                    >
                                                        {t(
                                                            'designer.interviews_recorded',
                                                            {
                                                                count: form.instances_count,
                                                            },
                                                        )}
                                                    </Link>
                                                ) : (
                                                    t(
                                                        'designer.interviews_recorded',
                                                        {
                                                            count: form.instances_count,
                                                        },
                                                    )
                                                )}
                                            </div>
                                        </div>
                                        <div className="flex shrink-0 flex-wrap items-center gap-1">
                                            <Link
                                                className="btn btn-primary btn-sm"
                                                data-tour={
                                                    index === 0
                                                        ? 'forms-design'
                                                        : undefined
                                                }
                                                href={route(
                                                    'designer.form.wizard',
                                                    {
                                                        project: project.id,
                                                        form: form.id,
                                                    },
                                                )}
                                            >
                                                {t('designer.index.wizard')}
                                            </Link>
                                            <button
                                                type="button"
                                                className="btn btn-ghost btn-sm"
                                                onClick={() => setEdit(form.id)}
                                            >
                                                <FontAwesomeIcon
                                                    icon={faPenToSquare}
                                                />
                                                {t(
                                                    'designer.index.edit_details',
                                                )}
                                            </button>
                                            <Link
                                                className="btn btn-ghost btn-sm"
                                                data-tour={
                                                    index === 0
                                                        ? 'forms-toggle'
                                                        : undefined
                                                }
                                                href={route(
                                                    'designer.form.toggle',
                                                    {
                                                        project: project.id,
                                                        form: form.id,
                                                    },
                                                )}
                                                method="put"
                                                as="button"
                                            >
                                                <FontAwesomeIcon
                                                    icon={faPowerOff}
                                                />
                                                {form.is_active
                                                    ? t(
                                                          'designer.index.disable',
                                                      )
                                                    : t(
                                                          'designer.index.enable',
                                                      )}
                                            </Link>
                                            <IconButton
                                                icon={faTrashCan}
                                                label={t(
                                                    'designer.index.delete',
                                                )}
                                                className="text-error"
                                                onClick={() =>
                                                    handleDelete(form)
                                                }
                                            />
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState
                                title={t('designer.index.no_forms_yet')}
                                hint={t('designer.index.no_forms_hint')}
                            />
                        )}
                    </Card>
                </div>
            </div>

            <DeletionModal
                modalRef={deletionModalRef}
                name={deletionModalOptions.name}
                url={deletionModalOptions.url}
            />

            <FormModal
                open={creating}
                onClose={() => setCreate(null)}
                title={t('designer.create_form.title')}
            >
                {creating && (
                    <FormDetailsForm
                        key="create"
                        project={project}
                        onClose={() => setCreate(null)}
                    />
                )}
            </FormModal>

            <FormModal
                open={!!editingForm}
                onClose={() => setEdit(null)}
                title={
                    editingForm
                        ? `${t('designer.create_form.edit_title')} — ${editingForm.name}`
                        : t('designer.create_form.edit_title')
                }
            >
                {editingForm && (
                    <FormDetailsForm
                        key={`edit-${editParam}`}
                        project={project}
                        form={editingForm}
                        onClose={() => setEdit(null)}
                    />
                )}
            </FormModal>
        </AuthenticatedLayout>
    );
}
