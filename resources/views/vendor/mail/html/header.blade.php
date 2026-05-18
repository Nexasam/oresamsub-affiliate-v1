{{-- @props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">
@else
{{ $slot }}
@endif
</a>
</td>
</tr> --}}

@props(['url'])

<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">

{{ session('affiliate')->name ?? config('app.name') }}

</a>
</td>
</tr>