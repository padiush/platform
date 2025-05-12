import Card from '@/Components/Card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Dashboard() {
    const { t } = useTranslation();
    const { auth } = usePage().props;

    return (
        <AuthenticatedLayout title={t('dashboard.title')}>
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <Card>
                        {t('dashboard.welcome_message', {
                            appName: t('app.name'),
                            name: auth.user.name,
                        })}
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
