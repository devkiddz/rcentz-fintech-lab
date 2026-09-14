@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if(site_logo())
<img src="{{ url(site_logo()) }}" class="logo" alt="{{ site_name() }}" style="height: 40px; width: auto;">
@else
<span style="font-size: 24px; font-weight: bold; color: #000;">{{ site_name() }}</span>
@endif
</a>
</td>
</tr>
