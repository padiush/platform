<x-card title="{{ $section->name ?? '' }}" id="section-{{ $section->id }}-instance-{{ $i }}">
            <div class="grid grid-cols-1 gap-4 w-full">
                @foreach($section->items as $item)
                @if($item->type == 'text')
                <div class="form-control w-full" id="{{ $item->id }}">
                    <label class="label">
                        <span class="label-text">{{ $item->label ?? '' }}@if($item->required) <span class="text-red-500">*</span>@endif</span>
                    </label>
                    <input type="text" class="input input-bordered w-full answer-repeatable" name="{{ $item->name ?? '' }}" value="{{ $answers->where('interview_item_id', $item->id)->first()->answer ?? '' }}" id="answer-repeatable-{{ $item->id }}-{{ $i }}"/>
                </div>
                @elseif($item->type == 'number')
                <div class="form-control w-full" id="{{ $item->id }}">
                    <label class="label">
                        <span class="label-text">{{ $item->label ?? '' }}@if($item->required) <span class="text-red-500">*</span>@endif</span>
                    </label>
                    <input type="number" class="input input-bordered w-full answer-repeatable" name="{{ $item->name ?? '' }}" value="{{ $answers->where('interview_item_id', $item->id)->first()->answer ?? '' }}" id="answer-repeatable-{{ $item->id }}-{{ $i }}" min="{{ $item->min ?? '0' }}" max="{{ $item->max ?? '999' }}" step="{{ $item->step ?? '1' }}" />
                </div>
                @elseif($item->type == 'date')
                <div class="form-control w-full" id="{{ $item->id }}">
                    <label class="label">
                        <span class="label-text">{{ $item->label ?? '' }}@if($item->required) <span class="text-red-500">*</span>@endif</span>
                    </label>
                    <input type="date" class="input input-bordered w-full answer-repeatable" name="{{ $item->name ?? '' }}" value="{{ $answers->where('interview_item_id', $item->id)->first()->answer ?? '' }}" id="answer-repeatable-{{ $item->id }}-{{ $i }}" />
                </div>
                @elseif($item->type == 'select')
                <div class="form-control w-full" id="{{ $item->id }}">
                    <label class="label">
                        <span class="label-text">{{ $item->label ?? '' }}@if($item->required) <span class="text-red-500">*</span>@endif</span>
                    </label>
                    <select class="select select-bordered w-full answer-repeatable" name="{{ $item->name ?? '' }}" id="answer-repeatable-{{ $item->id }}-{{ $i }}">
                        @foreach(json_decode($item->options) as $option)
                        <option value="{{ $option ?? '' }}" @if($answers->where('interview_item_id', $item->id)->first()) @if($answers->where('interview_item_id', $item->id)->first()->answer == $option) selected @endif @endif">{{ $option ?? '' }}</option>
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