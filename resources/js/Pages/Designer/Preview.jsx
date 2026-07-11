import Card from '@/Components/Card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    faArrowLeft,
    faPlus,
    faTrashCan,
} from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import ItemRender from './Partials/ItemRender';

export default function Preview({ project, form }) {
    const { t } = useTranslation();
    const [sectionInstances, setSectionInstances] = useState({});
    const [scrollToId, setScrollToId] = useState(null);
    const instanceRefs = useRef({});

    const handleAddInstance = (sectionId) => {
        const newId = Date.now();

        setSectionInstances((prev) => ({
            ...prev,
            [sectionId]: [...(prev[sectionId] || []), newId],
        }));

        setScrollToId(newId);
    };

    useEffect(() => {
        if (scrollToId && instanceRefs.current[scrollToId]) {
            instanceRefs.current[scrollToId].scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
            setScrollToId(null);
        }
    }, [sectionInstances, scrollToId]);

    const handleRemoveInstance = (sectionId, instanceId) => {
        setSectionInstances((prev) => ({
            ...prev,
            [sectionId]: prev[sectionId].filter((id) => id !== instanceId),
        }));
    };

    return (
        <AuthenticatedLayout
            title={t('designer.preview_title')}
            subtitle={form.name}
            action={
                <Link
                    href={route('designer.form.wizard', {
                        project: project.id,
                        form: form.id,
                    })}
                    className="btn btn-ghost"
                >
                    <FontAwesomeIcon icon={faArrowLeft} />
                </Link>
            }
        >
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {form.sections.length > 0 ? (
                        <div className="grid grid-cols-1 gap-4">
                            {form.sections.map((section) => (
                                <div key={section.id}>
                                    {section.repeatable ? (
                                        <div className="flex flex-col gap-4">
                                            <div className="mb-2">
                                                <button
                                                    className="btn btn-sm btn-primary"
                                                    onClick={() =>
                                                        handleAddInstance(
                                                            section.id,
                                                        )
                                                    }
                                                >
                                                    <FontAwesomeIcon
                                                        icon={faPlus}
                                                    />
                                                    {t(
                                                        'designer.add_section_instance',
                                                    )}{' '}
                                                    {section.name}
                                                </button>
                                            </div>

                                            {(
                                                sectionInstances[section.id] ||
                                                []
                                            ).map((instanceId, index) => (
                                                <div
                                                    className="indicator w-full"
                                                    key={instanceId}
                                                    ref={(el) =>
                                                        (instanceRefs.current[
                                                            instanceId
                                                        ] = el)
                                                    }
                                                >
                                                    <div className="indicator-item indicator-top">
                                                        <button
                                                            className="btn btn-sm btn-circle btn-error"
                                                            onClick={() =>
                                                                handleRemoveInstance(
                                                                    section.id,
                                                                    instanceId,
                                                                )
                                                            }
                                                        >
                                                            <FontAwesomeIcon
                                                                icon={
                                                                    faTrashCan
                                                                }
                                                            />
                                                        </button>
                                                    </div>
                                                    <details className="bg-base-200 border-base-300 collapse border">
                                                        <summary className="collapse-title font-semibold">
                                                            {section.name} #
                                                            {index + 1}
                                                        </summary>
                                                        <div className="collapse-content text-sm">
                                                            {section.items
                                                                .length > 0 ? (
                                                                <div className="grid grid-cols-1 gap-4">
                                                                    {section.items.map(
                                                                        (
                                                                            item,
                                                                        ) => (
                                                                            <ItemRender
                                                                                key={`${instanceId}-${item.id}`}
                                                                                item={
                                                                                    item
                                                                                }
                                                                            />
                                                                        ),
                                                                    )}
                                                                </div>
                                                            ) : (
                                                                <p>
                                                                    {t(
                                                                        'designer.no_items',
                                                                    )}
                                                                </p>
                                                            )}
                                                        </div>
                                                    </details>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <Card title={section.name}>
                                            {section.items.length > 0 ? (
                                                <div className="grid grid-cols-1 gap-4">
                                                    {section.items.map(
                                                        (item) => (
                                                            <ItemRender
                                                                key={item.id}
                                                                item={item}
                                                            />
                                                        ),
                                                    )}
                                                </div>
                                            ) : (
                                                <p>{t('designer.no_items')}</p>
                                            )}
                                        </Card>
                                    )}
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p>{t('designer.no_sections')}</p>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
