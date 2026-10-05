import Card from '@/Components/Card';
import EmptyState from '@/Components/EmptyState';
import ReleaseNotes from '@/Components/ReleaseNotes';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { releasedUpTo } from '@/lib/releases';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

/** Every release so far and what it brought, newest first. */
export default function Index() {
    const { release } = usePage().props;
    const { t } = useTranslation();
    const notes = useTranslation('whatsnew', { useSuspense: false });

    const releases = notes.ready
        ? releasedUpTo(
              notes.t('releases', { returnObjects: true }),
              release.version,
          )
        : [];

    return (
        // Someone came here to read the notes; a tour waits for the next page.
        <AuthenticatedLayout
            autoTours={false}
            title={t('whatsNew.page_title')}
            subtitle={t('whatsNew.page_intro')}
        >
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto flex max-w-3xl flex-col gap-4 sm:px-6 lg:px-8">
                    {!notes.ready ? (
                        <div className="flex justify-center py-20">
                            <span
                                className="loading loading-spinner loading-lg text-primary"
                                role="status"
                            />
                        </div>
                    ) : releases.length === 0 ? (
                        <EmptyState title={t('whatsNew.empty')} />
                    ) : (
                        <Card className="w-full">
                            <ReleaseNotes releases={releases} />
                        </Card>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
