import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import Pagination from './Pagination';

vi.mock('@inertiajs/react', () => ({
    Link: ({ href, children, ...props }) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
}));

const links = [
    { url: null, label: '&laquo;', active: false },
    { url: 'http://app/catalogs/1?page=1', label: '1', active: true },
    { url: 'http://app/catalogs/1?page=2', label: '2', active: false },
    { url: 'http://app/catalogs/1?page=2', label: '&raquo;', active: false },
];

describe('Pagination', () => {
    it('renders nothing for a single page', () => {
        const { container } = render(
            <Pagination
                links={[
                    { url: null, label: '&laquo;', active: false },
                    { url: 'http://app/catalogs/1', label: '1', active: true },
                    { url: null, label: '&raquo;', active: false },
                ]}
            />,
        );

        expect(container).toBeEmptyDOMElement();
    });

    it('renders page links and marks the active one', () => {
        render(<Pagination links={links} />);

        const active = screen.getByText('1');
        expect(active).toHaveClass('btn-primary');

        const two = screen.getByText('2');
        expect(two).toHaveAttribute('href', 'http://app/catalogs/1?page=2');
        expect(two).not.toHaveClass('btn-primary');
    });

    it('disables links without a url', () => {
        render(<Pagination links={links} />);

        const prev = screen.getByText('«');
        expect(prev).toHaveClass('btn-disabled');
        expect(prev).toHaveAttribute('href', '#');
    });
});
