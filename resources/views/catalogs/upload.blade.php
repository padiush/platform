<x-app-layout>
    <x-slot name="action">
        <a class="btn btn-ghost btn-circle" href="{{ route('catalogs.index') }}">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    </x-slot>
    <x-slot name="header">Cargar catálogo desde Excel</x-slot>
    <x-slot name="subtitle">{{ $project->name }}</x-slot>

    <div class="grid grid-cols-1 gap-4 w-full">
        <x-card>
            <h2 class="card-title">Cargar archivo</h2>
            <div>
                El archivo debe contener las siguientes columnas en orden, comenzando en la columna A:
                <ul class="list-decimal list-inside">
                    <li>Familia</li>
                    <li>Género</li>
                    <li>Especie</li>
                    <li>Abreviatura de autoridad</li>
                </ul>
            </div>
            <div>
                <p>Las columnas <span class="underline">no</span> deben tener encabezados.</p>
                <p>El archivo debe estar en formato .xlsx.</p>
                <p>Se agregarán las especies aunque ya existan en el catálogo, por lo que se recomienda revisar el catálogo antes de cargar el archivo.</p>
            </div>
            <form action="{{ route('catalogs.upload', ['project' => $project])}}" method="post" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" class="file-input file-input-bordered file-input-primary w-full max-w-xs" />
                <button type="submit" class="btn btn-primary mt-4">Cargar</button>
            </form>
        </x-card>
    </div>
</x-app-layout>