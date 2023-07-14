<x-app-layout>
    <x-slot name="header">Procesamiento de datos</x-slot>

    @if(count($projects) > 0)
    <div class="grid grid-cols-1 gap-4 w-full">
        @foreach($projects as $project)
        <x-card title="{{ $project->name }}">
            <div class="stats stats-vertical lg:stats-horizontal bg-base-200">
                <div class="stat">
                    <div class="stat-value">{{ count($project->unlinkedAnswers()) }}</div>
                    <div class="stat-title">Reportes de uso sin vincular a información taxonómica</div>
                </div>
                <div class="stat">
                    <div class="stat-value">{{ count($project->linkedAnswers()) }}</div>
                    <div class="stat-title">Reportes de uso vinculados a información taxonómica</div>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 w-full mt-4">
                <a href="{{ route('data.link', ['project' => $project])}}" class="btn btn-primary w-full" @if(!Auth::user()->hasCapabilityOnProject($project, 'manage_data') || !count($project->unlinkedAnswers())) disabled @endif>Vincular especie</a>
                <a href="{{ route('data.ethnobotanyR', ['project' => $project])}}" class="btn btn-primary w-full" @if(!Auth::user()->hasCapabilityOnProject($project, 'generate_reports')) disabled @endif>Generar reporte para EthnobotanyR</a>
                <a href="{{ route('data.custom', ['project' => $project])}}" class="btn btn-primary w-full" @if(!Auth::user()->hasCapabilityOnProject($project, 'generate_reports')) disabled @endif>Exportación personalizada</a>
            </div>
        </x-card>
        @endforeach
    </div>
    @endif
</x-app-layout>
