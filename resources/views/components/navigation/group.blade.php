@props([
    'id',
    'label',
    'icon',
    'items' => [],
])

@php
    $itemIsActive = fn (array $item): bool => request()->routeIs(...(array) ($item['patterns'] ?? $item['route']))
        && (empty($item['except']) || ! request()->routeIs(...(array) $item['except']));
    $isActive = collect($items)->contains($itemIsActive);
@endphp

<section {{ $attributes->class(['sidebar-group', 'is-active' => $isActive]) }}>
    <button class="sidebar-group__toggle" type="button" data-bs-toggle="collapse"
        data-bs-target="#{{ $id }}" aria-expanded="{{ $isActive ? 'true' : 'false' }}"
        aria-controls="{{ $id }}">
        <i class="fa-solid {{ $icon }} sidebar-group__icon" aria-hidden="true"></i>
        <span class="sidebar-group__label">{{ $label }}</span>
        <i class="fa-solid fa-chevron-down sidebar-group__chevron" aria-hidden="true"></i>
    </button>

    <div id="{{ $id }}" class="sidebar-group__items collapse {{ $isActive ? 'show' : '' }}">
        @foreach ($items as $item)
            @php
                $itemActive = $itemIsActive($item);
            @endphp
            <a class="sidebar-item {{ $itemActive ? 'active' : '' }}" href="{{ route($item['route']) }}"
                @if (! empty($item['testid'])) data-testid="{{ $item['testid'] }}" @endif
                @if ($itemActive) aria-current="page" @endif>
                <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</section>
