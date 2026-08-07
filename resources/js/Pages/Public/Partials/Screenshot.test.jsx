import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import Screenshot from './Screenshot';

describe('Screenshot', () => {
    it('ships a variant for each theme and shows only one at a time', () => {
        const { container } = render(<Screenshot name="reports" />);

        const [light, dark] = container.querySelectorAll('img');

        expect(light).toHaveAttribute('src', '/images/site/reports-light.webp');
        expect(dark).toHaveAttribute('src', '/images/site/reports-dark.webp');

        // A bright screenshot dropped into a dark page reads as a mistake, so
        // CSS keyed on the theme attribute picks the matching file.
        expect(light.className).toContain(
            "[[data-theme='padiushdark']_&]:hidden",
        );
        expect(dark.className).toContain('hidden');
        expect(dark.className).toContain(
            "[[data-theme='padiushdark']_&]:block",
        );
    });

    it('describes the shot once, and hides the duplicate from assistive tech', () => {
        render(<Screenshot name="sankey" />);

        const described = screen.getByAltText('public.shot_sankey_alt');
        expect(described).toBeInTheDocument();

        const decorative = described.parentElement.querySelectorAll(
            'img[aria-hidden="true"]',
        );
        expect(decorative).toHaveLength(1);
        expect(decorative[0]).toHaveAttribute('alt', '');
    });

    it('declares intrinsic dimensions so the image reserves its space', () => {
        render(<Screenshot name="catalog" />);

        const image = screen.getByAltText('public.shot_catalog_alt');

        expect(image).toHaveAttribute('width', '2880');
        expect(image).toHaveAttribute('height', '1800');
        expect(image).toHaveAttribute('loading', 'lazy');
    });

    it('only draws window chrome when asked', () => {
        const { container: plain } = render(<Screenshot name="sankey" />);
        expect(plain.querySelectorAll('.rounded-full')).toHaveLength(0);

        const { container: framed } = render(
            <Screenshot name="reports" chrome />,
        );
        expect(framed.querySelectorAll('.rounded-full')).toHaveLength(3);
    });
});
