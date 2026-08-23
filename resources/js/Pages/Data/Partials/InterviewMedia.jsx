import axios from 'axios';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

/**
 * What the companion captured against one interview.
 *
 * Read-only: the device authors this material and owns its lifecycle, so the
 * web shows it rather than managing it. Fetched on open instead of shipped with
 * every row — a page of interviews would otherwise carry every URL and
 * transcription whether or not anyone looked.
 */
export default function InterviewMedia({ project, instanceId }) {
    const { t } = useTranslation();
    const [media, setMedia] = useState(null);
    const [error, setError] = useState(false);

    useEffect(() => {
        let active = true;
        setMedia(null);
        setError(false);

        axios
            .get(
                route('data.media.index', {
                    project: project.id,
                    instance: instanceId,
                }),
            )
            .then(({ data }) => active && setMedia(data.media ?? []))
            .catch(() => active && setError(true));

        return () => {
            active = false;
        };
    }, [project.id, instanceId]);

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

    if (media.length === 0) {
        return (
            <p className="text-base-content/60 text-sm">
                {t('data.media.none')}
            </p>
        );
    }

    return (
        <div className="space-y-4">
            {media.map((medium) => (
                <figure key={medium.id} className="space-y-2">
                    {medium.kind === 'photo' ? (
                        <img
                            src={medium.url}
                            alt={t('data.media.photo_alt')}
                            className="max-h-72 rounded object-contain"
                        />
                    ) : (
                        <audio controls src={medium.url} className="w-full">
                            <track kind="captions" />
                        </audio>
                    )}

                    {/*
                        Shown only when a transcript exists. It stays null until
                        a real queue and a transcriber are provisioned, and an
                        empty box would read as "nothing was said".
                    */}
                    {medium.transcription_text && (
                        <figcaption className="bg-base-200/40 rounded-box p-3 text-sm">
                            <span className="text-base-content/60 mb-1 block text-xs">
                                {t('data.media.transcript')}
                            </span>
                            {medium.transcription_text}
                        </figcaption>
                    )}
                </figure>
            ))}
        </div>
    );
}
