import Input from '@/Components/Input';
import PublicLayout from '@/Layouts/PublicLayout';
import { useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Contact() {
    const { t } = useTranslation();
    const { honeypot } = usePage().props;

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        message: '',
        ...(honeypot?.enabled
            ? {
                  [honeypot.nameFieldName]: '',
                  [honeypot.validFromFieldName]: honeypot.encryptedValidFrom,
              }
            : {}),
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('public.contact.handle'), {
            preserveScroll: true,
            onSuccess: () => reset('name', 'email', 'message'),
        });
    };

    return (
        <PublicLayout title={t('public.contact')}>
            <div className="bg-primary text-primary-content py-12">
                <h1 className="text-center text-3xl font-bold md:text-5xl">
                    {t('public.contact')}
                </h1>
            </div>
            <div className="flex justify-center">
                <div className="w-full max-w-2xl px-4 py-8 md:py-12">
                    <div className="card bg-base-100 border-base-300 text-base-content border shadow-sm">
                        <div className="card-body">
                            <div>
                                <h2 className="text-2xl font-bold">
                                    {t('public.doubts')}
                                </h2>
                                <p className="text-base-content/70">
                                    {t('public.contact_desc')}
                                </p>
                                <p className="text-base-content/70 mt-2">
                                    {t('public.contact_direct')}{' '}
                                    <a
                                        className="link link-primary"
                                        href="mailto:hola@padiushbio.com"
                                    >
                                        hola@padiushbio.com
                                    </a>
                                </p>
                            </div>
                            <form onSubmit={submit} className="space-y-2">
                                {honeypot?.enabled && (
                                    <div style={{ display: 'none' }}>
                                        <input
                                            type="text"
                                            name={honeypot.nameFieldName}
                                            id={honeypot.nameFieldName}
                                            value={data[honeypot.nameFieldName]}
                                            onChange={(e) =>
                                                setData(
                                                    honeypot.nameFieldName,
                                                    e.target.value,
                                                )
                                            }
                                            tabIndex={-1}
                                            autoComplete="off"
                                        />
                                        <input
                                            type="text"
                                            name={honeypot.validFromFieldName}
                                            value={
                                                data[
                                                    honeypot.validFromFieldName
                                                ]
                                            }
                                            readOnly
                                        />
                                    </div>
                                )}
                                <Input
                                    type="text"
                                    name="name"
                                    label={t('public.name')}
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    error={errors.name}
                                    required
                                />
                                <Input
                                    type="email"
                                    name="email"
                                    label={t('public.email')}
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                    error={errors.email}
                                    required
                                />
                                <Input
                                    type="textarea"
                                    name="message"
                                    label={t('public.message')}
                                    value={data.message}
                                    onChange={(e) =>
                                        setData('message', e.target.value)
                                    }
                                    error={errors.message}
                                    required
                                />
                                <div className="pt-4">
                                    <button
                                        type="submit"
                                        className="btn btn-primary"
                                        disabled={processing}
                                    >
                                        {t('public.send')}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
