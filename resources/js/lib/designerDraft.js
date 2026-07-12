/**
 * Draft model for the interview designer: the whole form structure is edited
 * in memory and saved in one request. Drafts carry stable clientIds for React
 * keys; the signature (which excludes them) drives dirty tracking.
 */

let clientIdCounter = 0;

function nextClientId() {
    clientIdCounter += 1;
    return `draft-${clientIdCounter}`;
}

/** Mirrors Laravel's Str::slug($value, '_') closely enough for previews. */
export function slugify(value) {
    return String(value)
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
}

export function toDraftItem(item) {
    return {
        clientId: nextClientId(),
        id: item.id ?? null,
        label: item.label ?? '',
        name: item.name ?? '',
        type: item.type ?? 'text',
        required: Boolean(item.required),
        link_to_species: Boolean(item.link_to_species),
        is_use_category: Boolean(item.is_use_category),
        min: item.min ?? '',
        max: item.max ?? '',
        step: item.step ?? '',
        options: Array.isArray(item.options) ? [...item.options] : [],
        answers_count: item.answers_count ?? 0,
        // Existing items keep their stored name; new items auto-derive it
        // from the label until the author edits the name directly.
        nameEdited: Boolean(item.id),
    };
}

export function toDraft(structure) {
    return (structure ?? []).map((section) => ({
        clientId: nextClientId(),
        id: section.id ?? null,
        name: section.name ?? '',
        repeatable: Boolean(section.repeatable),
        items: (section.items ?? []).map(toDraftItem),
    }));
}

export function createSection() {
    return {
        clientId: nextClientId(),
        id: null,
        name: '',
        repeatable: false,
        items: [],
    };
}

const OPTION_TYPES = ['select', 'multi'];

export function createItem(type = 'text') {
    return {
        clientId: nextClientId(),
        id: null,
        label: '',
        name: '',
        type,
        required: false,
        link_to_species: false,
        is_use_category: false,
        min: '',
        max: '',
        step: '',
        options: OPTION_TYPES.includes(type) ? [''] : [],
        answers_count: 0,
        nameEdited: false,
    };
}

export function itemHasOptions(item) {
    return OPTION_TYPES.includes(item.type);
}

/** The name the item will be saved under (auto-derived until edited). */
export function effectiveName(item) {
    return item.nameEdited && item.name !== ''
        ? slugify(item.name)
        : slugify(item.label);
}

function signatureItem(item) {
    return {
        id: item.id,
        label: item.label,
        name: effectiveName(item),
        type: item.type,
        required: item.required,
        link_to_species: item.link_to_species,
        is_use_category: item.is_use_category,
        min: String(item.min ?? ''),
        max: String(item.max ?? ''),
        step: String(item.step ?? ''),
        options: itemHasOptions(item) ? item.options : [],
    };
}

export function signature(sections) {
    return JSON.stringify(
        sections.map((section) => ({
            id: section.id,
            name: section.name,
            repeatable: section.repeatable,
            items: section.items.map(signatureItem),
        })),
    );
}

export function prepareForSave(sections) {
    return sections.map((section) => ({
        ...(section.id ? { id: section.id } : {}),
        name: section.name.trim(),
        repeatable: section.repeatable,
        items: section.items.map((item) => ({
            ...(item.id ? { id: item.id } : {}),
            label: item.label.trim(),
            name: effectiveName(item),
            type: item.type,
            required: item.required,
            link_to_species: item.link_to_species,
            is_use_category: item.is_use_category,
            min: item.min === '' ? null : item.min,
            max: item.max === '' ? null : item.max,
            step: item.step === '' ? null : item.step,
            options: itemHasOptions(item)
                ? item.options
                      .map((option) => option.trim())
                      .filter((option) => option !== '')
                : [],
        })),
    }));
}

/**
 * Issues that block saving. Each issue carries the i18n key of its message
 * plus enough location info to point at the offending row.
 */
export function validateDraft(sections) {
    const issues = [];

    sections.forEach((section, sectionIndex) => {
        if (section.name.trim() === '') {
            issues.push({
                key: 'designer.issues.section_name',
                sectionIndex,
                itemIndex: null,
            });
        }

        section.items.forEach((item, itemIndex) => {
            if (item.label.trim() === '') {
                issues.push({
                    key: 'designer.issues.item_label',
                    sectionIndex,
                    itemIndex,
                });
            }

            if (
                itemHasOptions(item) &&
                item.options.every((option) => option.trim() === '')
            ) {
                issues.push({
                    key: 'designer.issues.item_options',
                    sectionIndex,
                    itemIndex,
                });
            }
        });
    });

    return issues;
}

export function moveItem(list, fromIndex, toIndex) {
    const next = [...list];
    const [moved] = next.splice(fromIndex, 1);
    next.splice(toIndex, 0, moved);
    return next;
}

/** One-line facts for a collapsed field card. */
export function itemSummary(item, t) {
    const facts = [t(`designer.item_types.${item.type}`)];

    if (item.required) {
        facts.push(t('designer.fields.required'));
    }

    if (itemHasOptions(item)) {
        const count = item.options.filter(
            (option) => option.trim() !== '',
        ).length;
        facts.push(t('designer.summary.options', { count }));
    }

    if (item.link_to_species) {
        facts.push(t('designer.summary.linked'));
    }

    if (item.is_use_category) {
        facts.push(t('designer.summary.use_category'));
    }

    if (item.answers_count > 0) {
        facts.push(
            t('designer.summary.answers', { count: item.answers_count }),
        );
    }

    return facts;
}
