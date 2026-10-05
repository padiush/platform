import FormModal from '@/Components/FormModal';
import { formatBytes } from '@/utils/bytes';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

/**
 * Deleting an account, said in full before it happens: the projects it
 * created go with it, their members lose them, their files are erased, and
 * its work in other people's projects stays. Its projects can be transferred
 * to someone else first. Confirmed by typing the account's email.
 */
export default function DeleteUserModal({ user, people, onClose }) {
    const { t, i18n } = useTranslation();
    const [preview, setPreview] = useState(null);
    const [failed, setFailed] = useState(false);
    const [typed, setTyped] = useState('');
    const [transferring, setTransferring] = useState(false);
    const [recipient, setRecipient] = useState('');
    const [busy, setBusy] = useState(false);

    const load = useCallback(() => {
        setFailed(false);
        axios
            .get(route('system.users.deletion', user.id))
            .then(({ data }) => setPreview(data))
            .catch(() => setFailed(true));
    }, [user]);

    useEffect(() => {
        setPreview(null);
        setTyped('');
        setTransferring(false);
        setRecipient('');
        if (user) {
            load();
        }
    }, [user, load]);

    const close = () => {
        if (!busy) onClose();
    };

    const transfer = () => {
        setBusy(true);
        router.post(
            route('system.users.transfer', user.id),
            { to: recipient },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setTransferring(false);
                    load();
                },
                onFinish: () => setBusy(false),
            },
        );
    };

    const destroy = () => {
        setBusy(true);
        router.delete(route('system.users.delete', user.id), {
            preserveScroll: true,
            onSuccess: onClose,
            onFinish: () => setBusy(false),
        });
    };

    const size = (bytes) => formatBytes(bytes, i18n.language);
    const confirmed =
        preview &&
        typed.trim().toLowerCase() === preview.user.email.toLowerCase();
    const others = people.filter((person) => person.id !== user?.id);

    return (
        <FormModal
            open={user !== null}
            onClose={close}
            title={user ? t('system.deletion.title', { name: user.name }) : ''}
        >
            <div className="space-y-4">
                <p className="text-sm">{t('system.deletion.intro')}</p>

                {failed && (
                    <div
                        role="alert"
                        className="alert alert-error alert-soft text-sm"
                    >
                        {t('system.deletion.failed')}
                    </div>
                )}

                {!preview && !failed && (
                    <p className="text-base-content/70 text-sm" role="status">
                        {t('system.deletion.loading')}
                    </p>
                )}

                {preview && (
                    <>
                        <section>
                            <h3 className="text-error text-xs font-bold tracking-[0.14em] uppercase">
                                {t('system.deletion.goes')}
                            </h3>
                            <ul className="divide-base-300 divide-y text-sm">
                                <li className="py-2.5">
                                    {preview.projects.length === 0 ? (
                                        t('system.deletion.no_projects')
                                    ) : (
                                        <>
                                            <span className="font-semibold">
                                                {t('system.deletion.projects', {
                                                    count: preview.projects
                                                        .length,
                                                })}
                                            </span>
                                            <ul className="text-base-content/70 mt-1 space-y-0.5">
                                                {preview.projects.map(
                                                    (project) => (
                                                        <li
                                                            key={project.id}
                                                            className="break-words"
                                                        >
                                                            {project.name}
                                                            {' · '}
                                                            {project.is_example
                                                                ? t(
                                                                      'system.deletion.example',
                                                                  )
                                                                : `${t('system.deletion.interviews', { count: project.interviews })} · ${size(project.bytes)}`}
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        </>
                                    )}
                                </li>
                                {preview.collaborators.count > 0 && (
                                    <li className="py-2.5">
                                        {t('system.deletion.collaborators', {
                                            count: preview.collaborators.count,
                                            names: names(
                                                preview.collaborators,
                                                t,
                                                i18n.language,
                                            ),
                                        })}
                                    </li>
                                )}
                                {preview.files > 0 && (
                                    <li className="py-2.5">
                                        {t('system.deletion.files', {
                                            count: preview.files,
                                            size: size(preview.bytes),
                                        })}
                                    </li>
                                )}
                            </ul>
                        </section>

                        <section>
                            <h3 className="text-success text-xs font-bold tracking-[0.14em] uppercase">
                                {t('system.deletion.stays')}
                            </h3>
                            <p className="py-2.5 text-sm">
                                {preview.other_projects > 0
                                    ? t('system.deletion.others', {
                                          count: preview.other_projects,
                                      })
                                    : t('system.deletion.others_none')}
                            </p>
                        </section>

                        {preview.transferable > 0 && (
                            <section className="bg-primary/10 rounded-box space-y-3 p-3">
                                <div className="flex flex-wrap items-center gap-3">
                                    <p className="min-w-0 flex-1 text-sm">
                                        {t('system.deletion.transfer_hint')}
                                    </p>
                                    {!transferring && (
                                        <button
                                            type="button"
                                            className="btn btn-sm"
                                            onClick={() =>
                                                setTransferring(true)
                                            }
                                        >
                                            {t('system.deletion.transfer')}
                                        </button>
                                    )}
                                </div>
                                {transferring && (
                                    <div className="flex flex-wrap items-end gap-2">
                                        <label className="flex min-w-0 flex-1 flex-col gap-1 text-sm font-semibold">
                                            {t('system.deletion.transfer_to')}
                                            <select
                                                className="select select-sm w-full"
                                                value={recipient}
                                                onChange={(event) =>
                                                    setRecipient(
                                                        event.target.value,
                                                    )
                                                }
                                            >
                                                <option value="">
                                                    {t(
                                                        'system.deletion.transfer_choose',
                                                    )}
                                                </option>
                                                {others.map((person) => (
                                                    <option
                                                        key={person.id}
                                                        value={person.id}
                                                    >
                                                        {person.name} ·{' '}
                                                        {person.email}
                                                    </option>
                                                ))}
                                            </select>
                                        </label>
                                        <button
                                            type="button"
                                            className="btn btn-primary btn-sm"
                                            disabled={!recipient || busy}
                                            onClick={transfer}
                                        >
                                            {t(
                                                'system.deletion.transfer_confirm',
                                            )}
                                        </button>
                                    </div>
                                )}
                                {transferring && (
                                    <p className="text-base-content/70 text-sm">
                                        {t('system.deletion.transfer_note')}
                                    </p>
                                )}
                            </section>
                        )}

                        <label className="flex flex-col gap-1 text-sm font-semibold">
                            <span className="break-words">
                                {t('system.deletion.confirm_label', {
                                    email: preview.user.email,
                                })}
                            </span>
                            <input
                                type="text"
                                className="input w-full"
                                autoComplete="off"
                                value={typed}
                                onChange={(event) =>
                                    setTyped(event.target.value)
                                }
                            />
                        </label>
                    </>
                )}

                <div className="flex justify-end gap-2">
                    <button
                        type="button"
                        className="btn btn-ghost"
                        onClick={close}
                    >
                        {t('actions.cancel')}
                    </button>
                    <button
                        type="button"
                        className="btn btn-error"
                        disabled={!confirmed || busy}
                        onClick={destroy}
                    >
                        {t('system.deletion.delete')}
                    </button>
                </div>
            </div>
        </FormModal>
    );
}

/** "Marta, Julio y 4 más", in the reader's language. */
function names({ count, names: shown }, t, locale) {
    const rest = count - shown.length;
    const list =
        rest > 0
            ? [...shown, t('system.deletion.and_more', { count: rest })]
            : shown;

    return new Intl.ListFormat(locale, { type: 'conjunction' }).format(list);
}
