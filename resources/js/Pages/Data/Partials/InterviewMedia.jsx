import { faMicrophone } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import axios from 'axios';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

/** A recording's length as the field app shows it: 8:05. */
function formatClock(totalSeconds) {
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    return `${minutes}:${String(seconds).padStart(2, '0')}`;
}

function Recording({ medium, number }) {
    const { t } = useTranslation();

    return (
        <div className="border-base-300 rounded-box space-y-3 border p-3">
            <div className="flex items-center gap-3">
                <span className="bg-primary/10 text-primary flex size-9 shrink-0 items-center justify-center rounded-full">
                    <FontAwesomeIcon icon={faMicrophone} />
                </span>
                <span className="font-semibold">
                    {t('data.media.recording_label', { number })}
                    {medium.duration_s
                        ? ` · ${formatClock(medium.duration_s)}`
                        : ''}
                </span>
            </div>

            <audio controls src={medium.url} className="w-full">
                <track kind="captions" />
            </audio>

            {/*
                Shown only when a transcript exists. It stays null until a real
                queue and a transcriber are provisioned, and an empty box would
                read as "nothing was said".
            */}
            {medium.transcription_text && (
                <div className="bg-base-200/40 rounded-box p-3 text-sm">
                    <span className="text-base-content/60 mb-1 block text-xs">
                        {t('data.media.transcript')}
                    </span>
                    {medium.transcription_text}
                </div>
            )}
        </div>
    );
}

function Group({ title, children }) {
    return (
        <section className="space-y-3">
            {title && (
                <h3 className="text-base-content/60 text-xs font-semibold tracking-wide uppercase">
                    {title}
                </h3>
            )}
            {children}
        </section>
    );
}

/**
 * What the companion captured against one interview, laid out as the field app
 * lays it out: the recordings, numbered, then the photographs. Given a `kind`,
 * only that one, for a page placing each where the app does.
 *
 * Read-only: the device authors this material and owns its lifecycle, so the
 * web shows it rather than managing it. Fetched on open instead of shipped with
 * every row — a page of interviews would otherwise carry every URL and
 * transcription whether or not anyone looked.
 */
export default function InterviewMedia({ project, instanceId, kind = null }) {
    const { t } = useTranslation();
    // Kept with the interview it was fetched for, so another interview's
    // media never shows while this one's is loading.
    const key = `${project.id}:${instanceId}`;
    const [fetched, setFetched] = useState({ key: null });
    const media = fetched.key === key ? (fetched.media ?? null) : null;
    const error = fetched.key === key && Boolean(fetched.error);

    useEffect(() => {
        let active = true;

        axios
            .get(
                route('data.media.index', {
                    project: project.id,
                    instance: instanceId,
                }),
            )
            .then(
                ({ data }) =>
                    active && setFetched({ key, media: data.media ?? [] }),
            )
            .catch(() => active && setFetched({ key, error: true }));

        return () => {
            active = false;
        };
    }, [key, project.id, instanceId]);

    if (error) {
        return <p className="text-error text-sm">{t('data.media.failed')}</p>;
    }

    if (media === null) {
        return (
            <p className="text-base-content/60 text-sm">
                {t('data.media.loading')}
            </p>
        );
    }

    const recordings =
        kind === 'photo' ? [] : media.filter((m) => m.kind !== 'photo');
    const photos =
        kind === 'audio' ? [] : media.filter((m) => m.kind === 'photo');

    if (recordings.length === 0 && photos.length === 0) {
        return (
            <p className="text-base-content/60 text-sm">
                {t('data.media.none')}
            </p>
        );
    }

    return (
        <div className="space-y-6">
            {recordings.length > 0 && (
                <Group title={kind ? null : t('data.media.audio_title')}>
                    {recordings.map((medium, index) => (
                        <Recording
                            key={medium.id}
                            medium={medium}
                            number={index + 1}
                        />
                    ))}
                </Group>
            )}

            {photos.length > 0 && (
                <Group title={kind ? null : t('data.media.photos_title')}>
                    <div className="flex flex-wrap gap-3">
                        {photos.map((medium) => (
                            <img
                                key={medium.id}
                                src={medium.url}
                                alt={t('data.media.photo_alt')}
                                className="max-h-72 max-w-full rounded object-contain"
                            />
                        ))}
                    </div>
                </Group>
            )}
        </div>
    );
}
