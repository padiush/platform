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
                    <div class="form-control w-full lg:col-span-2">
                    <label class="label">
                        <span class="label-text">{{ $item->label ?? '' }}@if($item->required) <span class="text-red-500">*</span>@endif</span>
                    </label>
                    <input type="text" class="input input-bordered w-full" name="name" value="" />
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    @endforeach
    </div>
</x-app-layout>