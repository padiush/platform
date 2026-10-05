import Alert from '@/Components/Alert';
import Card from '@/Components/Card';
import ConfirmModal from '@/Components/ConfirmModal';
import EmptyState from '@/Components/EmptyState';
import FormModal from '@/Components/FormModal';
import Input from '@/Components/Input';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatLongDate, formatRelativeTime } from '@/utils/datetime';
import {
    faDesktop,
    faMobileScreen,
    faRightFromBracket,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

/** A phone or tablet's system, else a computer's. */
const MOBILE = ['iOS', 'Android'];

/** One browser the user is signed in on. */
function SessionRow({ session, onSignOut }) {
    const { t, i18n } = useTranslation();

    const name =
        [session.browser, session.platform].filter(Boolean).join(' · ') ||
        t('account.sessions.unknown_browser');

    return (
        <li className="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex min-w-0 items-center gap-3">
                <FontAwesomeIcon
                    icon={
                        MOBILE.includes(session.platform)
                            ? faMobileScreen
                            : faDesktop
                    }
                    className="text-base-content/60 w-5 shrink-0"
                    fixedWidth
                />
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2 font-medium">
                        <span className="break-words">{name}</span>
                        {session.current && (
                            <span className="badge badge-success badge-soft badge-sm">
                                {t('account.sessions.this_browser')}
                            </span>
                        )}
                    </div>
                    <div className="text-base-content/60 text-xs break-words">
                        {[
                            session.ip_address,
                            session.current
                                ? t('account.sessions.active_now')
                                : t('account.sessions.last_active', {
                                      when: formatRelativeTime(
                                          session.last_active,
                                          i18n.language,
                                      ),
                                  }),
                        ]
                            .filter(Boolean)
                            .join(' · ')}
                    </div>
                </div>
            </div>
            {!session.current && (
                <button
                    type="button"
                    className="btn btn-ghost btn-sm self-start sm:self-auto"
                    onClick={() => onSignOut(session)}
                >
                    <FontAwesomeIcon icon={faRightFromBracket} />
                    {t('account.sessions.sign_out')}
                </button>
            )}
        </li>
    );
}

/** Signing every other browser out, behind the password. */
function SignOutOthersForm({ onClose }) {
    const { t } = useTranslation();
    const {
        data,
        setData,
        delete: destroy,
        processing,
        errors,
        reset,
    } = useForm({ password: '' });

    const submit = (event) => {
        event.preventDefault();
        destroy(route('account.sessions.destroy-others'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <form onSubmit={submit}>
            <p className="text-base-content/70 mb-4 text-sm">
                {t('account.sessions.others_hint')}
            </p>
            <Input
                name="password"
                type="password"
                label={t('account.sessions.password')}
                value={data.password}
                onChange={(event) => setData('password', event.target.value)}
                error={errors.password}
                autoComplete="current-password"
                required
            />
            <div className="mt-4 flex justify-end gap-2">
                <button
                    type="button"
                    className="btn btn-ghost"
                    onClick={onClose}
                >
                    {t('actions.cancel')}
                </button>
                <button
                    type="submit"
                    className="btn btn-primary"
                    disabled={processing}
                >
                    {t('account.sessions.sign_out_others')}
                </button>
            </div>
        </form>
    );
}

/**
 * Mi cuenta: where the user is signed in — browsers on the web, and devices
 * running the field app — with a way to sign any of them out.
 */
export default function Show({ account, sessions = null, devices = [] }) {
    const { t, i18n } = useTranslation();
    const [signingOut, setSigningOut] = useState(null);
    const [signingOutOthers, setSigningOutOthers] = useState(false);
    const [revoking, setRevoking] = useState(null);

    const others = (sessions ?? []).filter((session) => !session.current);

    const signOut = () =>
        router.delete(
            route('account.sessions.destroy', { session: signingOut.key }),
            { preserveScroll: true, onFinish: () => setSigningOut(null) },
        );

    const revoke = () =>
        router.delete(
            route('account.devices.destroy', { device: revoking.id }),
            {
                preserveScroll: true,
                onFinish: () => setRevoking(null),
            },
        );

    return (
        <AuthenticatedLayout title={t('account.title')}>
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto flex max-w-3xl flex-col gap-4 sm:px-6 lg:px-8">
                    <Card>
                        <div className="min-w-0">
                            <div className="text-lg font-semibold break-words">
                                {account.name}
                            </div>
                            <div className="text-base-content/70 text-sm break-all">
                                {account.email}
                            </div>
                        </div>
                    </Card>

                    <Card
                        title={t('account.sessions.title')}
                        description={t('account.sessions.description')}
                        actions={
                            others.length > 0 && (
                                <button
                                    type="button"
                                    className="btn btn-outline btn-sm"
                                    onClick={() => setSigningOutOthers(true)}
                                >
                                    {t('account.sessions.sign_out_others')}
                                </button>
                            )
                        }
                    >
                        {sessions === null ? (
                            <Alert
                                type="info"
                                message={t('account.sessions.unavailable')}
                            />
                        ) : (
                            <ul className="divide-base-300 divide-y">
                                {sessions.map((session) => (
                                    <SessionRow
                                        key={session.key}
                                        session={session}
                                        onSignOut={setSigningOut}
                                    />
                                ))}
                            </ul>
                        )}
                    </Card>

                    <Card
                        title={t('account.devices.title')}
                        description={t('account.devices.description')}
                    >
                        {devices.length === 0 ? (
                            <EmptyState
                                title={t('account.devices.none_title')}
                                hint={t('account.devices.none_hint')}
                            />
                        ) : (
                            <ul className="divide-base-300 divide-y">
                                {devices.map((device) => (
                                    <li
                                        key={device.id}
                                        className="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div className="flex min-w-0 items-center gap-3">
                                            <FontAwesomeIcon
                                                icon={faMobileScreen}
                                                className="text-base-content/60 w-5 shrink-0"
                                                fixedWidth
                                            />
                                            <div className="min-w-0">
                                                <div className="font-medium break-words">
                                                    {device.name}
                                                </div>
                                                <div className="text-base-content/60 text-xs">
                                                    {[
                                                        t(
                                                            'account.devices.signed_in',
                                                            {
                                                                when: formatLongDate(
                                                                    device.created_at,
                                                                    i18n.language,
                                                                ),
                                                            },
                                                        ),
                                                        device.last_used_at
                                                            ? t(
                                                                  'account.devices.last_used',
                                                                  {
                                                                      when: formatRelativeTime(
                                                                          device.last_used_at,
                                                                          i18n.language,
                                                                      ),
                                                                  },
                                                              )
                                                            : t(
                                                                  'account.devices.never_used',
                                                              ),
                                                    ].join(' · ')}
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            className="btn btn-ghost btn-sm text-error self-start sm:self-auto"
                                            onClick={() => setRevoking(device)}
                                        >
                                            {t('account.devices.revoke')}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                </div>
            </div>

            <ConfirmModal
                open={signingOut !== null}
                title={t('account.sessions.confirm_title')}
                message={t('account.sessions.confirm_message')}
                confirmLabel={t('account.sessions.sign_out')}
                onConfirm={signOut}
                onClose={() => setSigningOut(null)}
            />

            <ConfirmModal
                open={revoking !== null}
                title={t('account.devices.confirm_title')}
                message={t('account.devices.confirm_message', {
                    name: revoking?.name ?? '',
                })}
                confirmLabel={t('account.devices.revoke')}
                onConfirm={revoke}
                onClose={() => setRevoking(null)}
            />

            <FormModal
                open={signingOutOthers}
                onClose={() => setSigningOutOthers(false)}
                title={t('account.sessions.sign_out_others')}
            >
                {signingOutOthers && (
                    <SignOutOthersForm
                        onClose={() => setSigningOutOthers(false)}
                    />
                )}
            </FormModal>
        </AuthenticatedLayout>
    );
}
