import LegalDocument from '@/Components/LegalDocument';
import PublicLayout from '@/Layouts/PublicLayout';
import { useTranslation } from 'react-i18next';

export default function Privacy() {
    const { t } = useTranslation();

    return (
        <PublicLayout title={t('public.privacy')}>
            <LegalDocument document="privacy" />
        </PublicLayout>
    );
}
