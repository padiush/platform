import Card from '@/Components/Card';
import DeletionModal from '@/Components/DeletionModal';
import Input from '@/Components/Input';
import Select from '@/Components/Select';
import { requestHeaders } from '@/utils/requestHeaders';
import {
    faArrowDown,
    faArrowUp,
    faPlus,
    faTrashCan,
} from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { motion } from 'motion/react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function ItemCard({
    project,
    form,
    section,
    item,
    onItemDeleted,
    onItemUpdated,
    onItemMoved = () => {},
}) {
    const { t } = useTranslation();
    const [itemData, setItemData] = useState({ ...item });

    const parseOptions = (raw) => {
        if (Array.isArray(raw)) return raw.length > 0 ? raw : [''];
        try {
            const parsed = JSON.parse(raw);
            return Array.isArray(parsed) && parsed.length > 0 ? parsed : [''];
        } catch {
            return [''];
        }
    };

    const [optionsList, setOptionsList] = useState(parseOptions(item.options));
    const timeoutRef = useRef(null);
    const modalRef = useRef();
    const [modalOptions, setModalOptions] = useState({
        name: item.label,
        url: '',
    });

    const openDeleteModal = () => {
        setModalOptions({
            name: item.label,
            url: route('designer.form.section.items.delete', {
                project: project.id,
                form: form.id,
                section,
                item: item.id,
            }),
        });
        modalRef.current.showModal();
    };

    const handleChange = (field, value) => {
        setItemData((prev) => ({ ...prev, [field]: value }));
        if (timeoutRef.current) clearTimeout(timeoutRef.current);

        timeoutRef.current = setTimeout(() => {
            updateItem({ ...itemData, [field]: value });
        }, 400);
    };

    const debounceUpdateOptions = (newOptions) => {
        setOptionsList(newOptions);
        if (timeoutRef.current) clearTimeout(timeoutRef.current);

        timeoutRef.current = setTimeout(() => {
            handleChange('options', newOptions);
        }, 400);
    };

    const updateItem = async (payload) => {
        await fetch(
            route('designer.form.section.items.update', {
                project: project.id,
                form: form.id,
                section,
                item: item.id,
            }),
            {
                method: 'PUT',
                headers: requestHeaders(),
                body: JSON.stringify(payload),
            },
        );
        if (onItemUpdated) onItemUpdated();
    };

    const moveItem = async (direction) => {
        const res = await fetch(
            route('designer.form.section.items.reorder', {
                project: project.id,
                form: form.id,
                section,
                item: item.id,
            }),
            {
                method: 'PUT',
                headers: requestHeaders(),
                body: JSON.stringify({ direction }),
            },
        );

        if (res.ok) {
            onItemMoved();
        }
    };

    useEffect(() => {
        return () => {
            if (timeoutRef.current) clearTimeout(timeoutRef.current);
        };
    }, []);

    useEffect(() => {
        setOptionsList(parseOptions(item.options));
    }, [item.options]);

    return (
        <motion.div
            layout
            initial={{ opacity: 0, y: -8 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: 8 }}
            transition={{ duration: 0.2 }}
            className="indicator w-full"
        >
            <div className="indicator-item indicator-top">
                <button
                    className="btn btn-sm btn-circle btn-error"
                    onClick={openDeleteModal}
                >
                    <span className="text-xl">
                        <FontAwesomeIcon icon={faTrashCan} />
                    </span>
                </button>
            </div>

            <Card className="w-full">
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-6">
                    <Select
                        className="lg:col-span-6"
                        label={t('designer.fields.type')}
                        value={itemData.type}
                        onChange={(e) => handleChange('type', e.target.value)}
                        required
                    >
                        <option value="text">
                            {t('designer.item_types.text_field')}
                        </option>
                        <option value="number">
                            {t('designer.item_types.number_field')}
                        </option>
                        <option value="date">
                            {t('designer.item_types.date_field')}
                        </option>
                        <option value="multi">
                            {t('designer.item_types.checkbox')}
                        </option>
                        <option value="select">
                            {t('designer.item_types.single_choice')}
                        </option>
                    </Select>

                    <Input
                        className="lg:col-span-3"
                        label={t('designer.fields.label')}
                        value={itemData.label}
                        onChange={(e) => handleChange('label', e.target.value)}
                        required
                        bottomLabel={t('designer.fields.label_description')}
                    />
                    <Input
                        className="lg:col-span-3"
                        label={t('designer.fields.name')}
                        value={itemData.name}
                        onChange={(e) => handleChange('name', e.target.value)}
                        required
                        bottomLabel={t('designer.fields.name_description')}
                    />

                    {itemData.type === 'number' && (
                        <>
                            <Input
                                className="lg:col-span-2"
                                label={t('designer.fields.min')}
                                type="number"
                                value={itemData.min ?? ''}
                                onChange={(e) =>
                                    handleChange('min', e.target.value)
                                }
                            />
                            <Input
                                className="lg:col-span-2"
                                label={t('designer.fields.max')}
                                type="number"
                                value={itemData.max ?? ''}
                                onChange={(e) =>
                                    handleChange('max', e.target.value)
                                }
                            />
                            <Input
                                className="lg:col-span-2"
                                label={t('designer.fields.step')}
                                type="number"
                                value={itemData.step ?? ''}
                                onChange={(e) =>
                                    handleChange('step', e.target.value)
                                }
                            />
                        </>
                    )}

                    {(itemData.type === 'multi' ||
                        itemData.type === 'select') && (
                        <div className="lg:col-span-6">
                            <label className="label">
                                <span className="label-text">
                                    {t('designer.fields.options')}
                                </span>
                            </label>

                            <div className="flex flex-col gap-2">
                                {optionsList.map((opt, index) => (
                                    <div className="flex gap-2" key={index}>
                                        <input
                                            className="input input-bordered input-sm w-full"
                                            type="text"
                                            value={opt}
                                            onChange={(e) => {
                                                const updated = [
                                                    ...optionsList,
                                                ];
                                                updated[index] = e.target.value;
                                                debounceUpdateOptions(updated);
                                            }}
                                        />
                                        <button
                                            className="btn btn-sm btn-error"
                                            onClick={() => {
                                                const updated =
                                                    optionsList.filter(
                                                        (_, i) => i !== index,
                                                    );
                                                debounceUpdateOptions(updated);
                                            }}
                                        >
                                            <FontAwesomeIcon
                                                icon={faTrashCan}
                                            />
                                        </button>
                                    </div>
                                ))}

                                <button
                                    className="btn btn-sm btn-primary"
                                    onClick={() =>
                                        debounceUpdateOptions([
                                            ...optionsList,
                                            '',
                                        ])
                                    }
                                >
                                    <FontAwesomeIcon icon={faPlus} />{' '}
                                    {t('designer.fields.add_option')}
                                </button>
                            </div>

                            <p className="mt-1 text-sm text-gray-500">
                                {t('designer.fields.options_description')}
                            </p>
                        </div>
                    )}

                    <div className="flex items-center gap-2 lg:col-span-3">
                        <input
                            type="checkbox"
                            className="checkbox"
                            checked={itemData.required}
                            onChange={(e) =>
                                handleChange('required', e.target.checked)
                            }
                        />
                        <span>{t('designer.fields.required')}</span>
                    </div>

                    <div className="flex items-center gap-2 lg:col-span-3">
                        <input
                            type="checkbox"
                            className="checkbox"
                            checked={itemData.link_to_species}
                            onChange={(e) =>
                                handleChange(
                                    'link_to_species',
                                    e.target.checked,
                                )
                            }
                        />
                        <span>{t('designer.fields.link_to_species')}</span>
                    </div>

                    <div className="flex gap-2 lg:col-span-6">
                        <button
                            className="btn btn-sm btn-outline grow"
                            onClick={() => moveItem('up')}
                        >
                            <FontAwesomeIcon icon={faArrowUp} />
                            <span>{t('designer.fields.move_up')}</span>
                        </button>
                        <button
                            className="btn btn-sm btn-outline grow"
                            onClick={() => moveItem('down')}
                        >
                            <FontAwesomeIcon icon={faArrowDown} />
                            <span>{t('designer.fields.move_down')}</span>
                        </button>
                    </div>
                </div>
            </Card>

            <DeletionModal
                modalRef={modalRef}
                name={modalOptions.name}
                url={modalOptions.url}
                onDeleted={onItemDeleted}
                useRouter={false}
            />
        </motion.div>
    );
}
