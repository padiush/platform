import DeletionModal from '@/Components/DeletionModal';
import Input from '@/Components/Input';
import {
    faArrowDown,
    faArrowUp,
    faTrash,
} from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function SectionSelector({
    project,
    form,
    section,
    selectedSectionId,
    onClick = () => {},
    onModified = () => {},
}) {
    const { t } = useTranslation();
    const [sectionName, setSectionName] = useState(section.name);
    const [repeatable, setRepeatable] = useState(section.repeatable);
    const timeoutRef = useRef(null);
    const deletionModalRef = useRef();

    const renameSection = async (sectionId, newName) => {
        try {
            const res = await axios.put(
                route('designer.form.sections.rename', {
                    project: project.id,
                    form: form.id,
                    section: sectionId,
                }),
                { name: newName },
            );
            if (res.status === 200) onModified();
        } catch (error) {
            console.error('Rename failed:', error);
        }
    };

    const moveSection = async (direction) => {
        try {
            const res = await axios.put(
                route('designer.form.sections.reorder', {
                    project: project.id,
                    form: form.id,
                    section: section.id,
                }),
                { direction },
            );
            if (res.status === 200) onModified();
        } catch (error) {
            console.error('Reorder failed:', error);
        }
    };

    const toggleRepeatable = async (e) => {
        e.stopPropagation();
        const newValue = !repeatable;
        setRepeatable(newValue);
        try {
            const res = await axios.put(
                route('designer.form.sections.repeatable', {
                    project: project.id,
                    form: form.id,
                    section: section.id,
                }),
                { repeatable: newValue },
            );
            if (res.status === 200) onModified();
        } catch (error) {
            console.error('Repeatable toggle failed:', error);
        }
    };

    const handleSectionNameChange = (e) => {
        const newName = e.target.value;
        setSectionName(newName);
        if (timeoutRef.current) clearTimeout(timeoutRef.current);
        timeoutRef.current = setTimeout(() => {
            renameSection(section.id, newName);
        }, 400);
    };

    useEffect(() => {
        return () => {
            if (timeoutRef.current) clearTimeout(timeoutRef.current);
        };
    }, []);

    return (
        <>
            <li
                key={section.id}
                className={`hover:bg-base-300 cursor-pointer rounded-lg p-2 ${
                    selectedSectionId === section.id
                        ? 'border-primary bg-base-300 border-2 shadow-2xl'
                        : ''
                }`}
                onClick={() => onClick(section.id)}
            >
                <div className="mb-1 text-sm font-semibold">
                    {t('designer.section')} #{section.order}
                </div>
                <Input
                    className="input-sm"
                    type="text"
                    value={sectionName}
                    onChange={handleSectionNameChange}
                    onClick={(e) => e.stopPropagation()}
                />
                <div className="form-control mt-2">
                    <label className="label cursor-pointer justify-start gap-2">
                        <input
                            type="checkbox"
                            className="checkbox checkbox-sm"
                            checked={repeatable}
                            onChange={toggleRepeatable}
                            onClick={(e) => e.stopPropagation()}
                        />
                        <span className="label-text text-sm">
                            {t('designer.repeatable')}
                        </span>
                    </label>
                </div>
                <div className="mt-2 flex justify-between gap-2">
                    <button
                        className="btn btn-sm btn-outline grow"
                        onClick={(e) => {
                            e.stopPropagation();
                            moveSection('up');
                        }}
                    >
                        <FontAwesomeIcon icon={faArrowUp} />
                    </button>
                    <button
                        className="btn btn-sm btn-outline grow"
                        onClick={(e) => {
                            e.stopPropagation();
                            moveSection('down');
                        }}
                    >
                        <FontAwesomeIcon icon={faArrowDown} />
                    </button>
                    <button
                        className="btn btn-sm btn-error"
                        onClick={(e) => {
                            e.stopPropagation();
                            deletionModalRef.current?.showModal();
                        }}
                    >
                        <FontAwesomeIcon icon={faTrash} />
                    </button>
                </div>
            </li>

            <DeletionModal
                modalRef={deletionModalRef}
                name={sectionName}
                url={route('designer.form.sections.delete', {
                    project: project.id,
                    form: form.id,
                    section: section.id,
                })}
                onDeleted={onModified}
                useRouter={false}
            />
        </>
    );
}
