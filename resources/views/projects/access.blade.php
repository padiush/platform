<x-app-layout>
    <x-slot name="action">
        <a class="btn btn-ghost btn-circle" href="{{ route('projects.index') }}">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    </x-slot>
    <x-slot name="header">Acceso al proyecto</x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 w-full">
        <div class="grid grid-cols-1 gap-4 w-full">
            @if(count($invites) > 0)
            <div class="indicator w-full">
                <span class="indicator-item badge badge-secondary">{{ count($invites) }}</span>
                <a href="{{ route('projects.accesses.invites', ['project' => $project]) }}" class="btn btn-primary w-full">Ver invitaciones pendientes</a>
            </div>
            @endif
            <x-card title="Invitar usuario al proyecto">
                <form action="{{ route('projects.accesses.invite', ['project' => $project]) }}" method="post">
                    @csrf
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text">Nombre</span>
                        </label>
                        <input type="name" class="input input-bordered w-full" name="name" />
                    </div>
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text">Correo electrónico</span>
                        </label>
                        <input type="email" class="input input-bordered w-full" name="email" />
                    </div>
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text">Permisos</span>
                        </label>
                        <select name="capability_id" class="select select-bordered w-full">
                            <option value="0" selected disabled hidden>Selecciona un permiso</option>
                            @foreach($capabilities as $capability)
                            <option value="{{ $capability->id }}">{{ $capability->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mt-4 w-full">
                        <button type="submit" class="btn btn-primary w-full">Invitar</button>
                    </div>
                </form>
            </x-card>
        </div>
        <x-card class="lg:col-span-2" title="Usuarios con acceso al proyecto">
            @if(count($users) > 1)
            <table class="table table-compact w-full">
                <thead>
                    <tr>
                        <td>Nombre</td>
                        <td class="hidden lg:table-cell">Correo electrónico</td>
                        <td>Categoría</td>
                        <td>Acciones</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td class="hidden lg:table-cell">{{ $user->email }}</td>
                        <td>{{ $project->accesses->where('user_id', $user->id)->first()->capability->name }}</td>
                        <td>
                            <form action="{{ route('projects.accesses.revoke', ['project' => $project, 'user' => $user]) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-xs" type="submit" @if($user->id == Auth::user()->id) disabled @endif>
                                    <i class="fa-solid fa-trash lg:mr-2"></i><span class="hidden lg:inline">Revocar</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="alert shadow-lg">
                <div>
                    <i class="fa-solid fa-circle-info"></i>
                    <span>No hay más usuarios con acceso a este proyecto.</span>
                </div>
            </div>
            @endif
        </x-card>
    </div>
</x-app-layout>