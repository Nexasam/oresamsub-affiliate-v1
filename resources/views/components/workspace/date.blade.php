@props(['value', 'format' => 'd M Y, H:i', 'fallback' => '—'])
@php
    try {
        $displayDate = blank($value)
            ? $fallback
            : ($value instanceof \DateTimeInterface
                ? \Illuminate\Support\Carbon::instance($value)->format($format)
                : \Illuminate\Support\Carbon::parse((string) $value)->format($format));
    } catch (\Throwable) {
        $displayDate = filled($value) ? (string) $value : $fallback;
    }
@endphp
{{ $displayDate }}
