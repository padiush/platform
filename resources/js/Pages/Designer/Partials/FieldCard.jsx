import Card from '@/Components/Card';
import DragHandle from '@/Components/DragHandle';
import IconButton from '@/Components/IconButton';
import Input from '@/Components/Input';
import PositionSelect from '@/Components/PositionSelect';
import Select from '@/Components/Select';
import {
    effectiveName,
    itemHasOptions,
    itemSummary,
} from '@/lib/designerDraft';
import {
    faChevronDown,
    faChevronRight,
    faPlus,
    faTrashCan,
} from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useTranslation } from 'react-i18next';

/**
 * One field of the draft. Collapsed it reads as an outline row (title +
 * facts); expanded it shows the full editor. All edits go through onChange —
 * nothing talks to the server here. Reordering: drag from the grip, or use
 * the position select (keyboard/touch).
 */
export default function FieldCard({
    item,
    index,
    count,
    expanded,
    sections,
    sectionIndex,
    onToggle,
    onChange,
    onMove,
    onMoveToSection,
    onRemove,
    onDragArm,
    onDragDisarm,
}) {
    const { t } = useTranslation();

    const title = item.label.trim() || t('designer.field_n', { n: index + 1 });
    const patch = (changes) => onChange({ ...item, ...changes });

    const updateOption = (optionIndex, value) => {
        const options = [...item.options];
        options[optionIndex] = value;
        patch({ options });
    };

    return (
        <Card className="w-full">
            <div className="flex flex-wrap items-center gap-2">
                {count > 1 && (
                    <DragHandle
                        title={t('designer.drag_handle')}
                        onArm={onDragArm}
                        onDisarm={onDragDisarm}
                    />
                )}
                <button
                    type="button"
                    aria-expanded={expanded}
                    onClick={onToggle}
                    className="hover:bg-base-300 flex min-w-0 flex-1 items-center gap-3 rounded-lg px-2 py-1.5 text-left"
                >
                    <FontAwesomeIcon
                        icon={expanded ? faChevronDown : faChevronRight}
                        className="text-base-content/40 shrink-0"
                    />
                    <span className="min-w-0">
                        <span className="block truncate font-semibold">
                            {title}
                        </span>
                        <span className="text-base-content/60 mt-0.5 block truncate text-xs">
                            {itemSummary(item, t).join(' · ')}
                        </span>
                    </span>
                </button>

                <div className="flex flex-wrap items-center gap-1">
                    <PositionSelect
                        index={index}
                        count={count}
                        onMove={onMove}
                    />
                    <IconButton
                        icon={faTrashCan}
                        label={t('designer.delete_field')}
                        className="text-error"
                        onClick={onRemove}
                    />
                </div>
            </div>

            {expanded && (
                <div className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-6">
                    <Select
                        className="lg:col-span-2"
                        label={t('designer.fields.type')}
                        value={item.type}
                        onChange={(e) => {
                            const type = e.target.value;
                            patch({
                                type,
                                options:
                                    itemHasOptions({ type }) &&
                                    item.options.length === 0
                                        ? ['']
                                        : item.options,
                            });
                        }}
                    >
                        {['text', 'number', 'date', 'multi', 'select'].map(
                            (type) => (
                                <option key={type} value={type}>
                                    {t(`designer.item_types.${type}`)}
                                </option>
                            ),
                        )}
                    </Select>
                    <Input
                        className="lg:col-span-2"
                        label={t('designer.fields.label')}
                        value={item.label}
                        onChange={(e) => patch({ label: e.target.value })}
                        required
                        data-field-label
                        bottomLabel={t('designer.fields.label_description')}
                    />
                    <Input
                        className="lg:col-span-2"
                        label={t('designer.name_label')}
                        value={
                            item.nameEdited ? item.name : effectiveName(item)
                        }
                        onChange={(e) =>
                            patch({ name: e.target.value, nameEdited: true })
                        }
                        bottomLabel={t('designer.name_auto_hint')}
                    />

                    {item.type === 'number' && (
                        <>
                            <Input
                                className="lg:col-span-2"
                                label={t('designer.fields.min')}
                                type="number"
                                value={item.min ?? ''}
                                onChange={(e) => patch({ min: e.target.value })}
                            />
                            <Input
                                className="lg:col-span-2"
                                label={t('designer.fields.max')}
                                type="number"
                                value={item.max ?? ''}
                                onChange={(e) => patch({ max: e.target.value })}
                            />
                            <Input
                                className="lg:col-span-2"
                                label={t('designer.fields.step')}
                                type="number"
                                value={item.step ?? ''}
                                onChange={(e) =>
                                    patch({ step: e.target.value })
                                }
                            />
                        </>
                    )}

                    {itemHasOptions(item) && (
                        <div className="lg:col-span-6">
                            <div className="fieldset">
                                <span className="fieldset-legend">
                                    {t('designer.fields.options')}
                                </span>
                            </div>
                            <div className="flex flex-col gap-2">
                                {item.options.map((option, optionIndex) => (
                                    <div
                                        className="flex gap-2"
                                        key={optionIndex}
                                    >
                                        <input
                                            className="input input-bordered input-sm w-full"
                                            type="text"
                                            aria-label={`${t('designer.fields.options')} ${optionIndex + 1}`}
                                            value={option}
                                            onChange={(e) =>
                                                updateOption(
                                                    optionIndex,
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <IconButton
                                            icon={faTrashCan}
                                            label={`${t('actions.delete')} ${optionIndex + 1}`}
                                            className="btn-error btn-soft"
                                            onClick={() =>
                                                patch({
                                                    options:
                                                        item.options.filter(
                                                            (_, i) =>
                                                                i !==
                                                                optionIndex,
                                                        ),
                                                })
                                            }
                                        />
                                    </div>
                                ))}
                                <button
                                    type="button"
                                    className="btn btn-sm btn-primary btn-outline self-start"
                                    onClick={() =>
                                        patch({
                                            options: [...item.options, ''],
                                        })
                                    }
                                >
                                    <FontAwesomeIcon icon={faPlus} />{' '}
                                    {t('designer.fields.add_option')}
                                </button>
                            </div>
                        </div>
                    )}

                    <label className="flex items-center gap-2 lg:col-span-3">
                        <input
                            type="checkbox"
                            className="checkbox"
                            checked={item.required}
                            onChange={(e) =>
                                patch({ required: e.target.checked })
                            }
                        />
                        <span>{t('designer.fields.required')}</span>
                    </label>

                    <label className="flex items-center gap-2 lg:col-span-3">
                        <input
                            type="checkbox"
                            className="checkbox"
                            checked={item.link_to_species}
                            onChange={(e) =>
                                patch({
                                    link_to_species: e.target.checked,
                                    // A field is a taxon link or a use category,
                                    // never both — selecting one clears the other.
                                    ...(e.target.checked
                                        ? { is_use_category: false }
                                        : {}),
                                })
                            }
                        />
                        <span>{t('designer.fields.link_to_species')}</span>
                    </label>

                    <label className="flex items-center gap-2 lg:col-span-3">
                        <input
                            type="checkbox"
                            className="checkbox"
                            checked={item.is_use_category}
                            onChange={(e) =>
                                patch({
                                    is_use_category: e.target.checked,
                                    ...(e.target.checked
                                        ? { link_to_species: false }
                                        : {}),
                                })
                            }
                        />
                        <span>{t('designer.fields.use_category')}</span>
                    </label>

                    {sections.length > 1 && (
                        <div className="lg:col-span-6">
                            <select
                                className="select select-bordered select-sm w-auto"
                                aria-label={t('designer.move_to_section')}
                                value="current"
                                disabled={item.answers_count > 0}
                                title={
                                    item.answers_count > 0
                                        ? t('designer.move_has_answers')
                                        : undefined
                                }
                                onChange={(e) => {
                                    if (e.target.value !== 'current') {
                                        onMoveToSection(Number(e.target.value));
                                    }
                                }}
                            >
                                <option value="current" disabled>
                                    {t('designer.move_to_section')}
                                </option>
                                {sections.map((section, i) => (
                                    <option
                                        key={section.clientId}
                                        value={i}
                                        disabled={i === sectionIndex}
                                    >
                                        {section.name.trim() ||
                                            `${t('designer.section')} ${i + 1}`}
                                    </option>
                                ))}
                            </select>
                            {item.answers_count > 0 && (
                                <p className="text-base-content/60 mt-1 text-xs">
                                    {t('designer.summary.answers', {
                                        count: item.answers_count,
                                    })}
                                </p>
                            )}
                        </div>
                    )}
                </div>
            )}
        </Card>
    );
}
