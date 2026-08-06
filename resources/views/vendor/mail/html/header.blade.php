@props(['url'])
{{-- Upstream swaps in a hosted Laravel logo when the app name is "Laravel".
     A wordmark is used instead of any image: email clients block remote
     content by default, so a logo would be a blank gap for many recipients. --}}
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
{!! $slot !!}
</a>
</td>
</tr>
