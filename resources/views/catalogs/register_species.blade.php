<x-app-layout>
    <x-slot name="action">
        <a class="btn btn-ghost btn-circle" href="{{ route('catalogs.index') }}">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    </x-slot>
    <x-slot name="header">Registrar especie en el catálogo</x-slot>
    <x-slot name="subtitle">Catálogo del proyecto "{{ $project->name }}"</x-slot>

    <x-card title="Información de la especie">
        <form action="{{ route('catalogs.species.register', ['project' => $project]) }}" method="post">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 w-full">
                <div class="form-control lg:col-span-3">
                    <label class="label">
                        <span class="label-text">Familia <span class="text-red-500">*</span></span>
                    </label>
                    <input type="text" name="family" class="input input-bordered w-full">
                </div>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Género <span class="text-red-500">*</span></span>
                    </label>
                    <input type="text" name="genus" class="input input-bordered w-full">
                </div>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Especie <span class="text-red-500">*</span></span>
                    </label>
                    <input type="text" name="name" class="input input-bordered w-full">
                </div>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Abreviatura de la autoridad <span class="text-red-500">*</span></span>
                    </label>
                    <input type="text" name="authority" class="input input-bordered w-full">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary w-full">Registrar especie</button>
            </div>
        </form>
    </x-card>
</x-app-layout>