@props([
    'title' => 'Chưa có dữ liệu',
    'description' => null,
    'icon' => 'fa-inbox',
])

<div {{ $attributes->class(['lms-empty-state']) }}>
    <span class="lms-empty-state__icon" aria-hidden="true">
        <i class="fa-solid {{ $icon }}"></i>
    </span>
    <h3 class="lms-empty-state__title">{{ $title }}</h3>
    @if ($description)
        <p class="lms-empty-state__description">{{ $description }}</p>
    @endif
    @if (trim((string) $slot) !== '')
        <div class="lms-empty-state__action">{{ $slot }}</div>
    @endif
</div>
