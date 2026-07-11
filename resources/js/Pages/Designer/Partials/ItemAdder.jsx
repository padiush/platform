import {
    faCalendarDay,
    faCircleDot,
    faInputNumeric,
    faInputText,
    faSquareCheck,
} from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import axios from 'axios';
import { useTranslation } from 'react-i18next';

export default function ItemAdder({
    project,
    form,
    section,
    onItemAdded = () => {},
}) {
    const { t } = useTranslation();

    const addItem = async (itemType) => {
        const response = await axios.post(
            route('designer.form.section.items.create', {
                project: project.id,
                form: form.id,
                section: section,
            }),
            {
                type: itemType,
            },
        );

        if (response.status === 200) {
            onItemAdded(); // no args, triggers refetch
        }
    };

    return (
        <div className="dock bg-base-200 shadow-2xl lg:ml-80 lg:w-auto!">
            <button onClick={() => addItem('text')}>
                <span className="text-xl">
                    <FontAwesomeIcon icon={faInputText} />
                </span>
                <span className="text-xs">
                    {t('designer.item_types.text_field')}
                </span>
            </button>
            <button onClick={() => addItem('number')}>
                <span className="text-xl">
                    <FontAwesomeIcon icon={faInputNumeric} />
                </span>
                <span className="text-xs">
                    {t('designer.item_types.number_field')}
                </span>
            </button>
            <button onClick={() => addItem('date')}>
                <span className="text-xl">
                    <FontAwesomeIcon icon={faCalendarDay} />
                </span>
                <span className="text-xs">
                    {t('designer.item_types.date_field')}
                </span>
            </button>
            <button onClick={() => addItem('multi')}>
                <span className="text-xl">
                    <FontAwesomeIcon icon={faSquareCheck} />
                </span>
                <span className="text-xs">
                    {t('designer.item_types.checkbox')}
                </span>
            </button>
            <button onClick={() => addItem('select')}>
                <span className="text-xl">
                    <FontAwesomeIcon icon={faCircleDot} />
                </span>
                <span className="text-xs">
                    {t('designer.item_types.single_choice')}
                </span>
            </button>
        </div>
    );
}
