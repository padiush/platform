import { describe, expect, it } from 'vitest';
import {
    createItem,
    createSection,
    effectiveName,
    itemSummary,
    moveItem,
    prepareForSave,
    signature,
    slugify,
    toDraft,
    validateDraft,
} from './designerDraft';

const structure = [
    {
        id: 1,
        name: 'Plant',
        repeatable: false,
        items: [
            {
                id: 10,
                label: 'Use category',
                name: 'use_category',
                type: 'select',
                required: true,
                link_to_species: false,
                min: null,
                max: null,
                step: null,
                options: ['Medicinal', 'Food'],
                answers_count: 3,
            },
        ],
    },
];

describe('slugify', () => {
    it('matches the server slug shape', () => {
        expect(slugify('Nombre científico')).toBe('nombre_cientifico');
        expect(slugify('  Use -- Category!  ')).toBe('use_category');
        expect(slugify('ÁÉÍ óú ñ')).toBe('aei_ou_n');
    });
});

describe('toDraft', () => {
    it('assigns unique clientIds and marks stored names as edited', () => {
        const draft = toDraft(structure);

        expect(draft[0].clientId).toBeTruthy();
        expect(draft[0].items[0].clientId).toBeTruthy();
        expect(draft[0].clientId).not.toBe(draft[0].items[0].clientId);
        expect(draft[0].items[0].nameEdited).toBe(true);
        expect(createItem('text').nameEdited).toBe(false);
    });
});

describe('effectiveName', () => {
    it('derives from the label until the name is edited', () => {
        const item = { ...createItem('text'), label: 'Nombre del informante' };
        expect(effectiveName(item)).toBe('nombre_del_informante');

        const edited = { ...item, name: 'Informant Name', nameEdited: true };
        expect(effectiveName(edited)).toBe('informant_name');
    });
});

describe('signature', () => {
    it('ignores clientIds but sees content changes', () => {
        const a = toDraft(structure);
        const b = toDraft(structure);
        expect(signature(a)).toBe(signature(b));

        const renamed = [{ ...b[0], name: 'Planta' }];
        expect(signature(a)).not.toBe(signature(renamed));

        const reordered = [{ ...b[0], items: [...b[0].items].reverse() }];
        expect(signature(a)).toBe(signature(reordered)); // single item: no-op
    });

    it('changes when items reorder', () => {
        const draft = toDraft(structure);
        const twoItems = [
            {
                ...draft[0],
                items: [
                    draft[0].items[0],
                    { ...createItem('text'), label: 'B' },
                ],
            },
        ];
        const swapped = [
            { ...twoItems[0], items: moveItem(twoItems[0].items, 0, 1) },
        ];
        expect(signature(twoItems)).not.toBe(signature(swapped));
    });
});

describe('prepareForSave', () => {
    it('trims, drops empty options, and fills names from labels', () => {
        const item = {
            ...createItem('select'),
            label: '  Parte usada ',
            options: [' Hoja ', '', 'Raíz'],
        };
        const payload = prepareForSave([
            { ...createSection(), name: ' Uso ', items: [item] },
        ]);

        expect(payload[0].name).toBe('Uso');
        expect(payload[0].items[0].label).toBe('Parte usada');
        expect(payload[0].items[0].name).toBe('parte_usada');
        expect(payload[0].items[0].options).toEqual(['Hoja', 'Raíz']);
        expect(payload[0].items[0].id).toBeUndefined();
        expect(payload[0].id).toBeUndefined();
    });

    it('keeps ids for stored rows and nulls blank number bounds', () => {
        const payload = prepareForSave(toDraft(structure));

        expect(payload[0].id).toBe(1);
        expect(payload[0].items[0].id).toBe(10);
        expect(payload[0].items[0].min).toBeNull();
    });
});

describe('validateDraft', () => {
    it('flags empty section names, empty labels, and optionless choice fields', () => {
        const issues = validateDraft([
            {
                ...createSection(),
                items: [
                    { ...createItem('text') },
                    {
                        ...createItem('select'),
                        label: 'Choice',
                        options: ['', ' '],
                    },
                ],
            },
        ]);

        expect(issues).toEqual([
            {
                key: 'designer.issues.section_name',
                sectionIndex: 0,
                itemIndex: null,
            },
            {
                key: 'designer.issues.item_label',
                sectionIndex: 0,
                itemIndex: 0,
            },
            {
                key: 'designer.issues.item_options',
                sectionIndex: 0,
                itemIndex: 1,
            },
        ]);
    });

    it('accepts a complete draft', () => {
        expect(validateDraft(toDraft(structure))).toEqual([]);
    });
});

describe('itemSummary', () => {
    it('lists type, required, options, and recorded answers', () => {
        const t = (key, params) =>
            params?.count !== undefined ? `${key}:${params.count}` : key;
        const facts = itemSummary(toDraft(structure)[0].items[0], t);

        expect(facts).toEqual([
            'designer.item_types.select',
            'designer.fields.required',
            'designer.summary.options:2',
            'designer.summary.answers:3',
        ]);
    });
});
