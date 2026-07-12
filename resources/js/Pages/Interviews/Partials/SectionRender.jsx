import Card from '@/Components/Card';
import { faPlus, faTrash } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import ItemRender from './ItemRender';

export default function SectionRender({ section, instance, answers = [] }) {
    const { t } = useTranslation();

    const initialRepeatCount = section.repeatable
        ? Math.max(
              ...answers
                  .filter((ans) => ans.section_id === section.id)
                  .map((ans) => ans.repeatable_index ?? 0),
              0,
          ) + 1
        : 1;

    const [repeatCount, setRepeatCount] = useState(initialRepeatCount);

    useEffect(() => {
        setRepeatCount(initialRepeatCount);
    }, [initialRepeatCount]);

    const handleRemove = (index) => {
        router.delete(
            route('interviews.section.remove', {
                instance: instance.id,
                section: section.id,
            }),
            {
                data: {
                    repeatable_index: index,
                },
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onError: (errors) => {
                    console.error('Failed to delete section:', errors);
                },
            },
        );
    };

    if (section.repeatable) {
        return (
            <>
                {Array.from({ length: repeatCount }).map((_, i) => (
                    <details
                        className="bg-base-200 border-base-300 collapse border"
                        key={i}
                    >
                        <summary className="collapse-title font-semibold">
                            {section.name} #{i + 1}
                        </summary>
                        <div className="collapse-content text-sm">
                            {section.description && (
                                <p className="mb-2 text-sm text-gray-500">
                                    {t(section.description)}
                                </p>
                            )}

                            {section.items.map((item) => (
                                <ItemRender
                                    key={item.id}
                                    item={item}
                                    instance={instance}
                                    answers={answers}
                                    repeatableIndex={i}
                                />
                            ))}

                            <div className="mt-4 flex justify-end">
                                <button
                                    type="button"
                                    className="btn btn-ghost btn-sm text-error"
                                    onClick={() => handleRemove(i)}
                                >
                                    <FontAwesomeIcon
                                        icon={faTrash}
                                        className="mr-2"
                                    />
                                    {t('interviews.remove_section')}
                                </button>
                            </div>
                        </div>
                    </details>
                ))}

                <button
                    type="button"
                    className="btn btn-primary mt-2 self-start"
                    onClick={() => setRepeatCount(repeatCount + 1)}
                >
                    <FontAwesomeIcon icon={faPlus} className="mr-2" />
                    {t('interviews.add_section', { section: section.name })}
                </button>
            </>
        );
    }

    return (
        <Card title={section.name} className="mb-4">
            {section.description && (
                <p className="mb-2 text-sm text-gray-500">
                    {t(section.description)}
                </p>
            )}

            {section.items.map((item) => (
                <ItemRender
                    key={item.id}
                    item={item}
                    instance={instance}
                    answers={answers}
                />
            ))}
        </Card>
    );
}
