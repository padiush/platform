import { useTranslation } from 'react-i18next';

/**
 * Jump-to-position control: the keyboard- and touch-accessible counterpart
 * of drag-and-drop reordering. Hidden when there is nothing to reorder.
 */
export default function PositionSelect({ index, count, onMove }) {
    const { t } = useTranslation();

    if (count < 2) {
        return null;
    }

    return (
        <select
            className="select select-bordered select-sm w-auto"
            aria-label={t('designer.position_label', {
                position: index + 1,
                count,
            })}
            value={index}
            onChange={(e) => onMove(Number(e.target.value))}
        >
            {Array.from({ length: count }, (_, i) => (
                <option key={i} value={i}>
                    {t('designer.position_label', {
                        position: i + 1,
                        count,
                    })}
                </option>
            ))}
        </select>
    );
}
