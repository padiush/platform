import Card from '@/Components/Card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslation } from 'react-i18next';
import { Link, usePage } from '@inertiajs/react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowLeft } from '@fortawesome/pro-regular-svg-icons';

export default function EthnobotanyR({ project, forms }) {
    const { t } = useTranslation();
    const { csrf_token } = usePage().props;

    const handleSubmit = (e) => {
        const form = e.target;
        const select = form.querySelector('select[name="field_id"]');
        if (!select.value) {
            e.preventDefault();
            alert(t('validation.required'));
        }
    };

    return (
        <AuthenticatedLayout
            title={t('data.generate_ethnobotanyr')}
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
                                <form
                                    action={route('data.ethnobotanyR', { project: project.id })}
                                    method="post"
                                    onSubmit={handleSubmit}
                                >
                                    <input type="hidden" name="_token" value={csrf_token} />
                                    <input type="hidden" name="form_id" value={form.id} />
                                    <div className="form-control">
                                        <label className="label">
                                            <span className="label-text">{t('data.select_usage_field')}</span>
                                        </label>
                                        <select name="field_id" className="select select-bordered w-full" defaultValue="">
                                            <option value="" disabled hidden>
                                                {t('actions.select')}
                                            </option>
                                            {form.sections.map((section) =>
                                                section.items.map((item) => (
                                                    <option key={item.id} value={item.id}>
                                                        ({section.name}) {item.label}
                                                    </option>
                                                )),
                                            )}
                                        </select>
                                    </div>
                                    <div className="pt-4">
                                        <button type="submit" className="btn btn-primary">
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
