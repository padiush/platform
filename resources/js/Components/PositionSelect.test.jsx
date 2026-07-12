import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import PositionSelect from './PositionSelect';

describe('PositionSelect', () => {
    it('renders nothing when there is only one position', () => {
        const { container } = render(
            <PositionSelect index={0} count={1} onMove={() => {}} />,
        );

        expect(container).toBeEmptyDOMElement();
    });

    it('offers every position and reports the chosen one', () => {
        const onMove = vi.fn();
        render(<PositionSelect index={0} count={3} onMove={onMove} />);

        const select = screen.getByRole('combobox');
        expect(select.options).toHaveLength(3);

        fireEvent.change(select, { target: { value: '2' } });
        expect(onMove).toHaveBeenCalledWith(2);
    });
});
