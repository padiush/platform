<x-app-layout>
    <x-slot name="header">Diseñador de entrevistas</x-slot>

    @if(count($projects) > 0)
    <div class="grid grid-cols-1 gap-4 w-full">
        @foreach($projects as $project)
        <x-card title="{{ $project->name }}">
            @if(count($project->interviewForms) > 0)
            <table class="table table-compact table-fixed w-full">
                <thead>
                    <tr>
                        <th style="width: 125px;" class="text-center">¿Habilitado?</th>
                        <th class="hidden lg:table-cell">Nombre del formulario</th>
                        <th class="table-cell lg:hidden">Nombre</th>
                        <th class="hidden lg:table-cell">Entrevistas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->interviewForms as $form)
                    <tr>
                        <td class="text-center">@if($form->is_active)<i class="fa-solid fa-check"></i>@else<i class="fa-solid fa-times"></i>@endif</td>
                        <td class="text-wrap">{{ $form->name }}</td>
                        <td class="hidden lg:table-cell">0</td>
                        <td>
                            <a class="btn btn-primary btn-xs" href="{{ route('designer.form.edit', ['project' => $project, 'form' => $form]) }} ">
                                Editar
                            </a>
                            <br>
                            <a class="btn btn-primary btn-xs mt-2" href="{{ route('designer.form.toggle', ['project' => $project, 'form' => $form]) }} ">
                                @if($form->is_active)Deshabilitar @else Habilitar @endif
                            </a>
                            <br>
                            <form action="{{ route('designer.form.delete', ['project' => $project, 'form' => $form]) }}" method="post" class="inline-block">
                                @csrf
                                @method('delete')
                                <button type="submit" class="btn btn-error btn-xs mt-2" onclick="return confirmAction('¿Realmente deseas realizar esta acción?\n\nToma en cuenta que se eliminarán todas las entrevistas realizadas con este formulario.\n\nEsto no pude deshacerse.')">Eliminar</button>
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
