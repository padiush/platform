import Card from '@/Components/Card';
import ExportFieldPicker from '@/Components/ExportFieldPicker';
import Select from '@/Components/Select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { faArrowLeft, faDownload } from '@fortawesome/pro-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link, usePage } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

function PreviewTable({ preview }) {
    if (!preview.columns.length || !preview.rows.length) {
        return null;
    }

    return (
        <div className="border-base-300 rounded-box mt-3 max-h-72 overflow-auto border">
            <table className="table-xs table">
                <thead>
                    <tr>
                        {preview.columns.map((column, index) => (
                            <th key={index}>{column}</th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {preview.rows.map((row, r) => (
                        <tr key={r}>
                            {row.map((cell, c) => (
                                <td key={c} className="whitespace-nowrap">
                                    {cell === '' ? (
                                        <span className="text-base-content/30">
                                            —
                                        </span>
                                    ) : (
                                        cell
                                    )}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export default function DataExport({ project, forms, initial }) {
    const { t } = useTranslation();
    const { csrf_token } = usePage().props;

    const [mode, setMode] = useState(initial.mode || 'custom');
    const [formId, setFormId] = useState(
        forms.find((f) => f.id === initial.form)?.id ?? forms[0]?.id ?? null,
    );

    const selectedForm = forms.find((f) => f.id === formId) ?? null;

    const [selected, setSelected] = useState(() => {
        const set = new Set();
        if (initial.section) {
            const form = forms.find(
                (f) => f.id === (initial.form ?? forms[0]?.id),
            );
            form?.sections
                .find((s) => s.id === initial.section)
                ?.items.forEach((item) => set.add(item.id));
        }
        return set;
    });

    const [categoryFieldId, setCategoryFieldId] = useState('');
    const [format, setFormat] = useState('xlsx');

    const [preview, setPreview] = useState(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [mixedError, setMixedError] = useState(false);

    const toggleField = (id) =>
        setSelected((prev) => {
            const next = new Set(prev);
            next.has(id) ? next.delete(id) : next.add(id);
            return next;
        });

    const toggleSection = (section, checked) =>
        setSelected((prev) => {
            const next = new Set(prev);
            section.items.forEach((item) =>
                checked ? next.add(item.id) : next.delete(item.id),
            );
            return next;
        });

    const changeForm = (id) => {
        setFormId(id);
        setSelected(new Set());
        setCategoryFieldId('');
        setPreview(null);
    };

    const selectedKey = [...selected].sort((a, b) => a - b).join(',');

    // Live preview of the resulting export (columns, counts, sample rows).
    useEffect(() => {
        const ready =
            mode === 'custom' ? selected.size > 0 : categoryFieldId !== '';

        if (!formId || !ready) {
            setPreview(null);
            setMixedError(false);
            return;
        }

        const controller = new AbortController();
        const timeout = setTimeout(async () => {
            setPreviewLoading(true);
            setMixedError(false);
            try {
                const { data } = await axios.get(
                    route('data.export.preview', { project: project.id }),
                    {
                        params: {
                            mode,
                            form_id: formId,
                            selected_fields: JSON.stringify([...selected]),
                            field_id: categoryFieldId,
                        },
                        signal: controller.signal,
                    },
                );
                setPreview(data);
            } catch (err) {
                if (axios.isCancel(err)) return;
                if (err.response?.status === 422) {
                    setMixedError(true);
                    setPreview(null);
                }
            } finally {
                setPreviewLoading(false);
            }
        }, 350);

        return () => {
            clearTimeout(timeout);
            controller.abort();
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [mode, formId, selectedKey, categoryFieldId]);

    const canDownload =
        !mixedError &&
        (mode === 'custom' ? selected.size > 0 : categoryFieldId !== '');

    return (
        <AuthenticatedLayout
            title={t('data.export.title')}
            breadcrumbs={[
                { label: t('navigation.data'), href: route('data.index') },
                { label: project.name },
            ]}
            subtitle={project.name}
            action={
                <Link
                    className="btn btn-ghost btn-circle"
                    href={route('data.index')}
                    aria-label={t('navigation.back')}
                >
                    <FontAwesomeIcon icon={faArrowLeft} />
                </Link>
            }
        >
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <Card title={t('data.export.title')}>
                        <div className="mb-4 flex flex-wrap items-end gap-3">
                            <div className="join">
                                <button
                                    type="button"
                                    className={`btn btn-sm join-item ${mode === 'custom' ? 'btn-primary' : 'btn-ghost'}`}
                                    onClick={() => setMode('custom')}
                                >
                                    {t('data.export.mode_custom')}
                                </button>
                                <button
                                    type="button"
                                    className={`btn btn-sm join-item ${mode === 'ethnobotanyr' ? 'btn-primary' : 'btn-ghost'}`}
                                    onClick={() => setMode('ethnobotanyr')}
                                >
                                    {t('data.export.mode_ethnobotanyr')}
                                </button>
                            </div>

                            {forms.length > 1 && (
                                <Select
                                    label={t('data.view.form')}
                                    value={formId ?? ''}
                                    onChange={(e) =>
                                        changeForm(Number(e.target.value))
                                    }
                                    className="w-56"
                                >
                                    {forms.map((form) => (
                                        <option key={form.id} value={form.id}>
                                            {form.name}
                                        </option>
                                    ))}
                                </Select>
                            )}
                        </div>

                        {mode === 'custom' ? (
                            <ExportFieldPicker
                                sections={selectedForm?.sections ?? []}
                                selected={selected}
                                onToggleField={toggleField}
                                onToggleSection={toggleSection}
                            />
                        ) : (
                            <div className="flex flex-col gap-2">
                                <p className="text-base-content/70 text-sm">
                                    {t('data.export.ethnobotanyr_help')}
                                </p>
                                <Select
                                    label={t('data.export.category_field')}
                                    value={categoryFieldId}
                                    onChange={(e) =>
                                        setCategoryFieldId(e.target.value)
                                    }
                                >
                                    <option value="" disabled>
                                        {t('actions.select')}
                                    </option>
                                    {(selectedForm?.sections ?? []).map(
                                        (section) =>
                                            section.items.map((item) => (
                                                <option
                                                    key={item.id}
                                                    value={item.id}
                                                >
                                                    ({section.name}){' '}
                                                    {item.label}
                                                </option>
                                            )),
                                    )}
                                </Select>
                            </div>
                        )}

                        {mixedError && (
                            <p className="text-error mt-3 text-sm">
                                {t('data.export.repeatable_locked')}
                            </p>
                        )}

                        <div className="border-base-300 mt-4 flex flex-wrap items-end justify-between gap-3 border-t pt-4">
                            <div className="text-base-content/70 text-sm">
                                {previewLoading && t('data.export.previewing')}
                                {!previewLoading && preview && (
                                    <span>
                                        {mode === 'custom'
                                            ? t('data.export.custom_counts', {
                                                  interviews:
                                                      preview.instance_count,
                                                  records: preview.record_count,
                                              })
                                            : t('data.export.species_counts', {
                                                  count: preview.species_count,
                                              })}
                                    </span>
                                )}
                            </div>

                            <form
                                method="post"
                                action={route('data.export.download', {
                                    project: project.id,
                                })}
                                className="flex items-end gap-2"
                            >
                                <input
                                    type="hidden"
                                    name="_token"
                                    value={csrf_token}
                                />
                                <input type="hidden" name="mode" value={mode} />
                                <input
                                    type="hidden"
                                    name="form_id"
                                    value={formId ?? ''}
                                />
                                <input
                                    type="hidden"
                                    name="selected_fields"
                                    value={JSON.stringify([...selected])}
                                />
                                <input
                                    type="hidden"
                                    name="field_id"
                                    value={categoryFieldId}
                                />
                                <Select
                                    label={t('data.export.format')}
                                    value={format}
                                    onChange={(e) => setFormat(e.target.value)}
                                    className="w-28"
                                >
                                    <option value="xlsx">Excel (.xlsx)</option>
                                    <option value="csv">CSV (.csv)</option>
                                </Select>
                                <input
                                    type="hidden"
                                    name="format"
                                    value={format}
                                />
                                <button
                                    type="submit"
                                    className="btn btn-primary btn-sm"
                                    disabled={!canDownload}
                                >
                                    <FontAwesomeIcon icon={faDownload} />
                                    {t('data.export.generate')}
                                </button>
                            </form>
                        </div>

                        {preview && <PreviewTable preview={preview} />}
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
