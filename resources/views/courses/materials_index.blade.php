@extends('layouts.app')

@section('content')
    <style>
        .materials-index {
            background: #f4f7fb;
            min-height: calc(100vh - 70px);
            padding: 28px 0 48px;
        }

        .materials-index-shell {
            max-width: 1180px;
        }

        .materials-index-hero,
        .materials-course-card {
            background: #fff;
            border: 1px solid #e5ebf5;
            border-radius: 22px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
        }

        .materials-index-hero {
            margin-bottom: 18px;
            padding: 26px;
        }

        .materials-index-kicker {
            color: #2f6fed;
            font-size: 12px;
            font-weight: 950;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .materials-index-title {
            color: #111827;
            font-size: clamp(28px, 4vw, 44px);
            font-weight: 950;
            letter-spacing: 0;
            line-height: 1.12;
            margin: 8px 0;
        }

        .materials-index-subtitle {
            color: #64748b;
            font-size: 15px;
            line-height: 1.55;
            margin: 0;
            max-width: 760px;
        }

        .materials-course-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .materials-course-card {
            color: inherit;
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-height: 190px;
            padding: 20px;
            text-decoration: none;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .materials-course-card:hover {
            border-color: #b8ccff;
            box-shadow: 0 16px 34px rgba(47, 111, 237, .14);
            color: inherit;
            transform: translateY(-2px);
        }

        .materials-course-icon {
            align-items: center;
            background: #eaf1ff;
            border-radius: 16px;
            color: #2f6fed;
            display: inline-flex;
            font-size: 20px;
            height: 48px;
            justify-content: center;
            width: 48px;
        }

        .materials-course-title {
            color: #111827;
            font-size: 18px;
            font-weight: 950;
            line-height: 1.35;
            margin: 0;
        }

        .materials-course-meta {
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.5;
            margin-top: 6px;
        }

        .materials-course-footer {
            align-items: center;
            display: flex;
            justify-content: space-between;
            margin-top: auto;
        }

        .materials-count {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            color: #475569;
            font-size: 12px;
            font-weight: 900;
            padding: 6px 10px;
        }

        .materials-open {
            color: #2f6fed;
            font-size: 13px;
            font-weight: 950;
        }

        .materials-empty {
            background: #fff;
            border: 1px dashed #cbd5e1;
            border-radius: 20px;
            color: #64748b;
            font-weight: 800;
            padding: 36px;
            text-align: center;
        }

        @media (max-width: 991.98px) {
            .materials-course-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .materials-course-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="materials-index">
        <div class="container materials-index-shell">
            <div class="materials-index-hero">
                <div class="materials-index-kicker">
                    <i class="fas fa-folder-open me-2"></i>Kho học liệu
                </div>
                <h1 class="materials-index-title">Chọn khóa học để quản lý học liệu</h1>
                <p class="materials-index-subtitle">
                    Mỗi khóa học có một kho riêng để quản lý file PDF, slide, link video, website tham khảo và file code mẫu.
                </p>
            </div>

            @if ($courses->isEmpty())
                <div class="materials-empty">
                    <i class="fas fa-folder-open me-2"></i> Chưa có khóa học nào khả dụng.
                </div>
            @else
                <div class="materials-course-grid">
                    @foreach ($courses as $course)
                        <a class="materials-course-card" href="{{ route('courses.materials.index', $course->id) }}">
                            <span class="materials-course-icon">
                                <i class="fas fa-book-open"></i>
                            </span>
                            <div>
                                <h2 class="materials-course-title">{{ $course->title }}</h2>
                                <div class="materials-course-meta">
                                    {{ $course->teacher?->name ?? 'Chưa có giáo viên' }}
                                    @if ($course->classes->isNotEmpty())
                                        · {{ $course->classes->pluck('name')->take(2)->join(', ') }}
                                    @endif
                                </div>
                            </div>
                            <div class="materials-course-footer">
                                <span class="materials-count">{{ $course->materials_count }} học liệu</span>
                                <span class="materials-open">Mở kho <i class="fas fa-arrow-right ms-1"></i></span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
