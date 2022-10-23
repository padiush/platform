<x-app-layout>
    <x-slot name="action">
        <a class="btn btn-ghost btn-circle" href="{{ route('interviews.index') }}">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    </x-slot>
    <x-slot name="header">Entrevista {{ $instance->id }}</x-slot>
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
                    <input type="text" class="input input-bordered w-full answer-single" name="{{ $item->name ?? '' }}" value="" id="answer-{{ $item->id }}" />
                </div>
                @elseif($item->type == 'number')
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">{{ $item->label ?? '' }}@if($item->required) <span class="text-red-500">*</span>@endif</span>
                    </label>
                    <input type="number" class="input input-bordered w-full answer-single" name="{{ $item->name ?? '' }}" value="" min="{{ $item->min ?? '0' }}" max="{{ $item->max ?? '999' }}" step="{{ $item->step ?? '1' }}" id="answer-{{ $item->id }}" />
                </div>
                @elseif($item->type == 'date')
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">{{ $item->label ?? '' }}@if($item->required) <span class="text-red-500">*</span>@endif</span>
                    </label>
                    <input type="date" class="input input-bordered w-full answer-single" name="{{ $item->name ?? '' }}" id="answer-{{ $item->id }}" />
                </div>
                @elseif($item->type == 'select')
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">{{ $item->label ?? '' }}@if($item->required) <span class="text-red-500">*</span>@endif</span>
                    </label>
                    <select class="select select-bordered w-full answer-single" name="{{ $item->name ?? '' }}" id="answer-{{ $item->id }}">
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
                                <input type="checkbox" class="checkbox checkbox-primary answer-single-multi answer-single-multi-{{ $item->id }}" name="{{ $item->name ?? '' }}" value="{{ $option ?? '' }}" id="answer-{{ $item->id }}-{{ Crypt::encryptString($option) }}" />
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
        <div id="section-{{ $section->id }}-instances" class="grid grid-cols-1 gap-4 w-full">
        </div>
        <button class="btn btn-primary w-full" onclick="createNewRepeatingSectionInstance({{ $section->id }}, '{{ route('interviews.answer', ['instance' => $instance->id]) }}', '{{ csrf_token() }}')">
            <i class="fa-solid fa-plus"></i>
            <span class="ml-2"> {{ $section->name ?? '' }}</span>
        </button>
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
                <div class="form-control w-full" id="{{ $item->id }}">
                    <label class="label">
                        <span class="label-text">{{ $item->label ?? '' }}@if($item->required) <span class="text-red-500">*</span>@endif</span>
                    </label>
                    <input type="text" class="input input-bordered w-full answer-repeatable" name="{{ $item->name ?? '' }}" value="" />
                </div>
                @elseif($item->type == 'number')
                <div class="form-control w-full" id="{{ $item->id }}">
                    <label class="label">
                        <span class="label-text">{{ $item->label ?? '' }}@if($item->required) <span class="text-red-500">*</span>@endif</span>
                    </label>
                    <input type="number" class="input input-bordered w-full answer-repeatable" name="{{ $item->name ?? '' }}" value="" min="{{ $item->min ?? '0' }}" max="{{ $item->max ?? '999' }}" step="{{ $item->step ?? '1' }}" />
                </div>
                @elseif($item->type == 'date')
                <div class="form-control w-full" id="{{ $item->id }}">
                    <label class="label">
                        <span class="label-text">{{ $item->label ?? '' }}@if($item->required) <span class="text-red-500">*</span>@endif</span>
                    </label>
                    <input type="date" class="input input-bordered w-full answer-repeatable" name="{{ $item->name ?? '' }}" />
                </div>
                @elseif($item->type == 'select')
                <div class="form-control w-full" id="{{ $item->id }}">
                    <label class="label">
                        <span class="label-text">{{ $item->label ?? '' }}@if($item->required) <span class="text-red-500">*</span>@endif</span>
                    </label>
                    <select class="select select-bordered w-full answer-repeatable" name="{{ $item->name ?? '' }}">
                        @foreach(json_decode($item->options) as $option)
                        <option value="{{ $option ?? '' }}">{{ $option ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
                @elseif($item->type == 'multi')
                <div class="form-control w-full" id="{{ $item->id }}">
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

    <script type="text/javascript">
        $(document).ready(function() {
            var storeAnswerRoute = '{{ route('interviews.answer', ['instance' => $instance->id]) }}';
            var getAnswerRoute = '{{ route('interviews.answer.get', ['instance' => $instance->id]) }}';
            var csrfToken = '{{ csrf_token() }}';

            var answerSingleElements = document.getElementsByClassName("answer-single");

            for (var i = 0; i < answerSingleElements.length; i++) {
                var element = answerSingleElements[i];
                var item_id = element.getAttribute('id').replace('answer-', '');

                setAnswerToSingleField(getAnswerRoute, csrfToken, item_id, element);
            }

            var answerSingleMultiElements = document.getElementsByClassName("answer-single-multi");

            var answerSingleMultiElementsGrouped = {};

            for (var i = 0; i < answerSingleMultiElements.length; i++) {
                var element = answerSingleMultiElements[i];
                var item_id = element.getAttribute('id').replace('answer-', '').split('-')[0];

                if (answerSingleMultiElementsGrouped[item_id] == undefined) {
                    answerSingleMultiElementsGrouped[item_id] = [];
                }

                answerSingleMultiElementsGrouped[item_id].push(element);
            }

            for (var item_id in answerSingleMultiElementsGrouped) {
                var elements = answerSingleMultiElementsGrouped[item_id];

                setAnswerToSingleMultiField(getAnswerRoute, csrfToken, item_id, elements);
            }

            $('.answer-single').change(function() {
                var value = $(this).val();
                var id = $(this).attr('id');

                var item_id = id.replace('answer-', '');

                var answer = {
                    'item_id': item_id,
                    'repeatable_index': null,
                    'answer': value
                };

                var answerJSON = JSON.stringify(answer);

                storeAnswer(storeAnswerRoute, answerJSON, csrfToken);
            });

            $('.answer-single-multi').change(function(){
                var checked = $(this).is(':checked');
                var id = $(this).attr('id');

                var item_id = id.replace('answer-', '').replace(/-[^-]*$/, '');
                var allOptions = document.getElementsByClassName('answer-single-multi-' + item_id);

                var checkedOptions = [];

                for (var i = 0; i < allOptions.length; i++) {
                    var option = allOptions[i];

                    if (option.checked) {
                        checkedOptions.push(option.value);
                    }
                }

                var answer = {
                    'item_id': item_id,
                    'repeatable_index': null,
                    'answer': JSON.stringify(checkedOptions)
                };

                var answerJSON = JSON.stringify(answer);

                storeAnswer(storeAnswerRoute, answerJSON, csrfToken);
            });
        });
    </script>
</x-app-layout>