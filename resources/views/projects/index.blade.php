<x-app-layout>
    <x-slot name="header">Mis proyectos</x-slot>

    @if(count($projects) > 0)
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
        @foreach($projects as $project)
        <x-card title="{{ $project->name }}">
            @isset($project->author)
            Creado el {{ $project->created_at->format('d/m/Y') }} por {{ $project->user->name }}.
            @endisset
            <a class="btn btn-primary btn-sm" href="{{ route('projects.edit', ['project' => $project]) }}">
                Editar detalles
            </a>
            <a class="btn btn-primary btn-sm" href="{{ route('projects.accesses', ['project' => $project]) }}">
                Administrar acceso
            </a>
        </x-card>
        @endforeach
    </div>
    @else
    <div class="alert shadow-lg">
        <div>
            <i class="fa-solid fa-circle-info"></i>
            <span>No eres autor ni has participado en ningún proyecto aún.</span>
        </div>
    </div>
    @endif

    <x-card class="mt-4">
        <a class="btn btn-primary" href="{{ route('projects.create') }}">
            Crear proyecto
        </a>
    </x-card>
</x-app-layout>