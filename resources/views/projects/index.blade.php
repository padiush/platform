<x-app-layout>
    <x-slot name="header">Mis proyectos</x-slot>

    @if(count($invites) > 0)
    <div class="grid grid-cols-1 gap-4 pb-4">
        <x-card title="Invitaciones pendientes">
            <p>Has sido invitado a los siguientes proyectos:</p>
            <table class="table table-compact w-full">
                <thead>
                    <tr>
                        <td>Invitado por</td>
                        <td>Proyecto</td>
                        <td>Expira</td>
                        <td>Acciones</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invites as $invite)
                    <tr>
                        <td>{{ $invite->invitingUser->name }}</td>
                        <td>{{ $invite->project->name }}</td>
                        <td>{{ $invite->expires_at->diffForHumans() }}</td>
                        <td>
                            <div class="btn-group">
                                <a type="submit" class="btn btn-success btn-xs" href="{{ route('projects.invites.accept', $invite) }}">Aceptar</a>
                                <a type="submit" class="btn btn-danger btn-xs" href="{{ route('projects.invites.decline', $invite) }}">Rechazar</a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </x-card>
    </div>
    @endif

    @if(count($projects) > 0)
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
        @foreach($projects as $project)
        <x-card title="{{ $project->name }}">
            @isset($project->author)
            Creado el {{ $project->created_at->format('d/m/Y') }} por {{ $project->user->name }}.
            @endisset
            @if(Auth::user()->hasCapabilityOnProject($project, 'manage_project'))
            <a class="btn btn-primary btn-sm" href="{{ route('projects.edit', ['project' => $project]) }}">
                Editar detalles
            </a>
            <a class="btn btn-primary btn-sm" href="{{ route('projects.accesses', ['project' => $project]) }}">
                Administrar acceso
            </a>
            @endif
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