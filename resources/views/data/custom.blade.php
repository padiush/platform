<x-app-layout>
    <x-slot name="action">
        <a class="btn btn-ghost btn-circle" href="{{ route('data.index') }}">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    </x-slot>
    <x-slot name="header">Reporte personalizado</x-slot>
    <x-slot name="subtitle">{{ $project->name }}</x-slot>
    <div class="grid grid-cols-1 gap-4 w-full">
        @foreach($forms as $form)
        <x-card title="{{$form->name}}">
            <div>
                Por favor, selecciona los campos que deseas incluir en el reporte. Toma en cuenta que 
                <span class="underline">
                    no puedes mezclar campos que pertenecen a secciones únicas con 
                    campos que pertenecen a secciones repetibles.
                </span>
                Si lo haces, el reporte no se generará.
            </div>
            <form action="{{ route('data.custom', ['project' => $project])}}" method="post">
                @csrf
                <input type="hidden" name="form_id" value="{{ $form->id }}">
                <input type="hidden" name="selected_fields" id="selectedFieldsJson">
                @foreach($form->sections as $section)
                <h2 class="text-lg">{{ $section->name }} (Sección @if($section->repeatable) repetible) @else única) @endif</h2>
                @foreach($section->items as $item)
                <div class="form-control">
                    <label class="label cursor-pointer">
                        <span class="label-text">{{ $item->label }}</span> 
                        <input type="checkbox" class="checkbox checkbox-primary" id="item_{{ $item->id }}" />
                    </label>
                </div>
                @endforeach
                <div class="divider"></div>
                @endforeach
                <div class="pt-4">
                    <button type="submit" class="btn btn-primary">Generar reporte</button>
                </div>
            </form>
        </x-card>
        @endforeach
    </div>
    <script type="text/javascript">
        $(document).ready(function() {
            var selectedFields = [];
            $('.checkbox').change(function() {
                if($(this).is(':checked')) {
                    selectedFields.push($(this).attr('id').replace('item_', ''));
                } else {
                    selectedFields.splice(selectedFields.indexOf($(this).attr('id').replace('item_', '')), 1);
                }
                selectedFields.sort(function(a, b) {
                    return a - b;
                });
                $('#selectedFieldsJson').val(JSON.stringify(selectedFields));
            });
        });
    </script>
</x-app-layout>