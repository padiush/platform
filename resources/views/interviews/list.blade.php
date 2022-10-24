<x-app-layout>
    <x-slot name="action">
        <a class="btn btn-ghost btn-circle" href="{{ route('interviews.index') }}">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    </x-slot>
    <x-slot name="header">Entrevistas</x-slot>
    <x-slot name="subtitle">{{ $form->name }} (en {{ $project->name }})</x-slot>

    <x-card>
        <table class="table table-fixed table-compact w-full">
            <thead>
                <tr>
                    <th>Fecha y hora</th>
                    <th>Identificador</th>
                    <th>Creada por</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($instances as $instance)
                <tr>
                    <td>{{ $instance->created_at }}</td>
                    <td>{{ $instance->id }}</td>
                    <td>{{ $instance->user->name }}</td>
                    <td>
                        <a href="{{ route('interviews.show', $instance) }}" class="btn btn-xs btn-primary">Ver</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{ $instances->links() }}
    </x-card>
</x-app-layout>