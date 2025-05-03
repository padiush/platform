<x-app-layout>
    <x-slot name="action">
        <a class="btn btn-ghost btn-circle" href="{{ route('projects.index') }}">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    </x-slot>

    <x-slot name="header">@isset($project) Editar detalles del proyecto @else Crear proyecto @endisset</x-slot>
    <x-slot name="subtitle">@isset($project){{ $project->name }}@endisset</x-slot>

    <x-card title="Detalles del proyecto">
        <form action="@isset($project){{ route('projects.edit', ['project' => $project])}}@else{{ route('projects.create')}}@endif" method="post">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 w-full">
                <fieldset class="fieldset w-full lg:col-span-2">
                    <legend class="fieldset-legend">
                        Nombre del proyecto <span class="text-error">*</span>
                    </legend>
                    <input type="text" class="input input-bordered w-full" name="name" value="@isset($project){{$project->name}}@endisset" />
                </fieldset>
                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">
                        Autoría
                    </legend>
                    <input type="text" class="input input-bordered w-full" name="author" value="@isset($project){{$project->author}}@else{{Auth::user()->name}}@endisset" />
                </fieldset>
                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">
                        Institución
                    </legend>
                    <input type="text" class="input input-bordered w-full" name="institution" value="@isset($project){{$project->institution}}@endisset" />
                </fieldset>
                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">
                        Correo electrónico del autor
                    </legend>
                    <input type="email" class="input input-bordered w-full" name="author_email" value="@isset($project){{$project->author_email}}@else{{Auth::user()->email}}@endisset" />
                </fieldset>
                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">
                        País
                    </legend>
                    <input type="text" class="input input-bordered w-full" name="country" value="@isset($project){{$project->country}}@endisset" />
                </fieldset>
            </div>
            <div class="mt-4 w-full">
                <a href="{{ route('projects.index') }}" class="btn btn-outline">Cancelar</a>
                <button type="submit" class="btn btn-primary">@isset($project) Actualizar @else Crear @endisset</button>
            </div>
        </form>
    </x-card>
</x-app-layout>