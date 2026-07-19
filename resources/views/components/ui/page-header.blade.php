@props([
    'title',
    'breadcrumbs' => [],
])

<header {{ $attributes->class(['lms-page-header']) }}>
    <div>
        @if ($breadcrumbs !== [])
            <nav class="lms-breadcrumb" aria-label="breadcrumb">
                @foreach ($breadcrumbs as $breadcrumb)
                    @if (! $loop->first)
                        <span class="lms-breadcrumb-sep" aria-hidden="true">›</span>
                    @endif

                    @if (! empty($breadcrumb['url']))
                        <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</a>
                    @else
                        <span aria-current="page">{{ $breadcrumb['label'] }}</span>
                    @endif
                @endforeach
            </nav>
        @endif

        <h1 class="lms-page-title">{{ $title }}</h1>

        @isset($meta)
            <div class="lms-page-meta">{{ $meta }}</div>
        @endisset
    </div>

    @isset($actions)
        <div class="lms-btn-group">{{ $actions }}</div>
    @endisset
</header>
