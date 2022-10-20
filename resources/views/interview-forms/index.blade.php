<x-app-layout>
    <x-slot name="header">Diseñador de entrevistas</x-slot>

    @if(count($projects) > 0)
    <div class="grid grid-cols-1 gap-4 w-full">
        @foreach($projects as $project)
        <x-card title="{{ $project->name }}">
            @if(count($project->interviewForms) > 0)
            @else
            <div class="alert shadow-lg">
                <div>
                    <i class="fa-solid fa-circle-info"></i>
                    <span>No se han diseñado entrevistas para este proyecto aún.</span>
                </div>
            </div>
            @endif
            <a href="{{ route('designer.create', $project)}}" class="btn btn-primary w-full mt-4">Crear</a>
        </x-card>
        @endforeach
    </div>
    @endif
</x-app-layout>
