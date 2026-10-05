import Card from '@/Components/Card';
import { faPlus, faTrashCan } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import ItemRender from './ItemRender';

function ProgressBadge({ answered, total }) {
    if (total === 0) {
        return null;
    }

    return (
        <span
            className={`badge tabular-nums ${
                answered === total ? 'badge-success' : 'badge-ghost'
            }`}
        >
            {answered}/{total}
        </span>
    );
}

export default function SectionRender({
    section,
    projectId,
    instance,
    answers = [],
    answeredKeys = new Set(),
    catalogProjectId = null,
    onAnswered = () => {},
}) {
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

    // A collapsed set would otherwise give no sign that a plant named in it
    // was recorded; its header says how many field records came out of it.
    const recordsIn = (repeatableIndex) =>
        answers
            .filter(
                (ans) =>
                    ans.section_id === section.id &&
                    (ans.repeatable_index ?? 0) === repeatableIndex,
            )
            .reduce((sum, ans) => sum + (ans.field_records?.length ?? 0), 0);

    const answeredIn = (repeatableIndex) =>
        section.items.filter((item) =>
            answeredKeys.has(`${item.id}:${repeatableIndex ?? 'base'}`),
        ).length;

    const handleRemove = (index) => {
        router.delete(
            route('interviews.section.remove', {
                project: projectId,
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
                        className="bg-base-100 border-base-300 collapse border"
                        key={i}
                    >
                        <summary className="collapse-title flex items-center justify-between gap-2 py-4 font-semibold">
                            <span>
                                {section.name} #{i + 1}
                            </span>
                            <span className="ml-2 inline-flex items-center gap-2">
                                {recordsIn(i) > 0 && (
                                    <span className="badge badge-outline badge-sm whitespace-nowrap">
                                        {t('interviews.field_records_count', {
                                            count: recordsIn(i),
                                        })}
                                    </span>
                                )}
                                <ProgressBadge
                                    answered={answeredIn(i)}
                                    total={section.items.length}
                                />
                            </span>
                        </summary>
                        <div className="collapse-content text-sm">
                            {section.description && (
                                <p className="text-base-content/60 mb-2 text-sm">
                                    {t(section.description)}
                                </p>
                            )}

                            <div className="grid grid-cols-1 gap-3">
                                {section.items.map((item) => (
                                    <ItemRender
                                        key={item.id}
                                        item={item}
                                        projectId={projectId}
                                        instance={instance}
                                        answers={answers}
                                        repeatableIndex={i}
                                        catalogProjectId={catalogProjectId}
                                        onAnswered={onAnswered}
                                    />
                                ))}
                            </div>

                            <div className="mt-4 flex justify-end">
                                <button
                                    type="button"
                                    className="btn btn-ghost btn-sm text-error"
                                    onClick={() => handleRemove(i)}
                                >
                                    <FontAwesomeIcon
                                        icon={faTrashCan}
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
                    className="btn btn-primary mt-2 justify-self-start"
                    onClick={() => setRepeatCount(repeatCount + 1)}
                >
                    <FontAwesomeIcon icon={faPlus} className="mr-2" />
                    {t('interviews.add_section', { section: section.name })}
                </button>
            </>
        );
    }

    return (
        <Card
            title={section.name}
            className="mb-4"
            actions={
                <ProgressBadge
                    answered={answeredIn(null)}
                    total={section.items.length}
                />
            }
        >
            {section.description && (
                <p className="text-base-content/60 mb-2 text-sm">
                    {t(section.description)}
                </p>
            )}

            <div className="grid grid-cols-1 gap-3">
                {section.items.map((item) => (
                    <ItemRender
                        key={item.id}
                        item={item}
                        projectId={projectId}
                        instance={instance}
                        answers={answers}
                        catalogProjectId={catalogProjectId}
                        onAnswered={onAnswered}
                    />
                ))}
            </div>
        </Card>
    );
}
