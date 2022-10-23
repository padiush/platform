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
        @if(!$section->repeatable)
        <x-card title="{{ $section->name ?? '' }}">
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
                    </select>
                </div>
                @elseif($item->type == 'multi')
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">{{ $item->label ?? '' }}@if($item->required) <span class="text-red-500">*</span>@endif</span>
                    </label>
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 w-full">
                        @foreach(json_decode($item->options) as $option)
                        <div class="w-full">
                            <label class="cursor-pointer select-none">
                                <input type="checkbox" class="checkbox checkbox-primary" name="{{ $item->name ?? '' }}" value="{{ $option ?? '' }}" />
                                <span class="ml-2">{{ $option ?? '' }}</span>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                @endforeach
            </div>
        </x-card>
        @else
        <button class="btn btn-primary w-full" onclick="createNewRepeatingSectionInstance({{ $section->id }})">
            <i class="fa-solid fa-plus"></i>
            <span class="ml-2"> {{ $section->name ?? '' }}</span>
        </button>
        <div id="section-{{ $section->id }}-instances" class="grid grid-cols-1 gap-4 w-full">
        </div>
        @endif
        @endforeach
    </div>

    <div class="hidden">
        @foreach($form->sections as $section)
        @if($section->repeatable)
        <x-card title="{{ $section->name ?? '' }}" id="section-{{ $section->id }}-master">
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
                    </select>
                </div>
                @elseif($item->type == 'multi')
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">{{ $item->label ?? '' }}@if($item->required) <span class="text-red-500">*</span>@endif</span>
                    </label>
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 w-full">
                        @foreach(json_decode($item->options) as $option)
                        <div class="w-full">
                            <label class="cursor-pointer select-none">
                                <input type="checkbox" class="checkbox checkbox-primary" name="{{ $item->name ?? '' }}" value="{{ $option ?? '' }}" />
                                <span class="ml-2">{{ $option ?? '' }}</span>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                @endforeach
            </div>
        </x-card>
        @endif
        @endforeach
    </div>
</x-app-layout>