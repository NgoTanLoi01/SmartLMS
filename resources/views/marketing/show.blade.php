@extends('layouts.marketing')

@section('title', $page['title'])
@section('meta_description', $page['description'])

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => route('home').'#website',
                    'url' => route('home'),
                    'name' => 'SmartLMS.io.vn',
                    'alternateName' => 'SmartLMS',
                    'inLanguage' => 'vi-VN',
                ],
                [
                    '@type' => 'SoftwareApplication',
                    '@id' => url()->current().'#software',
                    'name' => 'SmartLMS.io.vn',
                    'alternateName' => 'SmartLMS',
                    'url' => url()->current(),
                    'description' => $page['description'],
                    'applicationCategory' => 'EducationalApplication',
                    'operatingSystem' => 'Web',
                    'inLanguage' => 'vi-VN',
                    'creator' => [
                        '@type' => 'Person',
                        'name' => 'NgoTanLoi',
                    ],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => route('home')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => $page['nav_label'], 'item' => url()->current()],
                    ],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endpush

@section('content')
    <section class="marketing-hero" aria-labelledby="marketing-title">
        <div class="marketing-hero__orb" aria-hidden="true"></div>
        <div class="marketing-shell marketing-hero__inner">
            <div class="marketing-hero__copy">
                <nav class="marketing-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ route('home') }}">Trang chủ</a>
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    <span aria-current="page">{{ $page['nav_label'] }}</span>
                </nav>
                <span class="marketing-eyebrow"><i class="fa-solid {{ $page['icon'] }}" aria-hidden="true"></i>{{ $page['eyebrow'] }}</span>
                <h1 id="marketing-title">{{ $page['headline'] }}</h1>
                <p>{{ $page['lead'] }}</p>
                <div class="marketing-actions">
                    <a class="button button--primary" href="{{ route('login') }}">Truy cập SmartLMS <x-ui.icon name="arrow-right" /></a>
                    <a class="button button--secondary" href="#tinh-nang">Xem chức năng <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
                </div>
                <div class="marketing-highlights" aria-label="Điểm nổi bật">
                    @foreach ($page['highlights'] as $highlight)
                        <span><i class="fa-solid fa-circle-check" aria-hidden="true"></i>{{ $highlight }}</span>
                    @endforeach
                </div>
            </div>
            <div class="marketing-hero__visual" aria-hidden="true">
                <div class="marketing-product-card">
                    <span class="marketing-product-card__brand">SmartLMS.io.vn</span>
                    <span class="marketing-product-card__icon"><i class="fa-solid {{ $page['icon'] }}"></i></span>
                    <strong>{{ $page['nav_label'] }}</strong>
                    <small>Vận hành trên cùng một hệ thống</small>
                    <div class="marketing-product-card__signals">
                        @foreach ($page['highlights'] as $highlight)
                            <span><i class="fa-solid fa-check"></i>{{ $highlight }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="marketing-section" id="tinh-nang" aria-labelledby="features-title">
        <div class="marketing-shell">
            <header class="marketing-section-heading">
                <span>Chức năng thực tế</span>
                <h2 id="features-title">Một quy trình rõ ràng cho người sử dụng</h2>
                <p>Các chức năng được kết nối với dữ liệu và quyền truy cập hiện có trong SmartLMS.</p>
            </header>
            <div class="marketing-feature-grid">
                @foreach ($page['features'] as $feature)
                    <article class="marketing-feature-card">
                        <span><i class="fa-solid {{ $feature['icon'] }}" aria-hidden="true"></i></span>
                        <h3>{{ $feature['title'] }}</h3>
                        <p>{{ $feature['body'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="marketing-section marketing-section--tinted" aria-labelledby="workflow-title">
        <div class="marketing-shell marketing-workflow-shell">
            <header class="marketing-section-heading marketing-section-heading--left">
                <span>Luồng sử dụng</span>
                <h2 id="workflow-title">Từ thiết lập đến vận hành</h2>
                <p>SmartLMS giữ các bước quan trọng trong cùng một luồng để giảm nhập lại và hạn chế sai lệch dữ liệu.</p>
            </header>
            <div class="marketing-workflow">
                @foreach ($page['workflow'] as $step)
                    <article>
                        <span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <div><h3>{{ $step['title'] }}</h3><p>{{ $step['body'] }}</p></div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="marketing-section" aria-labelledby="faq-title">
        <div class="marketing-shell marketing-faq-shell">
            <header class="marketing-section-heading marketing-section-heading--left">
                <span>Câu hỏi thường gặp</span>
                <h2 id="faq-title">Thông tin cần biết</h2>
            </header>
            <div class="marketing-faq-list">
                @foreach ($page['faq'] as $item)
                    <details {{ $loop->first ? 'open' : '' }}>
                        <summary>{{ $item['question'] }}<i class="fa-solid fa-plus" aria-hidden="true"></i></summary>
                        <p>{{ $item['answer'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    <section class="marketing-related" aria-labelledby="related-title">
        <div class="marketing-shell">
            <div class="marketing-related__head">
                <div><span>Khám phá SmartLMS</span><h2 id="related-title">Các chức năng liên quan</h2></div>
                <a href="{{ route('home') }}">Về trang tổng quan <x-ui.icon name="arrow-right" /></a>
            </div>
            <div class="marketing-related__links">
                @foreach (collect($pages)->except($slug)->take(4) as $related)
                    <a href="{{ route($related['route']) }}">
                        <span>{{ $related['nav_label'] }}</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="final-cta marketing-cta" aria-labelledby="marketing-cta-title">
        <div class="final-cta__inner">
            <div>
                <span class="final-cta__label">SmartLMS.io.vn · Sản phẩm độc lập</span>
                <h2 id="marketing-cta-title">Sẵn sàng tiếp tục công việc đào tạo?</h2>
                <p>Đăng nhập bằng tài khoản được quản trị viên cấp để truy cập đúng không gian làm việc của bạn.</p>
            </div>
            <a class="button button--light" href="{{ route('login') }}">Đến trang đăng nhập <x-ui.icon name="arrow-right" /></a>
        </div>
    </section>
@endsection
