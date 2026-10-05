import { useTranslation } from 'react-i18next';

const STYLES = {
    ok: 'badge-success badge-soft',
    warn: 'badge-warning badge-soft',
    // The soft neutral badge all but disappears on the dark theme.
    unknown: 'bg-base-300 text-base-content border-base-300',
};

/** How an upkeep check stands, in a word: Bien, Revisar, Sin datos. */
export default function StatusChip({ status }) {
    const { t } = useTranslation();

    return (
        <span
            className={`badge badge-sm w-20 shrink-0 justify-center font-semibold whitespace-nowrap ${
                STYLES[status] ?? STYLES.unknown
            }`}
        >
            {t(`system.checks.${status in STYLES ? status : 'unknown'}`)}
        </span>
    );
}
