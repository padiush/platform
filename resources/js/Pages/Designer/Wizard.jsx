import Input from '@/Components/Input';
import { useDesignerDraft } from '@/Hooks/useDesignerDraft';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { createItem, createSection, moveItem } from '@/lib/designerDraft';
import { faArrowLeft, faEye, faPlus } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import AddFieldBar from './Partials/AddFieldBar';
import FieldCard from './Partials/FieldCard';
import SectionList from './Partials/SectionList';

export default function Wizard({ project, form, structure, instancesCount }) {
    const { t } = useTranslation();

    const {
        blockedMoves,
        detachPrompt,
        isDirty,
        isSaving,
        issues,
        save,
        saveError,
        saved,
        sections,
        setDetachPrompt,
        setSections,
    } = useDesignerDraft({
        initialStructure: structure,
        saveUrl: route('designer.form.structure.update', {
            project: project.id,
            form: form.id,
        }),
        guardMessage: t('designer.unsaved_guard'),
    });

    const [selectedIndex, setSelectedIndex] = useState(
        structure.length > 0 ? 0 : null,
    );
    const [expandedFieldId, setExpandedFieldId] = useState(null);
    const [drawerOpen, setDrawerOpen] = useState(false);
    const pendingFocus = useRef(null); // {selector} to focus after render

    const selectedSection =
        selectedIndex !== null ? (sections[selectedIndex] ?? null) : null;

    useEffect(() => {
        if (!pendingFocus.current) {
            return;
        }

        const target = document.querySelector(pendingFocus.current);
        pendingFocus.current = null;

        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target.focus({ preventScroll: true });
        }
    }, [sections, selectedIndex]);

    const patchSection = (index, changes) => {
        setSections(
            sections.map((section, i) =>
                i === index ? { ...section, ...changes } : section,
            ),
        );
    };

    const addSection = () => {
        setSections([...sections, createSection()]);
        setSelectedIndex(sections.length);
        setDrawerOpen(false);
        pendingFocus.current = '[data-section-name] input';
    };

    const moveSection = (from, to) => {
        setSections(moveItem(sections, from, to));
        if (selectedIndex === from) {
            setSelectedIndex(to);
        } else if (from < selectedIndex && selectedIndex <= to) {
            setSelectedIndex(selectedIndex - 1);
        } else if (to <= selectedIndex && selectedIndex < from) {
            setSelectedIndex(selectedIndex + 1);
        }
    };

    const removeSection = (index) => {
        const next = sections.filter((_, i) => i !== index);
        setSections(next);
        if (next.length === 0) {
            setSelectedIndex(null);
        } else if (selectedIndex !== null) {
            setSelectedIndex(
                Math.min(
                    selectedIndex > index ? selectedIndex - 1 : selectedIndex,
                    next.length - 1,
                ),
            );
        }
    };

    const selectSection = (index) => {
        setSelectedIndex(index);
        setExpandedFieldId(null);
        setDrawerOpen(false);
    };

    const patchItems = (items) => patchSection(selectedIndex, { items });

    const addField = (type) => {
        const item = createItem(type);
        patchItems([...selectedSection.items, item]);
        setExpandedFieldId(item.clientId);
        pendingFocus.current = `[data-client-id="${item.clientId}"] [data-field-label]`;
    };

    const moveFieldToSection = (itemIndex, targetSectionIndex) => {
        const item = selectedSection.items[itemIndex];
        setSections(
            sections.map((section, i) => {
                if (i === selectedIndex) {
                    return {
                        ...section,
                        items: section.items.filter((_, j) => j !== itemIndex),
                    };
                }
                if (i === targetSectionIndex) {
                    return { ...section, items: [...section.items, item] };
                }
                return section;
            }),
        );
    };

    const fieldsCount = sections.reduce(
        (total, section) => total + section.items.length,
        0,
    );
    const showSaveBar = isDirty || isSaving || issues.length > 0 || saved;

    return (
        <AuthenticatedLayout
            title={t('designer.title')}
            subtitle={form.name}
            action={
                <Link href={route('designer.index')} className="btn btn-ghost">
                    <FontAwesomeIcon
                        icon={faArrowLeft}
                        aria-label={t('pagination.previous')}
                    />
                </Link>
            }
            actionRight={
                <div className="flex flex-wrap items-center justify-end gap-2">
                    {instancesCount > 0 && (
                        <span className="badge badge-warning badge-outline whitespace-nowrap">
                            {t('designer.interviews_recorded', {
                                count: instancesCount,
                            })}
                        </span>
                    )}
                    <span
                        className={`badge whitespace-nowrap ${
                            isDirty ? 'badge-warning' : 'badge-success'
                        }`}
                    >
                        {isDirty
                            ? t('designer.unsaved_badge')
                            : t('designer.saved_badge')}
                    </span>
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
                </div>
            }
        >
            <div className="drawer lg:drawer-open">
                <input
                    id="designer-drawer"
                    type="checkbox"
                    className="drawer-toggle"
                    checked={drawerOpen}
                    onChange={(e) => setDrawerOpen(e.target.checked)}
                />
                <div className="drawer-content flex flex-col p-4 lg:ml-80">
                    <label
                        htmlFor="designer-drawer"
                        className="btn btn-primary drawer-button mb-4 lg:hidden"
                    >
                        {t('designer.view_sections')}
                    </label>

                    <div className="mx-auto flex w-full max-w-4xl flex-col gap-4 pb-24">
                        {saveError && (
                            <div className="alert alert-error">
                                <span>{t(saveError)}</span>
                            </div>
                        )}
                        {blockedMoves && (
                            <div className="alert alert-error">
                                <div>
                                    <p>{t('designer.move_has_answers')}</p>
                                    <ul className="list-inside list-disc text-sm">
                                        {blockedMoves.map((item) => (
                                            <li key={item.id}>
                                                {item.label} —{' '}
                                                {t('designer.summary.answers', {
                                                    count: item.answers_count,
                                                })}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            </div>
                        )}

                        {sections.length === 0 && (
                            <div className="border-base-300 rounded-box border border-dashed p-10 text-center">
                                <h3 className="text-lg font-bold">
                                    {t('designer.empty.no_sections_title')}
                                </h3>
                                <p className="text-base-content/60 mt-2">
                                    {t('designer.empty.no_sections_hint')}
                                </p>
                                <button
                                    type="button"
                                    className="btn btn-primary mt-4"
                                    onClick={addSection}
                                >
                                    <FontAwesomeIcon icon={faPlus} />
                                    {t('designer.new_section')}
                                </button>
                            </div>
                        )}

                        {sections.length > 0 && selectedSection === null && (
                            <div className="border-base-300 rounded-box border border-dashed p-10 text-center">
                                <p className="text-base-content/60">
                                    {t('designer.empty.pick_section')}
                                </p>
                            </div>
                        )}

                        {selectedSection && (
                            <>
                                <div className="card bg-base-200 shadow-sm">
                                    <div className="card-body gap-3">
                                        <div
                                            className="flex flex-wrap items-end justify-between gap-3"
                                            data-section-name
                                        >
                                            <Input
                                                className="max-w-md grow"
                                                label={`${t('designer.section')} ${selectedIndex + 1}`}
                                                value={selectedSection.name}
                                                required
                                                onChange={(e) =>
                                                    patchSection(
                                                        selectedIndex,
                                                        {
                                                            name: e.target
                                                                .value,
                                                        },
                                                    )
                                                }
                                            />
                                            <label className="flex items-center gap-2 pb-1">
                                                <input
                                                    type="checkbox"
                                                    className="checkbox checkbox-sm"
                                                    checked={
                                                        selectedSection.repeatable
                                                    }
                                                    onChange={(e) =>
                                                        patchSection(
                                                            selectedIndex,
                                                            {
                                                                repeatable:
                                                                    e.target
                                                                        .checked,
                                                            },
                                                        )
                                                    }
                                                />
                                                <span className="text-sm">
                                                    {t('designer.repeatable')}
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                {selectedSection.items.length === 0 && (
                                    <div className="border-base-300 rounded-box border border-dashed p-6 text-center">
                                        <p className="text-base-content/60 text-sm">
                                            {t('designer.empty.no_fields')}
                                        </p>
                                    </div>
                                )}

                                {selectedSection.items.map(
                                    (item, itemIndex) => (
                                        <div
                                            key={item.clientId}
                                            data-client-id={item.clientId}
                                        >
                                            <FieldCard
                                                item={item}
                                                index={itemIndex}
                                                count={
                                                    selectedSection.items.length
                                                }
                                                expanded={
                                                    expandedFieldId ===
                                                    item.clientId
                                                }
                                                sections={sections}
                                                sectionIndex={selectedIndex}
                                                onToggle={() =>
                                                    setExpandedFieldId(
                                                        expandedFieldId ===
                                                            item.clientId
                                                            ? null
                                                            : item.clientId,
                                                    )
                                                }
                                                onChange={(next) =>
                                                    patchItems(
                                                        selectedSection.items.map(
                                                            (current, i) =>
                                                                i === itemIndex
                                                                    ? next
                                                                    : current,
                                                        ),
                                                    )
                                                }
                                                onMove={(to) =>
                                                    patchItems(
                                                        moveItem(
                                                            selectedSection.items,
                                                            itemIndex,
                                                            to,
                                                        ),
                                                    )
                                                }
                                                onMoveToSection={(target) =>
                                                    moveFieldToSection(
                                                        itemIndex,
                                                        target,
                                                    )
                                                }
                                                onRemove={() =>
                                                    patchItems(
                                                        selectedSection.items.filter(
                                                            (_, i) =>
                                                                i !== itemIndex,
                                                        ),
                                                    )
                                                }
                                            />
                                        </div>
                                    ),
                                )}

                                <AddFieldBar onAdd={addField} />
                            </>
                        )}
                    </div>

                    {showSaveBar && (
                        <div className="border-base-300 bg-base-100/95 rounded-box sticky bottom-4 z-20 mx-auto w-full max-w-4xl border px-4 py-3 shadow-xl backdrop-blur">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p className="text-sm font-semibold">
                                        {issues.length > 0
                                            ? t('designer.issues_before_save', {
                                                  count: issues.length,
                                              })
                                            : isDirty
                                              ? t('designer.unsaved_badge')
                                              : t('designer.structure_saved')}
                                    </p>
                                    {issues.length > 0 ? (
                                        <p className="text-base-content/60 text-xs">
                                            {t(issues[0].key)}
                                        </p>
                                    ) : (
                                        <p className="text-base-content/60 text-xs">
                                            {t('designer.draft_counts', {
                                                sections: sections.length,
                                                fields: fieldsCount,
                                            })}
                                        </p>
                                    )}
                                </div>
                                <button
                                    type="button"
                                    className="btn btn-primary"
                                    disabled={
                                        isSaving ||
                                        issues.length > 0 ||
                                        !isDirty
                                    }
                                    onClick={() => save()}
                                >
                                    {isSaving && (
                                        <span className="loading loading-spinner loading-sm" />
                                    )}
                                    {isSaving
                                        ? t('designer.saving')
                                        : t('designer.save')}
                                </button>
                            </div>
                        </div>
                    )}
                </div>

                <div className="drawer-side z-50">
                    <label
                        htmlFor="designer-drawer"
                        aria-label={t('actions.close')}
                        className="drawer-overlay"
                    ></label>
                    <div className="bg-base-200 text-base-content min-h-full w-80 p-4 shadow-2xl lg:fixed lg:top-[10.5rem] lg:h-[calc(100vh-8.5rem)] lg:overflow-y-auto">
                        <SectionList
                            sections={sections}
                            selectedIndex={selectedIndex}
                            onSelect={selectSection}
                            onAdd={addSection}
                            onMove={moveSection}
                            onRemove={removeSection}
                        />
                    </div>
                </div>
            </div>

            {detachPrompt && (
                <div className="modal modal-open" role="dialog">
                    <div className="modal-box">
                        <h3 className="text-lg font-bold">
                            {t('designer.detach.title')}
                        </h3>
                        <p className="py-2">{t('designer.detach.body')}</p>
                        <ul className="list-inside list-disc text-sm">
                            {detachPrompt.detaching.map((item) => (
                                <li key={item.id}>
                                    {item.label} —{' '}
                                    {t('designer.summary.answers', {
                                        count: item.answers_count,
                                    })}
                                </li>
                            ))}
                        </ul>
                        <div className="modal-action">
                            <button
                                type="button"
                                className="btn btn-ghost"
                                onClick={() => setDetachPrompt(null)}
                            >
                                {t('actions.cancel')}
                            </button>
                            <button
                                type="button"
                                className="btn btn-error"
                                disabled={isSaving}
                                onClick={() => save({ confirmDetach: true })}
                            >
                                {t('designer.detach.confirm')}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
