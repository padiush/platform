<table>
    <tr>
        <td>informant</td>
        <td>sp_name</td>
        @foreach($categories as $category)
        <td>{{ str_replace(" ", "_", strtolower($category->answer)) }}</td>
        @endforeach
    </tr>
    @foreach($answers as $answer)
    <tr>
        <td>PADIUSH_INST_{{ $answer->interview_instance_id }}</td>
        <td>{{ $answer->species->genus }} {{ $answer->species->name }}</td>
        @foreach($categories as $category)
        <td>@if($answer->category == $category->answer) 1 @else 0 @endif</td>
        @endforeach
    </tr>
    @endforeach
</table>