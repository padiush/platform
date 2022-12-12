<x-app-layout>
    <x-slot name="action">
        <a class="btn btn-ghost btn-circle" href="{{ route('data.index') }}">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    </x-slot>
    <x-slot name="header">Vincular especies</x-slot>
    <x-slot name="subtitle">{{ $project->name }}</x-slot>
    <div class="grid grid-cols-1 gap-4 w-full">
        @foreach($answered_sections as $section)
        <div id="div-{{ $section->interview_instance_id}}-{{ $section->section->id }}@if($section->repeatable)-{{$section->repeatable_index}}@endif" class="animate__animated animate__zoomIn">
            <x-card title="{{ $section->section->name }}">
                @foreach($section->items as $item)
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">{{ $item->label }}</span>
                    </label>
                    <input type="text" class="input input-bordered input-sm" value="{{ $section->answers->where('interview_item_id', $item->id)->first()->answer }}" disabled>
                </div>
                @endforeach
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Vincular a la especie</span>
                    </label>
                    <select name="" id="{{ $section->interview_instance_id}}-{{ $section->section->id }}@if($section->repeatable)-{{$section->repeatable_index}}@endif" class="select select-bordered w=full">
                        <option value="" selected disabled hidden>Seleccione una especie</option>
                        @foreach($species as $specie)
                        <option value="{{ $specie->id }}">Fam. {{ $specie->family }} - <span class="italic">{{ $specie->genus . " " . $specie->name }}</span> {{ $specie->authority }}</option>
                        @endforeach
                    </select>
                </div>
            </x-card>
        </div>
        @endforeach
    </div>
    <script type="text/javascript">
        $(document).ready(function() {
            var linkSpeciesRoute = "{{ route('data.link', ['project' => $project]) }}";
            var csrfToken = "{{ csrf_token() }}";

            @foreach($answered_sections as $section)
            $('#{{ $section->interview_instance_id }}-{{ $section->section->id }}@if($section->repeatable)-{{$section->repeatable_index}}@endif').change(function() {
                linkAnswerToSpecies(linkSpeciesRoute, csrfToken, '{{ $section->interview_instance_id }}', '{{ $section->section->id }}', @if($section->repeatable)'{{ $section->repeatable_index }}'@else null @endif, $(this).val())
            });
            @endforeach
        });
    </script>
</x-app-layout>