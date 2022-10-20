<x-app-layout>
    <x-slot name="action">
        <a class="btn btn-ghost btn-circle" href="{{ route('projects.accesses', ['project' => $project]) }}">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    </x-slot>
    <x-slot name="header">Invitaciones pendientes</x-slot>
    <x-slot name="subtitle">{{ $project->name }}</x-slot>
    <div class="grid grid-cols-1 gap-4 w-full">
        <x-card title="Invitaciones">
            <table class="table table-compact w-full">
                <thead>
                    <tr>
                        <td>Nombre</td>
                        <td class="hidden lg:table-cell">Correo electrónico</td>
                        <td>Categoría</td>
                        <td>Expira</td>
                        <td>Acciones</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invites as $invite)
                    <tr>
                        <td>@if($invite->invitedUser){{ $invite->invitedUser->name }}@else{{ $invite->invited_name }}@endif</td>
                        <td class="hidden lg:table-cell">{{ $invite->invited_email }}</td>
                        <td>{{ $invite->capability->name }}</td>
                        <td>{{ $invite->expires_at->diffForHumans() }}</td>
                        <td>
                            <form action="{{ route('projects.accesses.invites.revoke', ['project' => $project, 'invite' => $invite]) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-xs" type="submit">
                                    <i class="fa-solid fa-trash lg:mr-2"></i><span class="hidden lg:inline">Revocar</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </x-card>
    </div>
</x-app-layout>