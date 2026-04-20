@props([
    'type' => 'reservation',
    'value' => null,
    'label' => null,
    'pill' => true,
])

@php
    $resolvedLabel = $label ?? \App\Support\StatusBadge::label($value);
    $palette = \App\Support\StatusBadge::palette($type, $value);
    $style = sprintf(
        'background-color:%s !important;color:%s !important;border:1px solid %s !important;display:inline-flex;align-items:center;',
        $palette['background'],
        $palette['color'],
        $palette['border']
    );
@endphp

<span {{ $attributes->merge(['style' => $style])->class([
    'badge',
    'status-badge',
    'rounded-pill' => $pill,
    \App\Support\StatusBadge::for($type, $value),
]) }}>{{ $resolvedLabel }}</span>
