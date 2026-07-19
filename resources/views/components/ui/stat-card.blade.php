@props([
    'label',
    'value',
    'tone' => 'primary',
    'description' => null,
])

<article {{ $attributes->class(['lms-stat', $tone !== 'primary' ? $tone : null]) }}>
    <div class="lms-stat-label">{{ $label }}</div>
    <div class="lms-stat-value">{{ $value }}</div>
    {{ $slot }}
    @if ($description)
        <div class="lms-stat-sub">{{ $description }}</div>
    @endif
</article>
