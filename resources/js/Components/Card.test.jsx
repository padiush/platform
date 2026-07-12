import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import Card from './Card';

describe('Card', () => {
    it('renders kicker, title, description, and actions', () => {
        render(
            <Card
                kicker="Section"
                title="Project details"
                description="Everything about the project."
                actions={<button type="button">Edit</button>}
            >
                body
            </Card>,
        );

        expect(screen.getByText('Section')).toBeInTheDocument();
        expect(screen.getByText('Project details')).toBeInTheDocument();
        expect(
            screen.getByText('Everything about the project.'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Edit' }),
        ).toBeInTheDocument();
        expect(screen.getByText('body')).toBeInTheDocument();
    });

    it('renders without a header when no header props are given', () => {
        const { container } = render(<Card>just body</Card>);

        expect(screen.getByText('just body')).toBeInTheDocument();
        expect(container.querySelector('h2')).toBeNull();
    });
});
