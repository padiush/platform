import Card from '@/Components/Card';
import { formatDateTime } from '@/utils/datetime';
import { useTranslation } from 'react-i18next';

/**
 * Interviews carrying photographs or audio and no answers. The table builds its
 * rows from answers, so without this they could not be reached from here at
 * all.
 */
export default function UnansweredInterviews({ interviews, onOpen }) {
    const { t, i18n } = useTranslation();

    if (interviews.length === 0) {
        return null;
    }

    return (
        <Card
            className="mb-4 w-full"
            title={t('data.media.unanswered_title')}
            description={t('data.media.unanswered_hint')}
        >
            <ul className="divide-base-300 divide-y">
                {interviews.map((interview) => (
                    <li
                        key={interview.id}
                        className="flex flex-wrap items-center justify-between gap-2 py-2"
                    >
                        <span className="text-sm">
                            {[
                                formatDateTime(
                                    interview.recorded_at,
                                    i18n.language,
                                ),
                                interview.recorder,
                            ]
                                .filter(Boolean)
                                .join(' · ')}
                        </span>
                        <button
                            type="button"
                            className="btn btn-ghost btn-xs"
                            onClick={() => onOpen(interview.id)}
                        >
                            {t('data.media.title')} ({interview.media_count})
                        </button>
                    </li>
                ))}
            </ul>
        </Card>
    );
}
