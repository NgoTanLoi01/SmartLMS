@props([
    'href' => null,
    'tone' => 'primary',
    'size' => 'md',
    'icon' => null,
    'type' => 'button',
])

@php
    $classes = ['lms-btn', "lms-btn-{$tone}", $size === 'sm' ? 'lms-btn-sm' : null];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)<i class="fa-solid {{ $icon }}" aria-hidden="true"></i>@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon)<i class="fa-solid {{ $icon }}" aria-hidden="true"></i>@endif
        {{ $slot }}
    </button>
@endif
