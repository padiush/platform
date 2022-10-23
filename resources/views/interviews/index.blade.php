<x-app-layout>
    <x-slot name="header">Entrevistas</x-slot>

    @if(count($projects) > 0)
    <div class="grid grid-cols-1 gap-4 w-full">
        @foreach($projects as $project)
        <x-card title="{{ $project->name }}">
            @if(count($project->activeInterviewForms) > 0)
            <table class="table table-compact table-fixed w-full">
                <thead>
                    <tr>
                        <th class="hidden lg:table-cell">Nombre del formulario</th>
                        <th class="table-cell lg:hidden">Nombre</th>
                        <th class="hidden lg:table-cell">Entrevistas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->activeInterviewForms as $form)
                    <tr>
                        <td class="text-wrap">{{ $form->name }}</td>
                        <td class="hidden lg:table-cell">0</td>
                        <td>
                            <a href="{{ route('interviews.create', $form) }}" class="btn btn-xs btn-primary">Entrevistar</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="alert shadow-lg">
                <div>
                    <i class="fa-solid fa-circle-info"></i>
                    <span>No hay formularios de entrevista activos para este proyecto.</span>
                </div>
            </div>
            @endif
            <a href="{{ route('designer.create', $project)}}" class="btn btn-primary w-full mt-4">Crear</a>
        </x-card>
        @endforeach
    </div>
    @endif
</x-app-layout>
