import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('axios', () => ({ default: { post: vi.fn() } }));

globalThis.route = (name, params) => `/${name}/${params}`;

import ItemRender from './ItemRender';

const item = {
    id: 4,
    label: 'Nombre local de la planta',
    name: 'nombre_local',
    type: 'text',
    required: false,
};

const instance = { id: 'a9731e28' };

/** The "Ruda" answer, in the third set, with what was recorded from it. */
function answers(fieldRecords) {
    return [
        {
            item_id: 4,
            section_id: 2,
            repeatable_index: 2,
            value: 'Ruda',
            field_records: fieldRecords,
        },
    ];
}

const numbered = {
    id: 13,
    accession_number: null,
    collection_number: 'RA-031',
    was_collected: true,
};

const observed = {
    id: 14,
    accession_number: null,
    collection_number: null,
    was_collected: false,
};

describe('ItemRender', () => {
    it('lists the field records made from the answer, linked to their rows', () => {
        render(
            <ItemRender
                item={item}
                instance={instance}
                repeatableIndex={2}
                answers={answers([numbered])}
                catalogProjectId={1}
            />,
        );

        expect(
            screen.getByText('interviews.field_records'),
        ).toBeInTheDocument();
        expect(screen.getByText('RA-031')).toHaveAttribute(
            'href',
            '/catalogs.fieldRecords.index/1#record-13',
        );
    });

    /** Nothing was taken to number: it reads as what it is. */
    it('names an observation as one', () => {
        render(
            <ItemRender
                item={item}
                instance={instance}
                repeatableIndex={2}
                answers={answers([observed])}
                catalogProjectId={1}
            />,
        );

        expect(
            screen.getByText('catalogs.fieldRecords.basis_human_observation'),
        ).toBeInTheDocument();
    });

    it('lists them unlinked for someone who cannot read the catalog', () => {
        render(
            <ItemRender
                item={item}
                instance={instance}
                repeatableIndex={2}
                answers={answers([numbered])}
            />,
        );

        expect(screen.getByText('RA-031').closest('a')).toBeNull();
    });

    it('says nothing for an answer nothing was recorded from', () => {
        render(
            <ItemRender
                item={item}
                instance={instance}
                repeatableIndex={2}
                answers={answers([])}
                catalogProjectId={1}
            />,
        );

        expect(
            screen.queryByText('interviews.field_records'),
        ).not.toBeInTheDocument();
    });

    /** Records belong to the answer in their own set, not to its neighbours. */
    it('shows only the records of its own set', () => {
        render(
            <ItemRender
                item={item}
                instance={instance}
                repeatableIndex={0}
                answers={answers([numbered])}
                catalogProjectId={1}
            />,
        );

        expect(screen.queryByText('RA-031')).not.toBeInTheDocument();
    });
});
