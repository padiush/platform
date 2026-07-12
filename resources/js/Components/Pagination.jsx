import { Link } from '@inertiajs/react';

/**
 * Renders a Laravel paginator's `links` array as a daisyUI join of buttons.
 * Labels come from the server (already localized, may contain HTML entities
 * for the previous/next arrows), so they're set via dangerouslySetInnerHTML.
 */
export default function Pagination({ links, className = '' }) {
    if (!links || links.length <= 3) {
        // Only prev/current/next — a single page, nothing to page through.
        return null;
    }

    return (
        <div className={`flex justify-center ${className}`}>
            <div className="join">
                {links.map((link, index) => (
                    <Link
                        key={index}
                        href={link.url || '#'}
                        className={`join-item btn btn-sm ${
                            link.active ? 'btn-primary' : 'btn-ghost'
                        } ${link.url ? '' : 'btn-disabled'}`}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ))}
            </div>
        </div>
    );
}
