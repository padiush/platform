<x-app-layout>
    <x-slot name="action">
        <a class="btn btn-ghost btn-circle" href="{{ route('designer.index') }}">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    </x-slot>
    <x-slot name="header">Nuevo formulario de entrevista</x-slot>
    <x-slot name="subtitle">{{ $project->name }}</x-slot>

    <x-card title="Detalles del formulario">
        <form action="{{ route('designer.create', ['project' => $project]) }}" method="post">
            @csrf
            <input type="hidden" name="project_id" value="{{ $project->id }}" />
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 w-full">
                <div class="form-control w-full lg:col-span-2">
                    <label class="label">
                        <span class="label-text">Título del formulario <span class="text-red-500">*</span></span>
                    </label>
                    <input type="text" class="input input-bordered w-full" name="name" value="" />
                </div>
                <div class="form-control w-full lg:col-span-2">
                    <label class="label">
                        <span class="label-text">Descripción</span>
                    </label>
                    <textarea class="textarea h-24 textarea-bordered w-full" name="description"></textarea>
                </div>
            </div>
            <div class="mt-4 w-full">
                <a href="{{ route('designer.index') }}" class="btn btn-outline">Cancelar</a>
                <button type="submit" class="btn btn-primary">Crear</button>
            </div>
        </form>
    </x-card>
</x-app-layout>