import ReleaseNotes from '@/Components/ReleaseNotes';
import { releasesSince } from '@/lib/releases';
import { faWandMagicSparkles } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

/**
 * "What's new?", once, after an update: the notes of every release since the
 * last one this user saw. Dismissing it in any way — the button, Escape, the
 * backdrop, or following the link to all the notes — counts as seen, so it
 * never comes back for the same release.
 *
 * A release that shipped without notes is marked seen without a dialog.
 */
export default function WhatsNewDialog() {
    const { release } = usePage().props;
    const since = release?.unseenSince ?? null;

    const { t } = useTranslation();
    const notes = useTranslation('whatsnew', { useSuspense: false });
    const [dismissed, setDismissed] = useState(false);
    const ref = useRef(null);

    const unseen =
        since && notes.ready
            ? releasesSince(
                  notes.t('releases', { returnObjects: true }),
                  since,
                  release.version,
              )
            : [];

    const markSeen = () => {
        setDismissed(true);
        router.post(
            route('whats-new.seen'),
            {},
            { preserveScroll: true, preserveState: true },
        );
    };

    const open = Boolean(since) && !dismissed && unseen.length > 0;
    const nothingToShow =
        Boolean(since) && !dismissed && notes.ready && unseen.length === 0;

    useEffect(() => {
        if (nothingToShow) {
            markSeen();
        }
        // markSeen only changes what it closes over, never what it does.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [nothingToShow]);

    useEffect(() => {
        const dialog = ref.current;
        if (!dialog) return;

        if (open && !dialog.open) {
            dialog.showModal();
        } else if (!open && dialog.open) {
            dialog.close();
        }
    }, [open]);

    if (!since) {
        return null;
    }

    return (
        <dialog
            ref={ref}
            className="modal modal-bottom sm:modal-middle"
            aria-labelledby="whats-new-title"
            onCancel={(event) => {
                event.preventDefault();
                markSeen();
            }}
        >
            <div className="modal-box flex max-h-[85vh] flex-col gap-0 p-0 sm:max-w-lg">
                <div className="from-primary/15 to-base-100 flex items-center gap-4 bg-gradient-to-b px-6 pt-6 pb-4">
                    <span className="bg-primary text-primary-content flex size-11 shrink-0 items-center justify-center rounded-full shadow-sm">
                        <FontAwesomeIcon
                            icon={faWandMagicSparkles}
                            aria-hidden="true"
                        />
                    </span>
                    <div className="min-w-0">
                        <h2 id="whats-new-title" className="text-xl font-bold">
                            {t('whatsNew.title')}
                        </h2>
                        <p className="text-base-content/70 text-sm">
                            {t('whatsNew.intro')}
                        </p>
                    </div>
                </div>

                <div className="overflow-y-auto px-6 py-4">
                    <ReleaseNotes releases={unseen} />
                </div>

                <div className="border-base-300 flex flex-col-reverse gap-2 border-t px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <Link
                        href={route('whats-new')}
                        className="btn btn-ghost btn-sm"
                        onClick={markSeen}
                    >
                        {t('whatsNew.see_all')}
                    </Link>
                    <button
                        type="button"
                        className="btn btn-primary"
                        onClick={markSeen}
                        autoFocus
                    >
                        {t('whatsNew.got_it')}
                    </button>
                </div>
            </div>
            <button
                type="button"
                className="modal-backdrop"
                aria-label={t('actions.close')}
                onClick={markSeen}
            />
        </dialog>
    );
}
