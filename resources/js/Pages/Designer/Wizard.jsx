import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { requestHeaders } from '@/utils/requestHeaders';
import { faArrowLeft, faEye, faPlus } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import ItemAdder from './Partials/ItemAdder';
import ItemCard from './Partials/ItemCard';
import SectionSelector from './Partials/SectionSelector';

export default function Wizard({ project, form }) {
    const { t } = useTranslation();
    const [sections, setSections] = useState([]);
    const [selectedSectionId, setSelectedSectionId] = useState(null);
    const [sectionItems, setSectionItems] = useState([]);

    const fetchSections = async () => {
        const response = await fetch(
            route('designer.form.sections', {
                project: project.id,
                form: form.id,
            }),
        );
        const data = await response.json();
        setSections(data);
    };

    const fetchSectionItems = async () => {
        if (!selectedSectionId) {
            return [];
        }

        const response = await fetch(
            route('designer.form.section.items', {
                project: project.id,
                form: form.id,
                section: selectedSectionId,
            }),
        );
        const data = await response.json();
        return data;
    };

    const createSection = async () => {
        try {
            const res = await axios.post(
                route('designer.form.sections.create', {
                    project: project.id,
                    form: form.id,
                }),
                {
                    name: t('designer.new_section'),
                },
                {
                    headers: requestHeaders(),
                },
            );

            if (res.status === 200) {
                await fetchSections();
            }
        } catch (error) {
            console.error('Section creation failed:', error);
        }
    };

    useEffect(() => {
        fetchSections();
    }, []);

    useEffect(() => {
        fetchSectionItems().then(setSectionItems);
    }, [selectedSectionId]);

    return (
        <AuthenticatedLayout
            title={t('designer.title')}
            subtitle={form.name}
            action={
                <Link href={route('designer.index')} className="btn btn-ghost">
                    <FontAwesomeIcon icon={faArrowLeft} />
                </Link>
            }
            actionRight={
                <Link
                    href={route('designer.form.preview', {
                        project: project.id,
                        form: form.id,
                    })}
                    className="btn btn-primary"
                >
                    <FontAwesomeIcon icon={faEye} />
                    {t('designer.preview')}
                </Link>
            }
        >
            <div className="drawer lg:drawer-open">
                <input
                    id="my-drawer-2"
                    type="checkbox"
                    className="drawer-toggle"
                />
                <div className="drawer-content flex flex-col p-4 lg:ml-80">
                    <label
                        htmlFor="my-drawer-2"
                        className="btn btn-primary drawer-button mb-4 lg:hidden"
                    >
                        {t('designer.view_sections')}
                    </label>

                    <div className="mb-16 flex flex-col gap-4">
                        {selectedSectionId ? (
                            <div></div>
                        ) : (
                            <div className="text-center">
                                {sections.length > 0
                                    ? t('designer.select_a_section')
                                    : t('designer.no_sections_yet')}
                            </div>
                        )}

                        {selectedSectionId && sectionItems.length > 0 && (
                            <div className="grid w-full grid-cols-1 gap-8">
                                {sectionItems
                                    .sort((a, b) => a.order - b.order)
                                    .map((item) => (
                                        <ItemCard
                                            key={item.id}
                                            project={project}
                                            form={form}
                                            section={selectedSectionId}
                                            item={item}
                                            onItemUpdated={() =>
                                                fetchSectionItems().then(
                                                    setSectionItems,
                                                )
                                            }
                                            onItemDeleted={() =>
                                                fetchSectionItems().then(
                                                    setSectionItems,
                                                )
                                            }
                                            onItemMoved={() =>
                                                fetchSectionItems().then(
                                                    setSectionItems,
                                                )
                                            }
                                        />
                                    ))}
                            </div>
                        )}

                        {selectedSectionId && (
                            <ItemAdder
                                project={project}
                                form={form}
                                section={selectedSectionId}
                                onItemAdded={() =>
                                    fetchSectionItems().then(setSectionItems)
                                }
                            />
                        )}
                    </div>
                </div>

                <div className="drawer-side z-50">
                    <label
                        htmlFor="my-drawer-2"
                        aria-label="close sidebar"
                        className="drawer-overlay"
                    ></label>
                    <ul className="menu bg-base-200 text-base-content min-h-full w-80 p-4 shadow-2xl lg:fixed lg:top-[10.5rem] lg:h-[calc(100vh-8.5rem)]">
                        <li className="mb-4">
                            <div
                                className="btn btn-primary"
                                onClick={createSection}
                            >
                                <FontAwesomeIcon icon={faPlus} />
                                {t('designer.new_section')}
                            </div>
                        </li>

                        {sections.length > 0 ? (
                            sections.map((section) => (
                                <SectionSelector
                                    key={section.id}
                                    project={project}
                                    form={form}
                                    section={section}
                                    selectedSectionId={selectedSectionId}
                                    onClick={setSelectedSectionId}
                                    onModified={fetchSections}
                                />
                            ))
                        ) : (
                            <li>{t('designer.no_sections_yet')}</li>
                        )}
                    </ul>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
