import Card from '@/Components/Card';
import DeletionModal from '@/Components/DeletionModal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { faTrashCan } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useRef } from 'react';
import { useTranslation } from 'react-i18next';
import ProjectForm from './Partials/ProjectForm';

/**
 * A project's settings, for whoever manages it: the details that describe
 * the study, and deleting it.
 */
export default function Settings({ project }) {
    const { t } = useTranslation();
    const deletionModalRef = useRef();

    return (
        <AuthenticatedLayout title={t('navigation.settings')}>
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto flex max-w-3xl flex-col gap-4 sm:px-6 lg:px-8">
                    <Card
                        title={t('projects.settings.details')}
                        className="w-full"
                    >
                        <ProjectForm project={project} />
                    </Card>

                    <Card
                        title={t('projects.settings.delete_title')}
                        className="border-error/40 w-full"
                    >
                        <p className="text-base-content/70 text-sm">
                            {t('projects.settings.delete_hint')}
                        </p>
                        <div className="flex justify-end">
                            <button
                                type="button"
                                className="btn btn-outline btn-error btn-sm"
                                onClick={() =>
                                    deletionModalRef.current.showModal()
                                }
                            >
                                <FontAwesomeIcon icon={faTrashCan} />
                                {t('projects.settings.delete')}
                            </button>
                        </div>
                    </Card>
                </div>
            </div>

            <DeletionModal
                modalRef={deletionModalRef}
                name={project.name}
                url={route('projects.delete', { project: project.id })}
            />
        </AuthenticatedLayout>
    );
}
