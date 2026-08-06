import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import FieldLabel from './FieldLabel';

describe('FieldLabel', () => {
    it('renders nothing without a label', () => {
        const { container } = render(<FieldLabel id="a" label="" required />);

        expect(container).toBeEmptyDOMElement();
    });

    it('associates the label with its control', () => {
        render(<FieldLabel id="email" label="Email" />);

        expect(screen.getByText('Email').closest('label')).toHaveAttribute(
            'for',
            'email',
        );
    });

    it('keeps the required marker beside the label text', () => {
        render(<FieldLabel id="email" label="Email" required />);

        const marker = screen.getByText('*');

        // Regression: daisyUI's .fieldset-legend is a flex row with
        // space-between, so a marker rendered as a sibling of the text gets
        // pushed to the far edge of the field. They must share one flex child.
        expect(marker.parentElement).toBe(
            screen.getByText('Email').closest('span'),
        );
    });

    it('hides the required marker from assistive tech', () => {
        render(<FieldLabel id="email" label="Email" required />);

        // The control itself carries `required`; a spoken "asterisk" is noise.
        expect(screen.getByText('*')).toHaveAttribute('aria-hidden', 'true');
    });

    it('gives the selective marker a text alternative', () => {
        render(<FieldLabel id="uses" label="Uses" selective />);

        // Nothing on the control conveys "at least one", so the asterisk needs
        // an equivalent that assistive tech can actually read.
        expect(screen.getByText('validation.at_least_one')).toHaveClass(
            'sr-only',
        );
    });

    it('renders no markers when the field is neither required nor selective', () => {
        render(<FieldLabel id="notes" label="Notes" />);

        expect(screen.queryByText('*')).not.toBeInTheDocument();
    });
});
