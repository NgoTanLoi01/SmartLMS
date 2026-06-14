@extends('layouts.app')

@section('title', $program->name)

@section('content')
    <style>
        .program-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .program-detail-title {
            margin: 0 0 8px;
            color: #0f172a;
            font-size: 24px;
            font-weight: 800;
        }

        .program-detail-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
        }

        .program-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 5px 11px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
        }

        .program-detail-desc {
            color: #64748b;
            max-width: 780px;
            margin: 0;
            line-height: 1.6;
            font-size: 14px;
        }

        .program-actions-top {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .program-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .program-stat {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .program-stat-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .program-stat-value {
            color: #0f172a;
            font-size: 26px;
            font-weight: 800;
            margin-top: 6px;
        }

        .program-section {
            margin-bottom: 24px;
        }

        .program-section-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .program-section-title {
            margin: 0;
            color: #0f172a;
            font-size: 17px;
            font-weight: 800;
        }

        .program-section-sub {
            margin: 3px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .program-list {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .program-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 14px;
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            align-items: center;
        }

        .program-row:last-child {
            border-bottom: none;
        }

        .program-row-title {
            margin: 0 0 6px;
            color: #0f172a;
            font-size: 15px;
            font-weight: 750;
        }

        .program-row-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            color: #64748b;
            font-size: 12.5px;
        }

        .program-empty {
            padding: 24px;
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 767.98px) {
            .program-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .program-actions-top,
            .program-section-head,
            .program-row {
                align-items: stretch;
                grid-template-columns: 1fr;
            }

            .program-actions-top .btn,
            .program-row .btn {
                width: 100%;
            }
        }
    </style>

    <div class="program-detail-header">
        <div>

            <h1 class="program-detail-title">{{ $program->name }}</h1>
            <div class="program-detail-meta">
                <span class="program-chip"><i class="fas fa-hashtag"></i>{{ $program->code }}</span>
                <span class="program-chip" style="background:#f0fdf4;color:#15803d;">
                    <i class="fas fa-user-tie"></i>{{ $program->teacher?->name ?? 'N/A' }}
                </span>
                <span class="program-chip" style="background:#f8fafc;color:#475569;">
                    <i class="fas fa-circle"></i>{{ strtoupper($program->status) }}
                </span>
            </div>
            <p class="program-detail-desc">{{ $program->description ?: 'Chưa có mô tả cho chương trình này.' }}</p>
        </div>
        <div class="program-actions-top">
            <a href="{{ route('courses.create', ['learning_program_id' => $program->id, 'course_type' => 'template']) }}"
                class="btn btn-outline-primary rounded-pill px-4">
                <i class="fas fa-layer-group me-2"></i>Tạo khóa mẫu
            </a>
            <a href="{{ route('courses.create', ['learning_program_id' => $program->id, 'course_type' => 'delivery']) }}"
                class="btn btn-primary rounded-pill px-4">
                <i class="fas fa-plus me-2"></i>Tạo khóa triển khai
            </a>
        </div>
    </div>

    <div class="program-stats">
        <div class="program-stat">
            <div class="program-stat-label">Khóa mẫu</div>
            <div class="program-stat-value">{{ $stats['template_courses'] }}</div>
        </div>
        <div class="program-stat">
            <div class="program-stat-label">Khóa triển khai</div>
            <div class="program-stat-value">{{ $stats['delivery_courses'] }}</div>
        </div>
        <div class="program-stat">
            <div class="program-stat-label">Lớp đang học</div>
            <div class="program-stat-value">{{ $stats['classes'] }}</div>
        </div>
        <div class="program-stat">
            <div class="program-stat-label">Học sinh</div>
            <div class="program-stat-value">{{ $stats['students'] }}</div>
        </div>
    </div>

    <section class="program-section">
        <div class="program-section-head">
            <div>
                <h2 class="program-section-title">Khóa mẫu</h2>
                <p class="program-section-sub">Nguồn nội dung chuẩn dùng để tạo các khóa triển khai.</p>
            </div>
            <a href="{{ route('courses.create', ['learning_program_id' => $program->id, 'course_type' => 'template']) }}"
                class="btn btn-sm btn-outline-primary rounded-pill">
                <i class="fas fa-plus me-1"></i>Thêm mẫu
            </a>
        </div>
        <div class="program-list">
            @forelse ($templateCourses as $course)
                <div class="program-row">
                    <div>
                        <h3 class="program-row-title">{{ $course->title }}</h3>
                        <div class="program-row-meta">
                            <span><i class="fas fa-folder-tree"></i> {{ $course->modules_count ?? 0 }} chương</span>
                            <span><i class="fas fa-book-open"></i> {{ $course->lessons_count ?? 0 }} bài học</span>
                            <span><i class="fas fa-eye"></i> {{ strtoupper($course->status ?? 'published') }}</span>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                        <a href="{{ route('courses.show', $course->id) }}" class="btn btn-sm btn-light rounded-pill">
                            Xem nội dung
                        </a>
                        <a href="{{ route('courses.create', ['learning_program_id' => $program->id, 'template_course_id' => $course->id, 'course_type' => 'delivery']) }}"
                            class="btn btn-sm btn-primary rounded-pill">
                            Tạo khóa từ mẫu
                        </a>
                    </div>
                </div>
            @empty
                <div class="program-empty">
                    Chưa có khóa mẫu. Hãy tạo một khóa mẫu đầu tiên cho chương trình này.
                </div>
            @endforelse
        </div>
    </section>

    <section class="program-section">
        <div class="program-section-head">
            <div>
                <h2 class="program-section-title">Khóa đang triển khai</h2>
                <p class="program-section-sub">Các khóa thật đang được gắn với lớp và học sinh.</p>
            </div>
            <a href="{{ route('courses.create', ['learning_program_id' => $program->id, 'course_type' => 'delivery']) }}"
                class="btn btn-sm btn-outline-primary rounded-pill">
                <i class="fas fa-plus me-1"></i>Thêm khóa triển khai
            </a>
        </div>
        <div class="program-list">
            @forelse ($deliveryCourses as $course)
                <div class="program-row">
                    <div>
                        <h3 class="program-row-title">{{ $course->title }}</h3>
                        <div class="program-row-meta">
                            <span><i class="fas fa-chalkboard"></i>
                                {{ $course->classes->pluck('name')->join(', ') ?: 'Chưa gắn lớp' }}
                            </span>
                            <span><i class="fas fa-user-graduate"></i> {{ $course->students_count ?? 0 }} học sinh</span>
                            <span><i class="fas fa-book-open"></i> {{ $course->lessons_count ?? 0 }} bài học</span>
                            <span><i class="fas fa-eye"></i> {{ strtoupper($course->status ?? 'published') }}</span>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                        <a href="{{ route('courses.show', $course->id) }}" class="btn btn-sm btn-primary rounded-pill">
                            Vào khóa học
                        </a>
                    </div>
                </div>
            @empty
                <div class="program-empty">
                    Chưa có khóa triển khai từ chương trình này.
                </div>
            @endforelse
        </div>
    </section>
@endsection
