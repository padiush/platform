import { useTranslation } from 'react-i18next';

/**
 * The required / "at least one" markers that qualify a field's label.
 *
 * Both are hidden from assistive tech: `required` is already on the control
 * itself, so announcing a bare asterisk only adds noise. The selective rule has
 * no equivalent attribute, so it carries its own text alternative instead.
 */
export function RequiredMarks({ required = false, selective = false }) {
    const { t } = useTranslation();

    if (!required && !selective) {
        return null;
    }

    return (
        <>
            {required && (
                <span
                    className="text-error tooltip tooltip-right select-none"
                    data-tip={t('designer.required')}
                    aria-hidden="true"
                >
                    *
                </span>
            )}
            {selective && (
                <>
                    <span
                        className="text-warning tooltip tooltip-right select-none"
                        data-tip={t('validation.at_least_one')}
                        aria-hidden="true"
                    >
                        *
                    </span>
                    <span className="sr-only">
                        {t('validation.at_least_one')}
                    </span>
                </>
            )}
        </>
    );
}

/**
 * The label above a form control.
 *
 * daisyUI's `.fieldset-legend` is a flex row with `justify-content:
 * space-between`, so a marker rendered as a sibling of the label text gets
 * pushed to the opposite edge of the field — a red asterisk floating an inch
 * away from the word it qualifies. Wrapping the text and its markers in a
 * single flex child keeps them together.
 */
export default function FieldLabel({
    id,
    label,
    required = false,
    selective = false,
}) {
    if (!label) {
        return null;
    }

    return (
        <label htmlFor={id} className="fieldset-legend">
            <span className="inline-flex items-baseline gap-1">
                {label}
                <RequiredMarks required={required} selective={selective} />
            </span>
        </label>
    );
}
