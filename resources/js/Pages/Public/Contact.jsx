import PublicLayout from '@/Layouts/PublicLayout';
import { useTranslation } from 'react-i18next';

export default function Contact() {
    const { t } = useTranslation();

    return (
        <PublicLayout title={t('public.contact')}>
            <div className="bg-base-300 text-base-content py-12">
                <h1 className="text-center text-3xl font-bold md:text-5xl">
                    {t('public.contact')}
                </h1>
            </div>
            <div className="flex justify-center">
                <div className="px-4 py-4 md:w-[80vw] lg:w-[60vw]">
                    <div className="card bg-base-200 text-base-content shadow-xl">
                        <div className="card-body">
                            <div>
                                <h2 className="text-2xl font-bold">
                                    {t('public.doubts')}
                                </h2>
                                <p>{t('public.contact_desc')}</p>
                            </div>
                            <form
                                action={route('public.contact.handle')}
                                method="post"
                            >
                                <input
                                    type="hidden"
                                    name="_token"
                                    value={
                                        document.head.querySelector(
                                            'meta[name=csrf-token]',
                                        ).content
                                    }
                                />
                                <input
                                    type="hidden"
                                    name="_honeypot"
                                    className="hidden"
                                />
                                <div className="form-control w-full">
                                    <label className="label">
                                        <span className="label-text">
                                            {t('public.name')}
                                        </span>
                                    </label>
                                    <input
                                        type="text"
                                        name="name"
                                        className="input input-bordered w-full"
                                    />
                                </div>
                                <div className="form-control w-full">
                                    <label className="label">
                                        <span className="label-text">
                                            {t('public.email')}
                                        </span>
                                    </label>
                                    <input
                                        type="email"
                                        name="email"
                                        className="input input-bordered w-full"
                                    />
                                </div>
                                <div className="form-control w-full">
                                    <label className="label">
                                        <span className="label-text">
                                            {t('public.message')}
                                        </span>
                                    </label>
                                    <textarea
                                        name="message"
                                        className="textarea textarea-bordered w-full"
                                    />
                                </div>
                                <div className="pt-4">
                                    <button
                                        type="submit"
                                        className="btn btn-primary"
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
