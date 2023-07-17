<x-app-layout>
    <x-slot name="action">
        <a class="btn btn-ghost btn-circle" href="{{ route('catalogs.index') }}">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    </x-slot>
    <x-slot name="header">Catálogo etnobotánico</x-slot>
    <x-slot name="subtitle">{{ $project->name }}</x-slot>

    <x-card title="Información de la especie">
        <table class="table table-compact table-fixed w-full">
            <thead>
                <tr>
                    <th class="lg:w-1/6">Familia</th>
                    <th class="lg:w-2/3">Especie</th>
                    <th class="lg:w-1/6">Reportes</th>
                    <th class="lg:w-1/6">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($species as $sp)
                <tr>
                    <td class="text-wrap">{{ $sp->family }}</td>
                    <td class="text-wrap"><span class="italic">{{ $sp->genus }} {{ $sp->name }}</span> {{ $sp->authority }}</td>
                    <td class="text-wrap">{{ count($sp->answers) }}</td>
                    <td>
                        <a href="{{ route('catalogs.species.show', ['project' => $project, 'species' => $sp]) }}" class="btn btn-primary btn-xs">
                            <i class="fa-solid fa-eye mr-2"></i> Ver ficha
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $species->links() }}
    </x-card>
</x-app-layout>