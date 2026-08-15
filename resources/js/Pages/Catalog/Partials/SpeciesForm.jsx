import Input from '@/Components/Input';
import { faMagnifyingGlass } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useForm } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

/**
 * Catalog species register form, rendered inside a FormModal on the catalog
 * hub. A researcher can optionally search WFO and pick a name to prefill the
 * taxonomy (correct spelling and authorship, recorded as provenance) instead of
 * hand-typing it; the fields stay editable and manual entry still works.
 */
export default function SpeciesForm({ project, onClose }) {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors } = useForm({
        family: '',
        genus: '',
        name: '',
        authority: '',
        wfo_id: null,
    });

    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const [searching, setSearching] = useState(false);
    const [searchError, setSearchError] = useState(null);
    const [sourceName, setSourceName] = useState(null);
    const [photoInfo, setPhotoInfo] = useState(null);

    const showResults = query.trim().length >= 2;
    const scientificName = `${data.genus} ${data.name}`.trim();
    const showPhoto =
        data.genus.trim() &&
        data.name.trim() &&
        photoInfo?.found &&
        photoInfo.for === scientificName;

    useEffect(() => {
        const term = query.trim();
        if (term.length < 2) return undefined;

        let active = true;
        const timer = setTimeout(() => {
            setSearching(true);
            setSearchError(null);
            axios
                .get(
                    route('catalogs.species.wfo-search', {
                        project: project.id,
                    }),
                    {
                        params: { q: term },
                    },
                )
                .then(
                    (response) =>
                        active && setResults(response.data.results ?? []),
                )
                .catch(
                    () => active && setSearchError(t('catalogs.source.error')),
                )
                .finally(() => active && setSearching(false));
        }, 350);

        return () => {
            active = false;
            clearTimeout(timer);
        };
    }, [query, project.id, t]);

    // A reference photo (from iNaturalist) to visually confirm the species being
    // registered. Fetched on the current genus + epithet, whether prefilled or
    // typed. The image itself is proxied and never stored.
    useEffect(() => {
        const sci = `${data.genus} ${data.name}`.trim();
        if (!data.genus.trim() || !data.name.trim()) return undefined;

        let active = true;
        const timer = setTimeout(() => {
            axios
                .get(
                    route('catalogs.species.inaturalist', {
                        project: project.id,
                    }),
                    {
                        params: { name: sci },
                    },
                )
                .then(
                    (response) =>
                        active && setPhotoInfo({ ...response.data, for: sci }),
                )
                .catch(() => active && setPhotoInfo(null));
        }, 500);

        return () => {
            active = false;
            clearTimeout(timer);
        };
    }, [data.genus, data.name, project.id]);

    const selectCandidate = (candidate) => {
        axios
            .post(
                route('catalogs.species.wfo-resolve', { project: project.id }),
                {
                    wfo_id: candidate.wfo_id,
                },
            )
            .then((response) => {
                setData('family', response.data.family ?? '');
                setData('genus', response.data.genus ?? '');
                setData('name', response.data.name ?? '');
                setData('authority', response.data.authority ?? '');
                setData('wfo_id', response.data.wfo_id);
                setSourceName(response.data.name_plain);
                setQuery('');
                setResults([]);
            })
            .catch(() => setSearchError(t('catalogs.source.resolve_error')));
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        post(route('catalogs.species.register', { project: project.id }), {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    const field = (name, label, required = false) => (
        <Input
            name={name}
            label={label}
            value={data[name]}
            onChange={(e) => {
                setData(name, e.target.value);
                // A manual edit detaches the entry from its WFO provenance.
                if ((name === 'genus' || name === 'name') && data.wfo_id) {
                    setData('wfo_id', null);
                    setSourceName(null);
                }
            }}
            error={errors[name]}
            required={required}
        />
    );

    return (
        <form onSubmit={handleSubmit}>
            <div className="mb-4">
                <Input
                    name="source-search"
                    type="search"
                    label={t('catalogs.source.label')}
                    value={query}
                    placeholder={t('catalogs.source.placeholder')}
                    onChange={(e) => setQuery(e.target.value)}
                    leftAddon={
                        <span className="bg-base-200 border-base-300 join-item flex items-center border px-3">
                            <FontAwesomeIcon
                                icon={faMagnifyingGlass}
                                className="text-base-content/50"
                            />
                        </span>
                    }
                />

                {sourceName && (
                    <p className="text-success mt-1 text-xs">
                        {t('catalogs.source.prefilled', { name: sourceName })}
                    </p>
                )}

                {showResults && (
                    <div className="border-base-300 rounded-box mt-2 max-h-56 overflow-y-auto border">
                        {searching ? (
                            <p className="text-base-content/60 p-3 text-sm">
                                {t('catalogs.source.searching')}
                            </p>
                        ) : searchError ? (
                            <p className="text-error p-3 text-sm">
                                {searchError}
                            </p>
                        ) : results.length === 0 ? (
                            <p className="text-base-content/60 p-3 text-sm">
                                {t('catalogs.source.no_results')}
                            </p>
                        ) : (
                            <ul className="divide-base-300 divide-y">
                                {results.map((candidate, index) => (
                                    <li key={candidate.wfo_id ?? index}>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                selectCandidate(candidate)
                                            }
                                            className="hover:bg-base-200 w-full px-3 py-2 text-left transition"
                                        >
                                            <span
                                                dangerouslySetInnerHTML={{
                                                    __html: candidate.full_name_html,
                                                }}
                                            />
                                            <span className="mt-0.5 block text-xs">
                                                {candidate.is_accepted ? (
                                                    <span className="text-success">
                                                        {t(
                                                            'catalogs.source.accepted',
                                                        )}
                                                    </span>
                                                ) : candidate.accepted_name ? (
                                                    <span className="text-base-content/60">
                                                        {t(
                                                            'catalogs.source.synonym',
                                                        )}
                                                    </span>
                                                ) : null}
                                            </span>
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                )}
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                {field('family', t('catalogs.family'))}
                {field('genus', t('catalogs.genus'), true)}
                {field('name', t('catalogs.species'), true)}
                {field('authority', t('catalogs.authority'))}
            </div>

            {showPhoto && (
                <figure className="mt-4 max-w-xs">
                    <p className="mb-2 text-sm font-medium">
                        {t('catalogs.photo.title')}
                    </p>
                    <img
                        src={route('catalogs.species.inaturalist-photo', {
                            project: project.id,
                            name: scientificName,
                        })}
                        alt={scientificName}
                        loading="lazy"
                        className="border-base-300 rounded-box w-full border"
                        onError={() => setPhotoInfo(null)}
                    />
                    <figcaption className="text-base-content/60 mt-1 text-xs">
                        {photoInfo.attribution}
                        {photoInfo.page_url && (
                            <>
                                {' · '}
                                <a
                                    href={photoInfo.page_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="link"
                                >
                                    {photoInfo.source}
                                </a>
                            </>
                        )}
                    </figcaption>
                </figure>
            )}

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
                    {t('catalogs.register_species')}
                </button>
            </div>
        </form>
    );
}
