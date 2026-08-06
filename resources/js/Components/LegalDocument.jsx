import { useTranslation } from 'react-i18next';

/**
 * Renders one legal document (the privacy policy, the terms) from the `legal`
 * translation namespace, so the language toggle switches it like every other
 * string on the site.
 *
 * The source is structured rather than raw HTML: the document owns its words,
 * this component owns the markup. That keeps the heading hierarchy correct
 * (one h1, h2 per section) no matter what the translators write.
 *
 * @param {{ document: 'privacy' | 'terms' }} props
 */
export default function LegalDocument({ document }) {
    const { t, ready } = useTranslation('legal', { useSuspense: false });

    if (!ready) {
        return (
            <div className="flex justify-center py-20">
                <span
                    className="loading loading-spinner loading-lg text-primary"
                    role="status"
                />
            </div>
        );
    }

    const sections = t(`${document}.sections`, { returnObjects: true });

    return (
        <>
            <div className="bg-base-300 text-base-content py-12">
                <h1 className="text-center text-3xl font-bold md:text-5xl">
                    {t(`${document}.title`)}
                </h1>
                <p className="text-base-content/60 mt-2 text-center text-sm">
                    {t('updated_on', { date: t(`${document}.updated`) })}
                </p>
            </div>
            <article className="text-base-content mx-auto max-w-3xl px-6 py-10 leading-relaxed md:px-8">
                <p className="text-base-content/80 border-primary/40 mb-10 border-l-4 pl-4 text-lg">
                    {t(`${document}.summary`)}
                </p>
                {Array.isArray(sections) &&
                    sections.map((section, index) => (
                        <section key={index} className="mb-8">
                            <h2 className="mb-3 text-xl font-bold md:text-2xl">
                                {section.heading}
                            </h2>
                            {(section.blocks ?? []).map((block, blockIndex) => (
                                <Block key={blockIndex} block={block} />
                            ))}
                        </section>
                    ))}
            </article>
        </>
    );
}

function Block({ block }) {
    if (block.type === 'ul') {
        return (
            <ul className="mb-4 list-disc space-y-2 pl-6">
                {block.items.map((item, index) => (
                    <li key={index}>
                        {typeof item === 'string' ? (
                            item
                        ) : (
                            <>
                                <strong>{item.term}</strong> {item.text}
                            </>
                        )}
                    </li>
                ))}
            </ul>
        );
    }

    if (block.type === 'links') {
        return (
            <ul className="mb-4 list-disc space-y-2 pl-6">
                {block.items.map((item, index) => (
                    <li key={index}>
                        <a
                            className="link link-primary"
                            href={item.href}
                            {...(item.href.startsWith('http')
                                ? { target: '_blank', rel: 'noreferrer' }
                                : {})}
                        >
                            {item.label}
                        </a>
                    </li>
                ))}
            </ul>
        );
    }

    return <p className="mb-4">{block.text}</p>;
}
