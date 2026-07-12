import {
    faCalendarDay,
    faCircleDot,
    faInputNumeric,
    faInputText,
    faSquareCheck,
} from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useTranslation } from 'react-i18next';

const TYPES = [
    { type: 'text', icon: faInputText },
    { type: 'number', icon: faInputNumeric },
    { type: 'date', icon: faCalendarDay },
    { type: 'multi', icon: faSquareCheck },
    { type: 'select', icon: faCircleDot },
];

/**
 * In-flow "add a field" bar at the end of the section's field list — one
 * button per field type, where the new card will appear.
 */
export default function AddFieldBar({ onAdd }) {
    const { t } = useTranslation();

    return (
        <div className="border-base-300 bg-base-200 rounded-box border p-3">
            <p className="text-base-content/60 mb-2 text-sm font-semibold">
                {t('designer.add_field')}
            </p>
            <div className="flex flex-wrap gap-2">
                {TYPES.map(({ type, icon }) => (
                    <button
                        key={type}
                        type="button"
                        className="btn btn-sm btn-outline"
                        onClick={() => onAdd(type)}
                    >
                        <FontAwesomeIcon icon={icon} />
                        {t(`designer.item_types.${type}`)}
                    </button>
                ))}
            </div>
        </div>
    );
}
