import Input from '@/Components/Input';
import Select from '@/Components/Select';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

/**
 * How a field record reads beside the answer it came out of: by the number it
 * is known by, or as an observation when nothing was taken to number.
 */
function recordLabel(record, t) {
    if (record.accession_number || record.collection_number) {
        return record.accession_number ?? record.collection_number;
    }

    return record.was_collected === false
        ? t('catalogs.fieldRecords.basis_human_observation')
        : t('interviews.field_record_unnumbered');
}

/**
 * The field records made from this answer: an informant named a plant, and it
 * was recorded there and then, on the companion (ADR 0011). Each opens on its
 * row in the catalog for someone who can read the catalog.
 *
 * A plain link rather than an Inertia one: a full visit lands on the row's
 * anchor, which is what marks it.
 */
function AnswerRecords({ records, catalogProjectId }) {
    const { t } = useTranslation();

    if (!records?.length) {
        return null;
    }

    return (
        <div className="mt-1 flex flex-wrap items-center gap-2 text-xs">
            <span className="text-base-content/70">
                {t('interviews.field_records')}
            </span>
            {records.map((record) =>
                catalogProjectId ? (
                    <a
                        key={record.id}
                        href={`${route('catalogs.fieldRecords.index', catalogProjectId)}#record-${record.id}`}
                        className="badge badge-outline badge-sm hover:badge-primary"
                    >
                        {recordLabel(record, t)}
                    </a>
                ) : (
                    <span
                        key={record.id}
                        className="badge badge-outline badge-sm"
                    >
                        {recordLabel(record, t)}
                    </span>
                ),
            )}
        </div>
    );
}

function hasContent(type, value) {
    if (type === 'multi') {
        return Array.isArray(value) && value.length > 0;
    }

    return typeof value === 'string' ? value.trim() !== '' : value != null;
}

export default function ItemRender({
    item,
    instance,
    repeatableIndex = null,
    answers = [],
    catalogProjectId = null,
    onAnswered = () => {},
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
    // idle | saving | saved | error — every save is visible to the person
    // holding the phone in the field.
    const [saveState, setSaveState] = useState('idle');
    const savedTimeout = useRef(null);

    // Re-sync the field when the stored answer changes underneath us — e.g.
    // after a repeatable set is removed, the remaining sets are reindexed and
    // the page reloads, so this component now maps to a different answer.
    // Without this, the field would keep showing its old (stale) value.
    useEffect(() => {
        setValue(deriveValue());
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [persistedValue, repeatableIndex]);

    useEffect(
        () => () => {
            if (savedTimeout.current) {
                clearTimeout(savedTimeout.current);
            }
        },
        [],
    );

    const submit = async () => {
        setSaveState('saving');

        try {
            await axios.post(route('interviews.save_answer', instance.id), {
                item_id: item.id,
                repeatable_index: repeatableIndex,
                value: typeof value === 'string' ? value.trim() : value,
            });
            setError(null);
            setSaveState('saved');
            onAnswered(item.id, repeatableIndex, hasContent(item.type, value));
            savedTimeout.current = setTimeout(
                () =>
                    setSaveState((state) =>
                        state === 'saved' ? 'idle' : state,
                    ),
                2000,
            );
        } catch {
            setError(t('designer.save_error'));
            setSaveState('error');
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

    const control = (() => {
        switch (item.type) {
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
                        <div className="flex flex-col gap-3">
                            {options.map((opt, idx) => (
                                <label
                                    key={idx}
                                    className="label cursor-pointer justify-start gap-3"
                                >
                                    <input
                                        type="checkbox"
                                        className="checkbox checkbox-lg sm:checkbox-md"
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
                                    <span className="text-base">{opt}</span>
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
                        <option value="">
                            {t('designer.select_placeholder')}
                        </option>
                        {options.map((opt, idx) => (
                            <option key={idx} value={opt}>
                                {opt}
                            </option>
                        ))}
                    </Select>
                );

            case 'text':
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
    })();

    return (
        <div>
            {control}
            <p
                aria-live="polite"
                className={`mt-1 min-h-4 text-xs ${
                    saveState === 'saved'
                        ? 'text-success'
                        : 'text-base-content/50'
                }`}
            >
                {saveState === 'saving' && t('interviews.saving')}
                {saveState === 'saved' && t('interviews.saved')}
            </p>
            <AnswerRecords
                records={matchingAnswer?.field_records}
                catalogProjectId={catalogProjectId}
            />
        </div>
    );
}
