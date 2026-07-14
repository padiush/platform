import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('axios', () => ({
    default: { get: vi.fn(), post: vi.fn() },
}));

// A stateful useForm mock so prefill updates are reflected in the inputs.
vi.mock('@inertiajs/react', async () => {
    const React = await vi.importActual('react');
    return {
        useForm: (initial) => {
            const [data, setDataState] = React.useState(initial);
            const setData = (key, value) =>
                setDataState((d) => ({ ...d, [key]: value }));
            return {
                data,
                setData,
                post: vi.fn(),
                processing: false,
                errors: {},
            };
        },
    };
});

globalThis.route = (name) => `/${name}`;

import axios from 'axios';
import SpeciesForm from './SpeciesForm';

const project = { id: 1, name: 'Verify Project' };

describe('SpeciesForm', () => {
    beforeEach(() => {
        axios.get.mockReset();
        axios.post.mockReset();
    });

    it('prefills the taxonomy fields from a chosen WFO name', async () => {
        axios.get.mockResolvedValue({
            data: {
                results: [
                    {
                        wfo_id: 'wfo-1',
                        full_name_html: '<i>Cecropia obtusifolia</i> Bertol.',
                        is_accepted: true,
                        accepted_name: null,
                    },
                ],
            },
        });
        axios.post.mockResolvedValue({
            data: {
                wfo_id: 'wfo-1',
                name_plain: 'Cecropia obtusifolia Bertol.',
                family: 'Urticaceae',
                genus: 'Cecropia',
                name: 'obtusifolia',
                authority: 'Bertol.',
            },
        });

        const { container } = render(
            <SpeciesForm project={project} onClose={() => {}} />,
        );

        fireEvent.change(
            container.querySelector('input[name="source-search"]'),
            {
                target: { value: 'Cecropia' },
            },
        );

        // Debounced search renders the candidate; pick it.
        const accepted = await screen.findByText('catalogs.source.accepted');
        fireEvent.click(accepted.closest('button'));

        await waitFor(() =>
            expect(container.querySelector('input[name="genus"]').value).toBe(
                'Cecropia',
            ),
        );
        expect(container.querySelector('input[name="name"]').value).toBe(
            'obtusifolia',
        );
        expect(container.querySelector('input[name="family"]').value).toBe(
            'Urticaceae',
        );
    });

    it('keeps manual entry working without a source lookup', async () => {
        const { container } = render(
            <SpeciesForm project={project} onClose={() => {}} />,
        );

        const genus = container.querySelector('input[name="genus"]');
        fireEvent.change(genus, { target: { value: 'Inga' } });

        expect(genus.value).toBe('Inga');
        expect(axios.get).not.toHaveBeenCalled();
    });
});
