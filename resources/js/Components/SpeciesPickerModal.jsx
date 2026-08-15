import Input from '@/Components/Input';
import { faMagnifyingGlass } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import axios from 'axios';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

/**
 * Dialog for choosing a catalog species to link. Reuses the catalog's
 * fuzzy/accent search via the data.link.species-search endpoint. Opened
 * imperatively by the parent through `modalRef.current.showModal()`; calls
 * `onPick(species)` (or `onPick(null)` to unlink) and closes itself.
 */
export default function SpeciesPickerModal({
    modalRef,
    project,
    currentSpeciesId = null,
    onPick,
}) {
    const { t } = useTranslation();
    const [q, setQ] = useState('');
    const [results, setResults] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(false);

    useEffect(() => {
        const controller = new AbortController();
        const timeout = setTimeout(async () => {
            setLoading(true);
            setError(false);
            try {
                const { data } = await axios.get(
                    route('data.link.species-search', {
                        project: project.id,
                        q,
                    }),
                    { signal: controller.signal },
                );
                setResults(data.data ?? []);
            } catch (err) {
                if (!axios.isCancel(err)) {
                    console.error('Error searching species:', err);
                    setError(true);
                }
            } finally {
                setLoading(false);
            }
        }, 300);

        return () => {
            clearTimeout(timeout);
            controller.abort();
        };
    }, [q, project.id]);

    const pick = (species) => {
        onPick(species);
        modalRef.current?.close();
    };

    return (
        <dialog className="modal" ref={modalRef}>
            <div className="modal-box">
                <h3 className="text-lg font-bold">{t('data.picker.title')}</h3>

                <div className="mt-3">
                    <Input
                        name="species-search"
                        value={q}
                        placeholder={t('data.picker.search_placeholder')}
                        aria-label={t('data.picker.search_placeholder')}
                        leftAddon={
                            <span className="bg-base-200 border-base-300 join-item flex items-center border px-3">
                                <FontAwesomeIcon
                                    icon={faMagnifyingGlass}
                                    className="text-base-content/50"
                                />
                            </span>
                        }
                        onChange={(e) => setQ(e.target.value)}
                    />
                </div>

                {error ? (
                    <p className="text-error mt-3 text-sm" role="alert">
                        {t('data.picker.error')}
                    </p>
                ) : (
                    <ul className="border-base-300 divide-base-300 rounded-box mt-3 max-h-72 divide-y overflow-y-auto border">
                        {results.map((sp) => (
                            <li key={sp.id}>
                                <button
                                    type="button"
                                    onClick={() => pick(sp)}
                                    className={`hover:bg-base-200 flex w-full flex-col items-start px-4 py-2 text-left transition ${
                                        sp.id === currentSpeciesId
                                            ? 'bg-base-200'
                                            : ''
                                    }`}
                                >
                                    <span className="truncate">
                                        <span className="italic">
                                            {sp.genus} {sp.name}
                                        </span>{' '}
                                        <span className="text-base-content/60">
                                            {sp.authority}
                                        </span>
                                    </span>
                                    {sp.family && (
                                        <span className="text-base-content/50 text-xs">
                                            {sp.family}
                                        </span>
                                    )}
                                </button>
                            </li>
                        ))}
                        {!loading && results.length === 0 && (
                            <li className="text-base-content/60 px-4 py-3 text-sm">
                                {t('data.picker.no_matches')}
                            </li>
                        )}
                    </ul>
                )}

                {currentSpeciesId && (
                    <button
                        type="button"
                        className="btn btn-ghost btn-sm text-error mt-3"
                        onClick={() => pick(null)}
                    >
                        {t('data.picker.unlink')}
                    </button>
                )}
            </div>
            <form method="dialog" className="modal-backdrop">
                <button>{t('actions.close')}</button>
            </form>
        </dialog>
    );
}
