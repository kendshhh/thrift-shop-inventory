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
        '--status-badge-bg:%s;--status-badge-color:%s;--status-badge-border:%s;display:inline-flex;align-items:center;',
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
