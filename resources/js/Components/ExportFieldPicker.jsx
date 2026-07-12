import { useTranslation } from 'react-i18next';

/**
 * Field picker for the custom export, grouped by section with a select-all per
 * section. Enforces the repeatable rule live: once a field is picked, sections
 * of the other repeatability are disabled (they can't share a sheet), with an
 * inline explanation — instead of the old post-submit error.
 */
export default function ExportFieldPicker({
    sections,
    selected,
    onToggleField,
    onToggleSection,
}) {
    const { t } = useTranslation();

    const activeSection = sections.find((section) =>
        section.items.some((item) => selected.has(item.id)),
    );
    const activeRepeatable = activeSection ? activeSection.repeatable : null;

    return (
        <div className="flex flex-col gap-4">
            {sections.map((section) => {
                const disabled =
                    activeRepeatable !== null &&
                    section.repeatable !== activeRepeatable;
                const allChecked =
                    section.items.length > 0 &&
                    section.items.every((item) => selected.has(item.id));

                return (
                    <div
                        key={section.id}
                        className={`border-base-300 rounded-box border p-3 ${disabled ? 'opacity-50' : ''}`}
                    >
                        <div className="mb-2 flex items-center justify-between gap-2">
                            <span className="font-medium">
                                {section.name}{' '}
                                <span className="badge badge-ghost badge-sm">
                                    {section.repeatable
                                        ? t('data.repeatable')
                                        : t('data.unique')}
                                </span>
                            </span>
                            <label className="label cursor-pointer gap-2 text-xs">
                                <span>{t('data.export.select_all')}</span>
                                <input
                                    type="checkbox"
                                    className="checkbox checkbox-sm"
                                    checked={allChecked}
                                    disabled={disabled}
                                    onChange={(e) =>
                                        onToggleSection(
                                            section,
                                            e.target.checked,
                                        )
                                    }
                                />
                            </label>
                        </div>

                        {disabled && (
                            <p className="text-base-content/60 mb-2 text-xs">
                                {t('data.export.repeatable_locked')}
                            </p>
                        )}

                        <div className="grid grid-cols-1 gap-1 sm:grid-cols-2">
                            {section.items.map((item) => (
                                <label
                                    key={item.id}
                                    className="label cursor-pointer justify-start gap-2"
                                >
                                    <input
                                        type="checkbox"
                                        className="checkbox checkbox-sm"
                                        checked={selected.has(item.id)}
                                        disabled={disabled}
                                        onChange={() => onToggleField(item.id)}
                                    />
                                    <span className="truncate">
                                        {item.label}
                                    </span>
                                </label>
                            ))}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
