<x-app-layout>
    <x-slot name="action">
        <a class="btn btn-ghost btn-circle" href="{{ route('data.index') }}">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    </x-slot>
    <x-slot name="header">Preparar reporte para ethnobotanyR</x-slot>
    <x-slot name="subtitle">{{ $project->name }}</x-slot>
    <div class="grid grid-cols-1 gap-4 w-full">
        @foreach($forms as $form)
        <x-card title="{{$form->name}}">
            <form action="{{ route('data.ethnobotanyR', ['project' => $project])}}" method="post">
                @csrf
                <input type="hidden" name="form_id" value="{{ $form->id }}">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Selecciona el campo que contiene el uso</span>
                    </label>
                    <select name="field_id" class="select select-bordered w-full">
                        @foreach($form->sections as $section)
                        @foreach($section->items as $item)
                        <option value="{{ $item->id }}">({{ $section->name }}) {{ $item->label}}</option>
                        @endforeach
                        @endforeach
                    </select>
                </div>
                <div class="pt-4">
                    <button type="submit" class="btn btn-primary">Generar reporte</button>
                </div>
            </form>
        </x-card>
        @endforeach
    </div>
</x-app-layout>