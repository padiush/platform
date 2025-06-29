<table>
    <tr>
        <th>Entrevista</th>
        @foreach($items as $item)
        <th>{{ $item->label }}</th>
        @endforeach
    </tr>
    @foreach($instances as $instance)
        @if($repeatable)
            @for($i = 0; $i <= $instance->max_repeatable_index; $i++)
            <tr>
                <td>PADIUSH_INST_{{ $instance->id }}</td>
                @foreach($items as $item)
                    @foreach($item->answers as $answer)
                        @if($answer->interview_item_id == $item->id && $answer->interview_instance_id == $instance->id && $answer->repeatable_index == ($i + 1))
                            <td>{{ $answer->answer }}</td>
                        @endif
                    @endforeach
                @endforeach
            </tr>
            @endfor
        @else
            <tr>
                <td>PADIUSH_INST_{{ $instance->id }}</td>
                @foreach($items as $item)
                    @foreach($item->answers as $answer)
                        @if($answer->interview_item_id == $item->id && $answer->interview_instance_id == $instance->id)
                            <td>{{ $answer->answer }}</td>
                        @endif
                    @endforeach
                @endforeach
            </tr>
        @endif
    @endforeach
</table>