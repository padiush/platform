<x-app-layout>
    <x-slot name="action">
        <a class="btn btn-ghost btn-circle" href="{{ route('designer.index') }}">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    </x-slot>
    <x-slot name="header">Nuevo formulario de entrevista</x-slot>
    <x-slot name="subtitle">{{ $project->name }}</x-slot>
</x-app-layout>