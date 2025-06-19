import Card from '@/Components/Card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { router, usePage, Link } from '@inertiajs/react';
import axios from 'axios';
import { useTranslation } from 'react-i18next';

export default function LinkSpecies({ project, answered_sections, species }) {
    const { t } = useTranslation();
    const { csrf_token } = usePage().props;

    const handleSpeciesChange = async (section, catalog_species_id) => {
        try {
            await axios.post(
                route('data.link.handle', { project: project.id }),
                {
                    interview_instance_id: section.interview_instance_id,
                    interview_section_id: section.section.id,
                    repeatable_index: section.repeatable
                        ? section.repeatable_index
                        : null,
                    catalog_species_id,
                },
                {
                    headers: {
                        'X-CSRF-TOKEN': csrf_token,
                    },
                },
            );
            router.reload({ only: ['answered_sections'] });
        } catch (err) {
            console.error('Error linking species:', err);
            alert(t('catalog.fetch_error'));
        }
    };

    const renderAnswer = (item, answers) => {
        const answer = answers.find((a) => a.interview_item_id === item.id);
        return (
            <div className="form-control" key={item.id}>
                <label className="label">
                    <span className="label-text">{item.label}</span>
                </label>
                <input
                    type="text"
                    className="input input-bordered input-sm"
                    value={answer ? answer.answer : ''}
                    disabled
                />
            </div>
        );
    };

    const renderSpeciesSelect = (section, sectionId) => (
        <div className="form-control mt-4">
            <label className="label">
                <span className="label-text">{t('data.link_to')}</span>
            </label>
            <select
                id={sectionId}
                className="select select-bordered w-full"
                defaultValue=""
                onChange={(e) => handleSpeciesChange(section, e.target.value)}
            >
                <option value="" disabled hidden>
                    {t('data.select_species')}
                </option>
                {species.map((specie) => (
                    <option key={specie.id} value={specie.id}>
                        <span className="italic">
                            {`${specie.genus} ${specie.name}`}
                        </span>{' '}
                        {specie.authority}
                    </option>
                ))}
            </select>
        </div>
    );

    return (
        <AuthenticatedLayout
            title={t('data.title')}
            subtitle={project.name}
            action={
                <Link
                    className="btn btn-ghost btn-circle"
                    href={route('data.index')}
                >
                    <i className="fa-solid fa-arrow-left" />
                </Link>
            }
        >
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="grid w-full grid-cols-1 gap-4">
                        {answered_sections.map((section) => {
                            const sectionId = `${section.interview_instance_id}-${section.section.id}${section.repeatable ? `-${section.repeatable_index}` : ''}`;
                            return (
                                <div
                                    key={sectionId}
                                    id={`div-${sectionId}`}
                                    className="animate__animated animate__zoomIn"
                                >
                                    <Card title={section.section.name}>
                                        {section.items.map((item) =>
                                            renderAnswer(item, section.answers),
                                        )}
                                        {renderSpeciesSelect(
                                            section,
                                            sectionId,
                                        )}
                                    </Card>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
