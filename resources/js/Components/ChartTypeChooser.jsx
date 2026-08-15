import {
    faChartColumn,
    faChartLine,
    faChartPie,
    faTable,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useTranslation } from 'react-i18next';

const ICONS = {
    bar: faChartColumn,
    pie: faChartPie,
    line: faChartLine,
    table: faTable,
};

/**
 * Segmented icon buttons for picking a summary chart type. Only the types in
 * `available` (valid for the field's kind) are shown.
 */
export default function ChartTypeChooser({ available, value, onChange }) {
    const { t } = useTranslation();

    if (!available || available.length < 2) {
        return null;
    }

    return (
        <div className="join">
            {available.map((type) => (
                <button
                    key={type}
                    type="button"
                    aria-label={t(`data.view.chart.${type}`)}
                    aria-pressed={value === type}
                    className={`btn btn-xs join-item ${value === type ? 'btn-primary' : 'btn-ghost'}`}
                    onClick={() => onChange(type)}
                >
                    <FontAwesomeIcon icon={ICONS[type]} />
                </button>
            ))}
        </div>
    );
}
