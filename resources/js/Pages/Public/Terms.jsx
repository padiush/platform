import LegalDocument from '@/Components/LegalDocument';
import PublicLayout from '@/Layouts/PublicLayout';
import { useTranslation } from 'react-i18next';

export default function Terms() {
    const { t } = useTranslation();

    return (
        <PublicLayout title={t('public.terms')}>
            <LegalDocument document="terms" />
        </PublicLayout>
    );
}
