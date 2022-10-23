<x-app-layout>
    <x-slot name="action">
        <a class="btn btn-ghost btn-circle" href="{{ route('designer.form.edit', ['project' => $project, 'form' => $form]) }}">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    </x-slot>
    <x-slot name="header">Vista previa del formulario</x-slot>
    <x-slot name="subtitle">{{ $form->name }} (en {{ $project->name }})</x-slot>

    <div class="grid grid-cols-1 gap-4 w-full">
        @foreach($form->sections as $section)
        <div class="bg-base-100 rounded-lg py-4 px-8">
            <div class="grid grid-cols-1 gap-4 w-full">
                @foreach($section->items as $item)
                @if($item->type == 'text')
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">{{ $item->label ?? '' }}@if($item->required) <span class="text-red-500">*</span>@endif</span>
                    </label>
                    <input type="text" class="input input-bordered w-full" name="{{ $item->name ?? '' }}" value="" />
                </div>
                @elseif($item->type == 'number')
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">{{ $item->label ?? '' }}@if($item->required) <span class="text-red-500">*</span>@endif</span>
                    </label>
                    <input type="number" class="input input-bordered w-full" name="{{ $item->name ?? '' }}" value="" min="{{ $item->min ?? '0' }}" max="{{ $item->max ?? '999' }}" step="{{ $item->step ?? '1' }}" />
                </div>
                @elseif($item->type == 'date')
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">{{ $item->label ?? '' }}@if($item->required) <span class="text-red-500">*</span>@endif</span>
                    </label>
                    <input type="date" class="input input-bordered w-full" name="{{ $item->name ?? '' }}" />
                </div>
                @elseif($item->type == 'select')
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">{{ $item->label ?? '' }}@if($item->required) <span class="text-red-500">*</span>@endif</span>
                    </label>
                    <select class="select select-bordered w-full" name="{{ $item->name ?? '' }}">
                        @foreach(json_decode($item->options) as $option)
                        <option value="{{ $option ?? '' }}">{{ $option ?? '' }}</option>
                        @endforeach
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</x-app-layout>