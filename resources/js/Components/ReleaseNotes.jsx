import { formatLongDate } from '@/utils/datetime';
import { faCircleCheck } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useTranslation } from 'react-i18next';

/**
 * The notes of one or more releases, newest first: each a version, the day it
 * shipped, and what it brought.
 *
 * @param {{ releases: { version: string, date: ?string, items: string[] }[] }} props
 */
export default function ReleaseNotes({ releases }) {
    const { t, i18n } = useTranslation();

    return (
        <div className="flex flex-col gap-6">
            {releases.map((release) => (
                <section
                    key={release.version}
                    aria-labelledby={`release-${release.version}`}
                >
                    <div className="mb-3 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                        <h3
                            id={`release-${release.version}`}
                            className="text-base font-bold"
                        >
                            {t('whatsNew.version', {
                                version: release.version,
                            })}
                        </h3>
                        {release.date && (
                            <span className="text-base-content/60 text-xs">
                                {t('whatsNew.released_on', {
                                    // Midday, so no time zone moves it a day.
                                    date: formatLongDate(
                                        `${release.date}T12:00:00`,
                                        i18n.language,
                                    ),
                                })}
                            </span>
                        )}
                    </div>
                    <ul className="flex flex-col gap-2.5">
                        {(release.items ?? []).map((item, index) => (
                            <li
                                key={index}
                                className="flex gap-3 text-sm leading-relaxed"
                            >
                                <FontAwesomeIcon
                                    icon={faCircleCheck}
                                    className="text-primary mt-1 shrink-0"
                                    aria-hidden="true"
                                />
                                <span>{item}</span>
                            </li>
                        ))}
                    </ul>
                </section>
            ))}
        </div>
    );
}
