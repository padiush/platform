import Card from '@/Components/Card';
import { downloadChartPng, downloadChartSvg } from '@/utils/chartExport';
import { faDownload } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

/**
 * A Card that hosts a single chart and offers a download of it (top-right) as
 * PNG or SVG, with per-download options — background and PNG resolution — so a
 * researcher can match a paper/slide. Options are ephemeral (not persisted).
 */
export default function ChartCard({ title, filename, children }) {
    const { t } = useTranslation();
    const bodyRef = useRef(null);
    const [background, setBackground] = useState('themed');
    const [scale, setScale] = useState(2);

    const backgroundColor = () => {
        if (background === 'white') return '#ffffff';
        if (background === 'transparent') return 'transparent';
        const card = bodyRef.current?.closest('.card');
        return card ? getComputedStyle(card).backgroundColor : '#ffffff';
    };

    const svg = () => bodyRef.current?.querySelector('svg');

    const download = (format) => {
        const target = svg();
        if (!target) return;

        if (format === 'svg') {
            downloadChartSvg(target, `${filename}.svg`, {
                background: backgroundColor(),
            });
        } else {
            downloadChartPng(target, `${filename}.png`, {
                background: backgroundColor(),
                scale,
            });
        }
        // Close the dropdown.
        document.activeElement?.blur();
    };

    return (
        <Card title={title}>
            {/* Toolbar in the body (full width) so the options menu, opened with
                dropdown-end, always stays on-screen — the header-actions slot
                shifts sides across breakpoints and would clip. */}
            <div className="flex justify-end">
                <div className="dropdown dropdown-end">
                    <button
                        type="button"
                        tabIndex={0}
                        className="btn btn-ghost btn-sm"
                    >
                        <FontAwesomeIcon icon={faDownload} />
                        {t('data.reports.charts.download')}
                    </button>
                    <div
                        tabIndex={0}
                        className="dropdown-content bg-base-200 rounded-box z-10 mt-1 w-60 space-y-3 p-3 shadow"
                    >
                        <label className="block">
                            <span className="text-xs opacity-70">
                                {t('data.reports.charts.background')}
                            </span>
                            <select
                                className="select select-bordered select-sm mt-1 w-full"
                                value={background}
                                onChange={(event) =>
                                    setBackground(event.target.value)
                                }
                            >
                                <option value="themed">
                                    {t('data.reports.charts.bg_themed')}
                                </option>
                                <option value="white">
                                    {t('data.reports.charts.bg_white')}
                                </option>
                                <option value="transparent">
                                    {t('data.reports.charts.bg_transparent')}
                                </option>
                            </select>
                        </label>

                        <label className="block">
                            <span className="text-xs opacity-70">
                                {t('data.reports.charts.resolution')}
                            </span>
                            <select
                                className="select select-bordered select-sm mt-1 w-full"
                                value={scale}
                                onChange={(event) =>
                                    setScale(Number(event.target.value))
                                }
                            >
                                <option value={1}>1×</option>
                                <option value={2}>2×</option>
                                <option value={3}>3×</option>
                            </select>
                            <span className="text-[10px] opacity-50">
                                {t('data.reports.charts.resolution_note')}
                            </span>
                        </label>

                        <div className="flex gap-2">
                            <button
                                type="button"
                                className="btn btn-primary btn-sm flex-1"
                                onClick={() => download('png')}
                            >
                                {t('data.reports.charts.png')}
                            </button>
                            <button
                                type="button"
                                className="btn btn-outline btn-sm flex-1"
                                onClick={() => download('svg')}
                            >
                                {t('data.reports.charts.svg')}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div ref={bodyRef}>{children}</div>
        </Card>
    );
}
