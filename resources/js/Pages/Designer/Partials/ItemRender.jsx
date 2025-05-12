// resources/js/Pages/Designer/Partials/ItemRender.jsx
import Input from '@/Components/Input';
import Select from '@/Components/Select';
import { useTranslation } from 'react-i18next';

export default function ItemRender({ item }) {
    const { t } = useTranslation();

    const isRequired = item.required ?? false;
    const options = Array.isArray(item.options)
        ? item.options
        : typeof item.options === 'string'
          ? (() => {
                try {
                    const parsed = JSON.parse(item.options);
                    return Array.isArray(parsed) ? parsed : [];
                } catch {
                    return [];
                }
            })()
          : [];

    switch (item.type) {
        case 'text':
            return (
                <Input
                    label={item.label}
                    name={item.name}
                    required={isRequired}
                />
            );

        case 'number':
            return (
                <Input
                    label={item.label}
                    name={item.name}
                    type="number"
                    min={item.min}
                    max={item.max}
                    step={item.step}
                    required={isRequired}
                />
            );

        case 'date':
            return (
                <Input
                    label={item.label}
                    name={item.name}
                    type="date"
                    required={isRequired}
                />
            );

        case 'multi':
            return (
                <fieldset className="fieldset w-full">
                    <legend className="fieldset-legend">
                        {item.label}{' '}
                        {isRequired && (
                            <span
                                className="text-error tooltip tooltip-bottom"
                                data-tip="Campo requerido"
                            >
                                *
                            </span>
                        )}
                    </legend>
                    <div className="flex flex-col gap-2">
                        {options.map((opt, idx) => (
                            <label
                                key={idx}
                                className="label cursor-pointer gap-2"
                            >
                                <input type="checkbox" className="checkbox" />
                                <span>{opt}</span>
                            </label>
                        ))}
                    </div>
                </fieldset>
            );

        case 'select':
            return (
                <Select label={item.label} required={isRequired}>
                    <option disabled selected>
                        {t('designer.select_placeholder')}
                    </option>
                    {options.map((opt, idx) => (
                        <option key={idx}>{opt}</option>
                    ))}
                </Select>
            );

        default:
            return (
                <Input
                    label={item.label}
                    name={item.name}
                    required={isRequired}
                />
            );
    }
}
