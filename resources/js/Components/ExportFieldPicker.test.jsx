import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import ExportFieldPicker from './ExportFieldPicker';

const sections = [
    {
        id: 1,
        name: 'Unique section',
        repeatable: false,
        items: [
            { id: 10, label: 'A' },
            { id: 11, label: 'B' },
        ],
    },
    {
        id: 2,
        name: 'Repeatable section',
        repeatable: true,
        items: [{ id: 20, label: 'C' }],
    },
];

describe('ExportFieldPicker', () => {
    it('locks sections of the other repeatability once a field is picked', () => {
        // A repeatable field is already selected → the unique section's fields
        // must be disabled.
        render(
            <ExportFieldPicker
                sections={sections}
                selected={new Set([20])}
                onToggleField={() => {}}
                onToggleSection={() => {}}
            />,
        );

        const a = screen.getByRole('checkbox', { name: 'A' });
        expect(a).toBeDisabled();
        const c = screen.getByRole('checkbox', { name: 'C' });
        expect(c).not.toBeDisabled();
        expect(
            screen.getByText('data.export.repeatable_locked'),
        ).toBeInTheDocument();
    });

    it('toggles a field', () => {
        const onToggleField = vi.fn();
        render(
            <ExportFieldPicker
                sections={sections}
                selected={new Set()}
                onToggleField={onToggleField}
                onToggleSection={() => {}}
            />,
        );

        fireEvent.click(screen.getByRole('checkbox', { name: 'A' }));
        expect(onToggleField).toHaveBeenCalledWith(10);
    });

    it('select-all toggles the whole section', () => {
        const onToggleSection = vi.fn();
        render(
            <ExportFieldPicker
                sections={sections}
                selected={new Set()}
                onToggleField={() => {}}
                onToggleSection={onToggleSection}
            />,
        );

        // The first "select all" checkbox belongs to the unique section.
        fireEvent.click(
            screen.getAllByRole('checkbox', {
                name: 'data.export.select_all',
            })[0],
        );
        expect(onToggleSection).toHaveBeenCalledWith(sections[0], true);
    });
});
