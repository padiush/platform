import Input from '@/Components/Input';
import Select from '@/Components/Select';
import axios from 'axios';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function ItemRender({
    item,
    instance,
    repeatableIndex = null,
    answers = [],
}) {
    const { t } = useTranslation();

    const matchingAnswer = answers.find(
        (ans) =>
            ans.item_id === item.id &&
            (ans.repeatable_index ?? null) === (repeatableIndex ?? null),
    );

    const persistedValue = matchingAnswer?.value ?? null;

    const deriveValue = () =>
        item.type === 'multi'
            ? (() => {
                  try {
                      const parsed = JSON.parse(persistedValue || '[]');
                      return Array.isArray(parsed) ? parsed : [];
                  } catch {
                      return [];
                  }
              })()
            : (persistedValue ?? '');

    const [value, setValue] = useState(deriveValue);
    const [error, setError] = useState(null);

    // Re-sync the field when the stored answer changes underneath us — e.g.
    // after a repeatable set is removed, the remaining sets are reindexed and
    // the page reloads, so this component now maps to a different answer.
    // Without this, the field would keep showing its old (stale) value.
    useEffect(() => {
        setValue(deriveValue());
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [persistedValue, repeatableIndex]);

    const submit = async () => {
        try {
            await axios.post(route('interviews.save_answer', instance.id), {
                item_id: item.id,
                repeatable_index: repeatableIndex,
                value: typeof value === 'string' ? value.trim() : value,
            });
            setError(null);
        } catch (err) {
            setError(t('designer.save_error'));
        }
    };

    const isRequired = item.required ?? false;

    const options = Array.isArray(item.options)
        ? item.options
        : typeof item.options === 'string'
          ? (() => {
                try {
                    const parsed = JSON.parse(item.options);
                    return Array.isArray(parsed) ? parsed : [];
                } catch {
                    return [];
                }
            })()
          : [];

    const handleChange = (newValue) => {
        setValue(newValue);
    };

    switch (item.type) {
        case 'text':
            return (
                <Input
                    label={item.label}
                    name={item.name}
                    required={isRequired}
                    value={value}
                    onChange={(e) => handleChange(e.target.value)}
                    onBlur={submit}
                    error={error}
                />
            );

        case 'number':
            return (
                <Input
                    label={item.label}
                    name={item.name}
                    type="number"
                    min={item.min}
                    max={item.max}
                    step={item.step}
                    required={isRequired}
                    value={value}
                    onChange={(e) => handleChange(e.target.value)}
                    onBlur={submit}
                    error={error}
                />
            );

        case 'date':
            return (
                <Input
                    label={item.label}
                    name={item.name}
                    type="date"
                    required={isRequired}
                    value={value}
                    onChange={(e) => handleChange(e.target.value)}
                    onBlur={submit}
                    error={error}
                />
            );

        case 'multi':
            return (
                <fieldset className="fieldset w-full">
                    <legend className="fieldset-legend">
                        {item.label}{' '}
                        {isRequired && (
                            <span
                                className="text-error tooltip tooltip-bottom"
                                data-tip={t('designer.required')}
                            >
                                *
                            </span>
                        )}
                    </legend>
                    <div className="flex flex-col gap-2">
                        {options.map((opt, idx) => (
                            <label
                                key={idx}
                                className="label cursor-pointer gap-2"
                            >
                                <input
                                    type="checkbox"
                                    className="checkbox"
                                    onChange={(e) => {
                                        const newValue = e.target.checked
                                            ? [...(value || []), opt]
                                            : (value || []).filter(
                                                  (v) => v !== opt,
                                              );
                                        handleChange(newValue);
                                    }}
                                    onBlur={submit}
                                    checked={(value || []).includes(opt)}
                                />
                                <span>{opt}</span>
                            </label>
                        ))}
                    </div>
                </fieldset>
            );

        case 'select':
            return (
                <Select
                    label={item.label}
                    required={isRequired}
                    value={value}
                    onChange={(e) => handleChange(e.target.value)}
                    onBlur={submit}
                    error={error}
                >
                    <option value="">{t('designer.select_placeholder')}</option>
                    {options.map((opt, idx) => (
                        <option key={idx} value={opt}>
                            {opt}
                        </option>
                    ))}
                </Select>
            );

        default:
            return (
                <Input
                    label={item.label}
                    name={item.name}
                    required={isRequired}
                    value={value}
                    onChange={(e) => handleChange(e.target.value)}
                    onBlur={submit}
                    error={error}
                />
            );
    }
}
