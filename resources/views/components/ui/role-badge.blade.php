@props(['role'])

@php
    $tone = \App\Support\UiLabels::roleTone($role);
@endphp

<span {{ $attributes->class(['lms-badge', "lms-badge--{$tone}"]) }}>
    {{ \App\Support\UiLabels::role($role) }}
</span>
