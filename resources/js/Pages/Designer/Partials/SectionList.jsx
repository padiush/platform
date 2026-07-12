import IconButton from '@/Components/IconButton';
import {
    faArrowDown,
    faArrowUp,
    faPlus,
    faTrashCan,
} from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useTranslation } from 'react-i18next';

/**
 * The outline sidebar: one row per section, keyboard-selectable, with item
 * counts and boundary-disabled reordering. Renaming happens in the main
 * panel, so rows stay plain buttons.
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
                        }`}
                    >
                        <button
                            type="button"
                            aria-label={t('designer.select_section_aria', {
                                name,
                            })}
                            aria-current={selected ? 'true' : undefined}
                            onClick={() => onSelect(index)}
                            className="w-full rounded-md px-2 py-1.5 text-left"
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
                        <div className="flex justify-end gap-1 px-1 pb-1">
                            <IconButton
                                icon={faArrowUp}
                                label={t('designer.fields.move_up')}
                                disabled={index === 0}
                                onClick={() => onMove(index, index - 1)}
                                className="btn-xs"
                            />
                            <IconButton
                                icon={faArrowDown}
                                label={t('designer.fields.move_down')}
                                disabled={index === sections.length - 1}
                                onClick={() => onMove(index, index + 1)}
                                className="btn-xs"
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
