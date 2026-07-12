import { Link } from '@inertiajs/react';

/**
 * In-place empty state for hub pages: says why the page is empty and where
 * to go next, instead of bouncing the user somewhere else with an error.
 */
export default function EmptyState({ title, hint, ctaHref, ctaLabel }) {
    return (
        <div className="border-base-300 rounded-box mx-auto my-8 max-w-xl border border-dashed p-10 text-center">
            <h3 className="text-lg font-bold">{title}</h3>
            {hint && <p className="text-base-content/60 mt-2">{hint}</p>}
            {ctaHref && (
                <Link href={ctaHref} className="btn btn-primary mt-4">
                    {ctaLabel}
                </Link>
            )}
        </div>
    );
}
