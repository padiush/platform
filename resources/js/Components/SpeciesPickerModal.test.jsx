import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import SpeciesPickerModal from './SpeciesPickerModal';

vi.mock('axios', () => ({
    default: {
        get: vi.fn(),
        isCancel: vi.fn(() => false),
    },
}));

import axios from 'axios';

globalThis.route = (name) => `/${name}`;

const species = [
    {
        id: 1,
        genus: 'Cecropia',
        name: 'obtusifolia',
        authority: 'Bertol.',
        family: 'Urticaceae',
    },
];

describe('SpeciesPickerModal', () => {
    beforeEach(() => {
        axios.get.mockResolvedValue({ data: { data: species } });
    });

    it('lists search results and picks one', async () => {
        const onPick = vi.fn();
        const modalRef = { current: null };

        render(
            <SpeciesPickerModal
                modalRef={modalRef}
                project={{ id: 1 }}
                onPick={onPick}
            />,
        );

        const label = await screen.findByText(/Cecropia/);
        fireEvent.click(label.closest('button'));

        expect(onPick).toHaveBeenCalledWith(species[0]);
    });

    it('offers an unlink action when a species is already linked', async () => {
        const onPick = vi.fn();
        const modalRef = { current: null };

        render(
            <SpeciesPickerModal
                modalRef={modalRef}
                project={{ id: 1 }}
                currentSpeciesId={1}
                onPick={onPick}
            />,
        );

        const unlink = await screen.findByText('data.picker.unlink');
        fireEvent.click(unlink);

        expect(onPick).toHaveBeenCalledWith(null);
    });
});
