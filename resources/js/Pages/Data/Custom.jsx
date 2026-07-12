import Card from '@/Components/Card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { faArrowLeft } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function CustomExport({ project, forms }) {
    const { t } = useTranslation();
    const { csrf_token } = usePage().props;
    const [selected, setSelected] = useState({});

    const toggle = (formId, itemId, checked) => {
        setSelected((prev) => {
            const list = prev[formId] ? [...prev[formId]] : [];
            if (checked) {
                list.push(itemId);
            } else {
                const idx = list.indexOf(itemId);
                if (idx !== -1) list.splice(idx, 1);
            }
            return { ...prev, [formId]: list };
        });
    };

    const handleSubmit = (e, formId) => {
        const input = e.target.querySelector('input[name="selected_fields"]');
        input.value = JSON.stringify(
            (selected[formId] || []).sort((a, b) => a - b),
        );
    };

    return (
        <AuthenticatedLayout
            title={t('data.custom_export')}
            breadcrumbs={[
                { label: t('navigation.data'), href: route('data.index') },
                { label: project.name },
            ]}
            subtitle={project.name}
            action={
                <Link className="btn btn-ghost" href={route('data.index')}>
                    <FontAwesomeIcon icon={faArrowLeft} />
                </Link>
            }
        >
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="grid w-full grid-cols-1 gap-4">
                        {forms.map((form) => (
                            <Card key={form.id} title={form.name}>
                                <div className="mb-2">
                                    {t('data.custom_instructions')}
                                </div>
                                <form
                                    onSubmit={(e) => handleSubmit(e, form.id)}
                                    action={route('data.custom', {
                                        project: project.id,
                                    })}
                                    method="post"
                                >
                                    <input
                                        type="hidden"
                                        name="_token"
                                        value={csrf_token}
                                    />
                                    <input
                                        type="hidden"
                                        name="form_id"
                                        value={form.id}
                                    />
                                    <input
                                        type="hidden"
                                        name="selected_fields"
                                    />
                                    {form.sections.map((section) => (
                                        <div key={section.id}>
                                            <h2 className="text-lg">
                                                {section.name} (
                                                {section.repeatable
                                                    ? t('data.repeatable')
                                                    : t('data.unique')}
                                                )
                                            </h2>
                                            {section.items.map((item) => (
                                                <div
                                                    className="form-control"
                                                    key={item.id}
                                                >
                                                    <label className="label cursor-pointer">
                                                        <span className="label-text">
                                                            {item.label}
                                                        </span>
                                                        <input
                                                            type="checkbox"
                                                            className="checkbox checkbox-primary"
                                                            onChange={(e) =>
                                                                toggle(
                                                                    form.id,
                                                                    item.id,
                                                                    e.target
                                                                        .checked,
                                                                )
                                                            }
                                                        />
                                                    </label>
                                                </div>
                                            ))}
                                            <div className="divider" />
                                        </div>
                                    ))}
                                    <div className="pt-4">
                                        <button
                                            type="submit"
                                            className="btn btn-primary"
                                        >
                                            {t('actions.generate')}
                                        </button>
                                    </div>
                                </form>
                            </Card>
                        ))}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
