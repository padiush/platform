import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({ router: { delete: vi.fn() } }));
vi.mock('axios', () => ({ default: { post: vi.fn() } }));

globalThis.route = (name, params) => `/${name}/${JSON.stringify(params)}`;

import SectionRender from './SectionRender';

const section = {
    id: 2,
    name: 'Usos reportados',
    repeatable: true,
    items: [
        {
            id: 4,
            label: 'Nombre local de la planta',
            name: 'nombre_local',
            type: 'text',
            required: false,
        },
    ],
};

const answers = [
    {
        item_id: 4,
        section_id: 2,
        repeatable_index: 0,
        value: 'Hierbabuena',
        field_records: [],
    },
    {
        item_id: 4,
        section_id: 2,
        repeatable_index: 1,
        value: 'Ruda',
        field_records: [
            {
                id: 13,
                accession_number: null,
                collection_number: 'RA-031',
                was_collected: true,
            },
        ],
    },
];

describe('SectionRender', () => {
    /** A set stays collapsed until opened; its header still says a record came out of it. */
    it('counts the field records made in each set, on its header', () => {
        render(
            <SectionRender
                section={section}
                instance={{ id: 'a9731e28' }}
                answers={answers}
            />,
        );

        const headers = screen
            .getAllByRole('group')
            .map((set) => set.querySelector('summary').textContent);

        expect(headers[0]).not.toContain('interviews.field_records_count');
        expect(headers[1]).toContain('interviews.field_records_count');
        expect(headers[1]).toContain('"count":1');
    });
});
