<x-app-layout>
    <x-slot name="action">
        <a class="btn btn-ghost btn-circle" href="{{ route('designer.index') }}">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    </x-slot>
    <x-slot name="header">Diseñando formulario</x-slot>
    <x-slot name="subtitle">{{ $form->name }} (en {{ $project->name }})</x-slot>
    <x-slot name="action_right">
        <a class="btn btn-primary btn-circle" href="{{ route('designer.index') }}">
            <i class="fa-solid fa-eye"></i>
        </a>
    </x-slot>

    <div class="overflow-hidden">
        <div id="form-designer" class="grid grid-cols-1 gap-4 w-full pb-8">
        </div>
    </div>

    <script type="text/javascript">
        $(document).ready(function(){
            var csrfToken = '{{ csrf_token() }}';
            startFormDesigner(csrfToken);
        });
    </script>
    <x-slot name="bottom_bar">
        <button onclick="pushSingleSection()">
            <span><i class="fa-regular fa-clipboard"></i><i class="fa-regular fa-circle-1 ml-2"></i></span><span class="hidden lg:inline-block text-sm">Sección única</span>
        </button>
        <button onclick="pushRepeatingSection()">
            <span><i class="fa-regular fa-clipboard"></i><i class="fa-regular fa-repeat ml-2"></i></span><span class="hidden lg:inline-block text-sm">Sección repetitiva</span>
        </button>
        <button onclick="pushTextInput()">
            <span><i class="fa-solid fa-plus"></i><i class="fa-regular fa-input-text ml-2"></i></span><span class="hidden lg:inline-block text-sm">Campo de texto</span>
        </button>
        <button onclick="pushNumberInput()">
            <span><i class="fa-solid fa-plus"></i><i class="fa-regular fa-input-numeric ml-2"></i></span><span class="hidden lg:inline-block text-sm">Campo numérico</span>
        </button>
        <button onclick="pushDateInput()">
            <span><i class="fa-solid fa-plus"></i><i class="fa-regular fa-calendar-days ml-2"></i></span><span class="hidden lg:inline-block text-sm">Campo de fecha</span>
        </button>
        <button onclick="pushSelect()">
            <span><i class="fa-solid fa-plus"></i><i class="fa-regular fa-list-dropdown ml-2"></i></span><span class="hidden lg:inline-block text-sm">Selección única</span>
        </button>
        <button onclick="pushMultiSelect()">
            <span><i class="fa-solid fa-plus"></i><i class="fa-regular fa-list-check ml-2"></i></span><span class="hidden lg:inline-block text-sm">Selección múltiple</span>
        </button>
    </x-slot>
</x-app-layout>