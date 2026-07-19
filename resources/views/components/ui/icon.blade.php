@props([
    'name',
    'label' => null,
])

@php
    $icons = [
        'ai' => 'fa-robot',
        'arrow-right' => 'fa-arrow-right',
        'assignment' => 'fa-file-pen',
        'book' => 'fa-book-open',
        'calendar' => 'fa-calendar-days',
        'check' => 'fa-check',
        'class' => 'fa-school',
        'close' => 'fa-xmark',
        'course' => 'fa-graduation-cap',
        'game' => 'fa-gamepad',
        'info' => 'fa-circle-info',
        'quiz' => 'fa-clipboard-question',
        'sparkles' => 'fa-wand-magic-sparkles',
        'students' => 'fa-user-group',
        'success' => 'fa-circle-check',
        'teacher' => 'fa-chalkboard-user',
        'warning' => 'fa-triangle-exclamation',
    ];

    $icon = $icons[$name] ?? 'fa-circle';
@endphp

<i {{ $attributes->class(['fa-solid', $icon]) }}
    @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif></i>
