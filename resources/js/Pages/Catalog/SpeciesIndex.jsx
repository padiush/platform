import Card from '@/Components/Card';
import EmptyState from '@/Components/EmptyState';
import Input from '@/Components/Input';
import Pagination from '@/Components/Pagination';
import Select from '@/Components/Select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    faArrowLeft,
    faChevronRight,
    faMagnifyingGlass,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function CatalogSpeciesIndex({
    project,
    species,
    filters = {},
    families = [],
    genera = [],
}) {
    const { t } = useTranslation();

    const [q, setQ] = useState(filters.q || '');
    const [family, setFamily] = useState(filters.family || '');
    const [genus, setGenus] = useState(filters.genus || '');
    const [link, setLink] = useState(filters.link || 'all');
    const [sort, setSort] = useState(filters.sort || 'family');

    const genusOptions = useMemo(() => {
        const scoped = family
            ? genera.filter((g) => g.family === family)
            : genera;

        return [...new Set(scoped.map((g) => g.genus))].sort((a, b) =>
            a.localeCompare(b),
        );
    }, [family, genera]);

    // Issue one reload for whatever the current controls hold. Values are
    // passed explicitly so a handler can visit before its setState settles.
    const visit = (next) => {
        const params = { project: project.id };

        if (next.q) params.q = next.q;
        if (next.family) params.family = next.family;
        if (next.genus) params.genus = next.genus;
        if (next.link && next.link !== 'all') params.link = next.link;
        if (next.sort && next.sort !== 'family') params.sort = next.sort;

        router.get(
            route('catalogs.show', params),
            {},
            {
                preserveScroll: true,
                replace: true,
                preserveState: true,
                only: ['species', 'filters'],
            },
        );
    };

    // Debounce free-text search; the selects reload immediately on change.
    const [debouncedQ, setDebouncedQ] = useState(q);

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (debouncedQ !== q) {
                setDebouncedQ(q);
                visit({ q, family, genus, link, sort });
            }
        }, 400);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [q, debouncedQ, family, genus, link, sort]);

    const onFamily = (value) => {
        setFamily(value);
        setGenus('');
        visit({ q, family: value, genus: '', link, sort });
    };

    const onGenus = (value) => {
        setGenus(value);
        visit({ q, family, genus: value, link, sort });
    };

    const onLink = (value) => {
        setLink(value);
        visit({ q, family, genus, link: value, sort });
    };

    const onSort = (value) => {
        setSort(value);
        visit({ q, family, genus, link, sort: value });
    };

    return (
        <AuthenticatedLayout
            title={t('catalogs.ethnobotanical_catalog')}
            breadcrumbs={[
                {
                    label: t('navigation.catalogs'),
                    href: route('catalogs.index'),
                },
                { label: project.name },
            ]}
            subtitle={project.name}
            action={
                <Link
                    href={route('catalogs.index')}
                    className="btn btn-ghost btn-circle"
                    aria-label={t('navigation.back')}
                >
                    <FontAwesomeIcon icon={faArrowLeft} />
                </Link>
            }
        >
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <Card title={t('catalogs.species_list')}>
                        <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div className="sm:col-span-2 lg:col-span-4">
                                <Input
                                    name="q"
                                    value={q}
                                    placeholder={t(
                                        'catalogs.search_placeholder',
                                    )}
                                    aria-label={t(
                                        'catalogs.search_placeholder',
                                    )}
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

                            <Select
                                label={t('catalogs.family')}
                                value={family}
                                onChange={(e) => onFamily(e.target.value)}
                            >
                                <option value="">
                                    {t('catalogs.all_families')}
                                </option>
                                {families.map((f) => (
                                    <option key={f} value={f}>
                                        {f}
                                    </option>
                                ))}
                            </Select>

                            <Select
                                label={t('catalogs.genus')}
                                value={genus}
                                disabled={genusOptions.length === 0}
                                onChange={(e) => onGenus(e.target.value)}
                            >
                                <option value="">
                                    {t('catalogs.all_genera')}
                                </option>
                                {genusOptions.map((g) => (
                                    <option key={g} value={g}>
                                        {g}
                                    </option>
                                ))}
                            </Select>

                            <Select
                                label={t('catalogs.link_status.label')}
                                value={link}
                                onChange={(e) => onLink(e.target.value)}
                            >
                                <option value="all">
                                    {t('catalogs.link_status.all')}
                                </option>
                                <option value="linked">
                                    {t('catalogs.link_status.linked')}
                                </option>
                                <option value="unlinked">
                                    {t('catalogs.link_status.unlinked')}
                                </option>
                            </Select>

                            <Select
                                label={t('catalogs.sort.label')}
                                value={sort}
                                onChange={(e) => onSort(e.target.value)}
                            >
                                <option value="family">
                                    {t('catalogs.sort.family')}
                                </option>
                                <option value="linked">
                                    {t('catalogs.sort.linked')}
                                </option>
                            </Select>
                        </div>

                        {species.data.length > 0 ? (
                            <>
                                <ul className="border-base-300 divide-base-300 rounded-box divide-y border">
                                    {species.data.map((sp) => (
                                        <li key={sp.id}>
                                            <Link
                                                href={route(
                                                    'catalogs.species.show',
                                                    {
                                                        project: project.id,
                                                        species: sp.id,
                                                    },
                                                )}
                                                className="hover:bg-base-200 group flex items-center gap-3 px-4 py-3 transition"
                                            >
                                                <span className="min-w-0 grow">
                                                    <span className="block truncate font-medium">
                                                        <span className="italic">
                                                            {sp.genus} {sp.name}
                                                        </span>{' '}
                                                        <span className="text-base-content/60 font-normal">
                                                            {sp.authority}
                                                        </span>
                                                    </span>
                                                    <span className="text-base-content/60 mt-0.5 block truncate text-xs">
                                                        {[
                                                            sp.family,
                                                            t(
                                                                'designer.summary.answers',
                                                                {
                                                                    count: sp
                                                                        .answers
                                                                        .length,
                                                                },
                                                            ),
                                                        ]
                                                            .filter(Boolean)
                                                            .join(' · ')}
                                                    </span>
                                                </span>
                                                <FontAwesomeIcon
                                                    icon={faChevronRight}
                                                    className="text-base-content/30 group-hover:text-base-content/60 shrink-0 transition"
                                                />
                                            </Link>
                                        </li>
                                    ))}
                                </ul>

                                <Pagination
                                    links={species.links}
                                    className="mt-4"
                                />
                            </>
                        ) : (
                            <EmptyState
                                title={t('catalogs.no_matches_title')}
                                hint={t('catalogs.no_matches_hint')}
                            />
                        )}
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
