<x-app-layout>
    <x-slot name="header">Catálogos etnobotánicos</x-slot>

    @if(count($projects) > 0)
    <div class="grid grid-cols-1 gap-4 w-full">
        @foreach($projects as $project)
        <x-card title="{{ $project->name }}">
            <div class="stats stats-vertical lg:stats-horizontal bg-base-200">
                <div class="stat">
                    <div class="stat-value">{{ count($project->catalogSpecies )}}</div>
                    <div class="stat-title">Especies en el catálogo</div>
                </div>
                <div class="stat">
                    <div class="stat-value">0</div>
                    <div class="stat-title">Especies vinculadas</div>
                </div>
                <div class="stat">
                    <div class="stat-value">0</div>
                    <div class="stat-title">Familias vinculadas</div>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 w-full mt-4">
                <a href="{{ route('catalogs.species.register', ['project' => $project]) }}" class="btn btn-primary w-full" @if(!Auth::user()->hasCapabilityOnProject($project, 'edit_catalog')) disabled @endif>Registrar especie</a>
                <a href="{{ route('catalogs.show', ['project' => $project])}}" class="btn btn-primary w-full" @if(!Auth::user()->hasCapabilityOnProject($project, 'view_catalog') || !count($project->catalogSpecies)) disabled @endif>Ver catálogo</a>
            </div>
        </x-card>
        @endforeach
    </div>
    @endif
</x-app-layout>
