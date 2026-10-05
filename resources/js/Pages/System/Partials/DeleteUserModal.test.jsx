import {
    act,
    fireEvent,
    render,
    screen,
    waitFor,
} from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import DeleteUserModal from './DeleteUserModal';

const mockPost = vi.fn();
const mockDelete = vi.fn();
const mockGet = vi.fn();

vi.mock('@inertiajs/react', () => ({
    router: {
        post: (...args) => mockPost(...args),
        delete: (...args) => mockDelete(...args),
    },
}));
vi.mock('axios', () => ({ default: { get: (...args) => mockGet(...args) } }));

globalThis.route = (name, id) => `/${name}/${id}`;

const ana = { id: 7, name: 'Ana', email: 'ana@example.org' };
const marta = { id: 8, name: 'Marta', email: 'marta@example.org' };

const preview = {
    user: ana,
    projects: [
        {
            id: 1,
            name: 'Huertos',
            is_example: false,
            interviews: 96,
            bytes: 2048,
            files: 3,
        },
        {
            id: 2,
            name: 'Ejemplo',
            is_example: true,
            interviews: 0,
            bytes: 0,
            files: 0,
        },
    ],
    collaborators: { count: 4, names: ['Julio', 'Marta', 'Sara'] },
    files: 3,
    bytes: 2048,
    other_projects: 2,
    transferable: 1,
};

beforeEach(() => {
    mockPost.mockReset();
    mockDelete.mockReset();
    mockGet.mockReset().mockResolvedValue({ data: preview });
});

const open = () =>
    render(
        <DeleteUserModal user={ana} people={[ana, marta]} onClose={vi.fn()} />,
    );

describe('DeleteUserModal', () => {
    it('says what goes with the account before anything is deleted', async () => {
        open();

        await screen.findByText(/Huertos/);
        expect(mockGet).toHaveBeenCalledWith('/system.users.deletion/7');
        expect(
            screen.getByText(/system\.deletion\.projects/),
        ).toBeInTheDocument();
        expect(
            screen.getByText(/system\.deletion\.example/),
        ).toBeInTheDocument();
        expect(
            screen.getByText(/system\.deletion\.collaborators/),
        ).toHaveTextContent('"count":4');
        expect(
            screen.getByText(/system\.deletion\.others/),
        ).toBeInTheDocument();
        expect(mockDelete).not.toHaveBeenCalled();
    });

    it('deletes only once the email is typed', async () => {
        open();
        await screen.findByText(/Huertos/);
        const remove = screen.getByRole('button', {
            name: 'system.deletion.delete',
        });

        expect(remove).toBeDisabled();
        fireEvent.change(screen.getByRole('textbox'), {
            target: { value: 'ANA@example.org ' },
        });
        expect(remove).toBeEnabled();

        fireEvent.click(remove);
        expect(mockDelete).toHaveBeenCalledWith(
            '/system.users.delete/7',
            expect.any(Object),
        );
    });

    it('transfers the projects to the person chosen, then counts again', async () => {
        mockPost.mockImplementation((url, data, options) =>
            options.onSuccess(),
        );
        open();
        await screen.findByText(/Huertos/);

        fireEvent.click(
            screen.getByRole('button', { name: 'system.deletion.transfer' }),
        );
        const confirm = screen.getByRole('button', {
            name: 'system.deletion.transfer_confirm',
        });
        expect(confirm).toBeDisabled();
        // Nobody can be handed their own projects.
        expect(
            screen.queryByRole('option', { name: /ana@example.org/ }),
        ).toBeNull();

        fireEvent.change(screen.getByRole('combobox'), {
            target: { value: '8' },
        });
        await act(async () => fireEvent.click(confirm));

        expect(mockPost).toHaveBeenCalledWith(
            '/system.users.transfer/7',
            { to: '8' },
            expect.any(Object),
        );
        await waitFor(() => expect(mockGet).toHaveBeenCalledTimes(2));
    });

    it('offers no transfer when there is nothing to hand over', async () => {
        mockGet.mockResolvedValue({ data: { ...preview, transferable: 0 } });
        open();
        await screen.findByText(/Huertos/);

        expect(
            screen.queryByRole('button', { name: 'system.deletion.transfer' }),
        ).toBeNull();
    });

    it('says so when it cannot tell what would be deleted', async () => {
        mockGet.mockRejectedValue(new Error('offline'));
        open();

        expect(await screen.findByRole('alert')).toHaveTextContent(
            'system.deletion.failed',
        );
        expect(
            screen.getByRole('button', { name: 'system.deletion.delete' }),
        ).toBeDisabled();
    });
});
