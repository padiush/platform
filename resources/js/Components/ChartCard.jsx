import Card from '@/Components/Card';
import { downloadChartPng } from '@/utils/chartExport';
import { faDownload } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useRef } from 'react';
import { useTranslation } from 'react-i18next';

/**
 * A Card that hosts a single chart and offers a PNG download of it (top-right).
 * The chart is the children; the download grabs the rendered <svg> and exports
 * it over the card's own background.
 */
export default function ChartCard({ title, filename, children }) {
    const { t } = useTranslation();
    const bodyRef = useRef(null);

    const handleDownload = () => {
        const svg = bodyRef.current?.querySelector('svg');
        if (!svg) return;

        const card = bodyRef.current.closest('.card');
        const background = card
            ? getComputedStyle(card).backgroundColor
            : '#ffffff';

        downloadChartPng(svg, filename, { background });
    };

    return (
        <Card
            title={title}
            actions={
                <button
                    type="button"
                    className="btn btn-ghost btn-sm"
                    onClick={handleDownload}
                >
                    <FontAwesomeIcon icon={faDownload} />
                    {t('data.reports.charts.download')}
                </button>
            }
        >
            <div ref={bodyRef}>{children}</div>
        </Card>
    );
}
