@props([
    'status',
    'label' => null,
])

@php
    $tone = \App\Support\UiLabels::statusTone($status);
    $text = $label ?: \App\Support\UiLabels::status($status);
@endphp

<span {{ $attributes->class(['lms-badge', "lms-badge--{$tone}"]) }}>{{ $text }}</span>
