import DragHandle from '@/Components/DragHandle';
import IconButton from '@/Components/IconButton';
import PositionSelect from '@/Components/PositionSelect';
import { faPlus, faTrashCan } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

/**
 * The outline sidebar: one row per section, keyboard-selectable, with item
 * counts. Reordering: drag from the grip, or use the position select
 * (keyboard/touch). Renaming happens in the main panel, so rows stay plain
 * buttons.
 */
export default function SectionList({
    sections,
    selectedIndex,
    onSelect,
    onAdd,
    onMove,
    onRemove,
}) {
    const { t } = useTranslation();
    // Rows are draggable only while their grip is pressed.
    const [dragArmedId, setDragArmedId] = useState(null);
    const [draggedIndex, setDraggedIndex] = useState(null);

    return (
        <nav aria-label={t('designer.title')} className="flex flex-col gap-2">
            <button
                type="button"
                className="btn btn-primary"
                onClick={onAdd}
                data-add-section
            >
                <FontAwesomeIcon icon={faPlus} />
                {t('designer.new_section')}
            </button>

            {sections.map((section, index) => {
                const name =
                    section.name.trim() ||
                    `${t('designer.section')} ${index + 1}`;
                const selected = index === selectedIndex;

                return (
                    <div
                        key={section.clientId}
                        className={`rounded-lg border-2 p-1 ${
                            selected
                                ? 'border-primary bg-base-300'
                                : 'hover:bg-base-300 border-transparent'
                        } ${draggedIndex === index ? 'opacity-60' : ''}`}
                        draggable={dragArmedId === section.clientId}
                        onDragStart={() => setDraggedIndex(index)}
                        onDragOver={(e) => {
                            if (draggedIndex !== null) {
                                e.preventDefault();
                            }
                        }}
                        onDrop={() => {
                            if (
                                draggedIndex !== null &&
                                draggedIndex !== index
                            ) {
                                onMove(draggedIndex, index);
                            }
                            setDraggedIndex(null);
                            setDragArmedId(null);
                        }}
                        onDragEnd={() => {
                            setDraggedIndex(null);
                            setDragArmedId(null);
                        }}
                    >
                        <div className="flex items-start gap-1">
                            {sections.length > 1 && (
                                <DragHandle
                                    title={t('designer.drag_handle')}
                                    onArm={() =>
                                        setDragArmedId(section.clientId)
                                    }
                                    onDisarm={() => setDragArmedId(null)}
                                />
                            )}
                            <button
                                type="button"
                                aria-label={t('designer.select_section_aria', {
                                    name,
                                })}
                                aria-current={selected ? 'true' : undefined}
                                onClick={() => onSelect(index)}
                                className="min-w-0 grow rounded-md px-2 py-1.5 text-left"
                            >
                                <span className="block truncate font-semibold">
                                    {name}
                                </span>
                                <span className="text-base-content/60 block text-xs">
                                    {t('designer.summary.fields', {
                                        count: section.items.length,
                                    })}
                                    {section.repeatable
                                        ? ` · ${t('designer.repeatable')}`
                                        : ''}
                                </span>
                            </button>
                        </div>
                        <div className="flex items-center justify-end gap-1 px-1 pb-1">
                            <PositionSelect
                                index={index}
                                count={sections.length}
                                onMove={(to) => onMove(index, to)}
                            />
                            <IconButton
                                icon={faTrashCan}
                                label={t('designer.delete_section')}
                                className="btn-xs text-error"
                                onClick={() => onRemove(index)}
                            />
                        </div>
                    </div>
                );
            })}
        </nav>
    );
}
