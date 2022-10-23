<x-app-layout>
    <x-slot name="action">
        <a class="btn btn-ghost btn-circle" href="{{ route('designer.index') }}">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    </x-slot>
    <x-slot name="header">Diseñando formulario</x-slot>
    <x-slot name="subtitle">{{ $form->name }} (en {{ $project->name }})</x-slot>
    <x-slot name="action_right">
        <a class="btn btn-primary btn-circle" href="{{ route('designer.form.preview', ['project' => $project, 'form' => $form]) }}">
            <i class="fa-solid fa-eye"></i>
        </a>
    </x-slot>

    <div class="overflow-hidden">
        <div id="form-designer" class="grid grid-cols-1 gap-4 w-full pb-8">
        </div>
    </div>

    <x-slot name="bottom_bar">
        <button id="singleSection">
            <span><i class="fa-regular fa-clipboard"></i><i class="fa-regular fa-circle-1 ml-2"></i></span><span class="hidden lg:inline-block text-sm">Sección única</span>
        </button>
        <button id="repeatingSection">
            <span><i class="fa-regular fa-clipboard"></i><i class="fa-regular fa-repeat ml-2"></i></span><span class="hidden lg:inline-block text-sm">Sección repetitiva</span>
        </button>
        <button id="textInput">
            <span><i class="fa-solid fa-plus"></i><i class="fa-regular fa-input-text ml-2"></i></span><span class="hidden lg:inline-block text-sm">Campo de texto</span>
        </button>
        <button id="numberInput">
            <span><i class="fa-solid fa-plus"></i><i class="fa-regular fa-input-numeric ml-2"></i></span><span class="hidden lg:inline-block text-sm">Campo numérico</span>
        </button>
        <button id="dateInput">
            <span><i class="fa-solid fa-plus"></i><i class="fa-regular fa-calendar-days ml-2"></i></span><span class="hidden lg:inline-block text-sm">Campo de fecha</span>
        </button>
        <button id="singleSelect">
            <span><i class="fa-solid fa-plus"></i><i class="fa-regular fa-list-dropdown ml-2"></i></span><span class="hidden lg:inline-block text-sm">Selección única</span>
        </button>
        <button id="multiSelect">
            <span><i class="fa-solid fa-plus"></i><i class="fa-regular fa-list-check ml-2"></i></span><span class="hidden lg:inline-block text-sm">Selección múltiple</span>
        </button>
    </x-slot>

    <script type="text/javascript">
        $(document).ready(function(){
            var csrfToken = '{{ csrf_token() }}';
            var sections = @json($form->sections);
            var getItemsRoute = '{{ route('designer.section.items', ['form' => $form]) }}';

            var getItemRoute = '{{ route('designer.item.data', ['form' => $form]) }}';
            var updateItemRoute = '{{ route('designer.item.update', ['form' => $form]) }}';
            startFormDesigner(csrfToken, sections, getItemsRoute, getItemRoute, updateItemRoute);

            $('#singleSection').click(function(){
                var route = '{{ route('designer.section.create', ['form' => $form]) }}';
                pushSingleSection(route, csrfToken);
            });

            $('#repeatingSection').click(function(){
                var route = '{{ route('designer.section.create', ['form' => $form]) }}';
                pushRepeatingSection(route, csrfToken);
            });

            var createRoute = '{{ route('designer.item.create', ['form' => $form]) }}';

            $('#textInput').click(function(){
                pushTextInput(createRoute, getItemRoute, updateItemRoute, csrfToken);
            });

            $('#numberInput').click(function(){
                pushNumberInput(createRoute, getItemRoute, updateItemRoute, csrfToken);
            });

            $('#dateInput').click(function(){
                pushDateInput(createRoute, getItemRoute, updateItemRoute, csrfToken);
            });

            $('#singleSelect').click(function(){
                pushSingleSelect(createRoute, getItemRoute, updateItemRoute, csrfToken);
            });

            $('#multiSelect').click(function(){
                pushMultiSelect(createRoute, getItemRoute, updateItemRoute, csrfToken);
            });
        });

    </script>
</x-app-layout>