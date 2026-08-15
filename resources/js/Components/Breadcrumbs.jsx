import { faChevronRight } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

/**
 * Trail for drill-down pages: every ancestor is a link, the current page is
 * plain text. Items: [{ label, href? }] — omit href on the last one.
 */
export default function Breadcrumbs({ items }) {
    const { t } = useTranslation();

    return (
        <nav
            aria-label={t('navigation.breadcrumb')}
            className="text-base-content/60 flex flex-wrap items-center gap-2 text-sm font-normal"
        >
            {items.map((item, index) => (
                <span
                    className="flex min-w-0 items-center gap-2"
                    key={`${item.label}-${index}`}
                >
                    {item.href ? (
                        <Link
                            href={item.href}
                            className="hover:text-base-content truncate transition"
                        >
                            {item.label}
                        </Link>
                    ) : (
                        <span
                            className="text-base-content truncate font-medium"
                            aria-current="page"
                        >
                            {item.label}
                        </span>
                    )}
                    {index < items.length - 1 && (
                        <FontAwesomeIcon
                            icon={faChevronRight}
                            className="text-base-content/30 shrink-0 text-xs"
                        />
                    )}
                </span>
            ))}
        </nav>
    );
}
