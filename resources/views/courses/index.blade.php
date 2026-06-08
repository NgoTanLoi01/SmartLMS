@extends('layouts.app')

@section('title', 'Danh sách khóa học')

@section('content')
    <style>
        .page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 28px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .page-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--text);
            margin: 0 0 4px;
            line-height: 1.3;
        }

        .page-subtitle {
            font-size: 13.5px;
            color: var(--muted);
            margin: 0;
        }

        .btn-create {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--blue);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 9px 18px;
            font-size: 14px;
            font-weight: 500;
            font-family: 'Be Vietnam Pro', sans-serif;
            text-decoration: none;
            white-space: nowrap;
            transition: background 0.15s;
            flex-shrink: 0;
        }

        .btn-create:hover {
            background: #1d4ed8;
            color: #fff;
        }

        .btn-create i {
            font-size: 12px;
        }

        /* ── Grid ── */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(370px, 1fr));
            gap: 20px;
        }

        /* ── Card ── */
        .course-card {
            background: #fff;
            border: 1px solid #e8edf3;
            border-radius: 14px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .course-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 28px rgba(37, 99, 235, 0.09);
            border-color: #bfdbfe;
        }

        /* ── Card thumb ── */
        .card-thumb {
            height: 130px;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            border-bottom: 1px solid #e8edf3;
        }

        .card-thumb .thumb-icon {
            font-size: 2.4rem;
            color: #93c5fd;
        }

        /* ── Action menu (3 chấm) ── */
        .card-menu-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 13px;
            color: #64748b;
            transition: background 0.15s;
            padding: 0;
        }

        .card-menu-btn:hover {
            background: #fff;
            color: #2563eb;
        }

        .card-dropdown {
            border: 1px solid #e8edf3;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.09);
            padding: 6px;
            min-width: 180px;
        }

        .card-dropdown .dropdown-item {
            border-radius: 8px;
            font-size: 13.5px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #0f172a;
        }

        .card-dropdown .dropdown-item:hover {
            background: #eff6ff;
            color: #2563eb;
        }

        .card-dropdown .dropdown-item.text-danger:hover {
            background: #fef2f2;
            color: #dc2626;
        }

        .card-dropdown .dropdown-divider {
            border-color: #f1f5f9;
            margin: 4px 0;
        }

        /* ── Card body ── */
        .card-body-inner {
            padding: 18px 18px 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .card-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 11.5px;
            font-weight: 500;
            padding: 3px 10px;
            border-radius: 6px;
            margin-bottom: 10px;
            max-width: 100%;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .card-title {
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
            line-height: 1.45;
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 44px;
        }

        .card-desc {
            font-size: 13px;
            color: #64748b;
            line-height: 1.55;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 14px;
        }

        /* Teacher row */
        .teacher-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .teacher-row img {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .teacher-row span {
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        /* Stats row */
        .stats-row {
            display: flex;
            gap: 14px;
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 14px;
        }

        .stats-row span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .stats-row i {
            font-size: 11px;
        }

        /* Divider */
        .card-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 0 0 14px;
        }

        /* Progress */
        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 11.5px;
            margin-bottom: 5px;
            color: #94a3b8;
        }

        .progress-label span:last-child {
            font-weight: 600;
            color: #2563eb;
        }

        .progress-bar-wrap {
            height: 4px;
            background: #e8edf3;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 14px;
        }

        .progress-bar-fill {
            height: 100%;
            background: #2563eb;
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        /* Updated at */
        .updated-at {
            font-size: 11.5px;
            color: #94a3b8;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Enter button */
        .btn-enter {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 13.5px;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.15s, border-color 0.15s;
            margin-top: auto;
        }

        .btn-enter:hover {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
        }

        .btn-enter i {
            font-size: 11px;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
            display: block;
            opacity: .4;
        }

        .empty-state p {
            font-size: 15px;
            margin: 0;
        }

        .dropdown {
            position: absolute;
            top: 10px;
            right: 10px;
        }

        @media (max-width: 767.98px) {
            .page-header {
                align-items: stretch;
            }

            .btn-create {
                justify-content: center;
                width: 100%;
            }

            .courses-grid {
                grid-template-columns: 1fr;
            }

            .stats-row {
                flex-wrap: wrap;
                gap: 8px 14px;
            }
        }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">Khóa học của tôi</h1>
            <p class="page-subtitle">Học tập và phát triển kỹ năng mỗi ngày cùng SmartLMS</p>
        </div>
        @if (auth()->user()->role === 'teacher' || auth()->user()->role === 'admin')
            <a href="{{ route('courses.create') }}" class="btn-create">
                <i class="fas fa-plus"></i> Tạo khóa học mới
            </a>
        @endif
    </div>

    @if ($courses->isEmpty())
        <div class="empty-state">
            <i class="fas fa-graduation-cap"></i>
            <p>Chưa có khóa học nào. Hãy tạo hoặc tham gia khóa học đầu tiên!</p>
        </div>
    @else
        <div class="courses-grid">
            @foreach ($courses as $course)
                <div class="course-card">

                    {{-- Thumb --}}
                    <div class="card-thumb">
                        <i class="fas fa-laptop-code thumb-icon"></i>

                        @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                            <div class="dropdown">
                                <button class="card-menu-btn" type="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end card-dropdown">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('courses.edit', $course->id) }}">
                                            <i class="fas fa-edit" style="color:#f59e0b;"></i> Sửa khóa học
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <form action="{{ route('courses.destroy', $course->id) }}" method="POST"
                                            onsubmit="return confirm('Bạn có chắc chắn muốn xóa khóa học này?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger"
                                                style="background:none; border:none; width:100%; text-align:left;">
                                                <i class="fas fa-trash-alt"></i> Xóa khóa học
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        @endif
                    </div>

                    {{-- Body --}}
                    <div class="card-body-inner">
                        <div class="card-badge">
                            <i class="fas fa-users" style="font-size:10px;"></i>
                            {{ $course->classes->first()->name ?? 'Tự do' }}
                        </div>
                        @if (auth()->user()->role !== 'student')
                            <div class="card-badge" style="background:{{ $course->status === 'published' ? '#ecfdf5' : ($course->status === 'hidden' ? '#f1f5f9' : '#fffbeb') }};color:{{ $course->status === 'published' ? '#047857' : ($course->status === 'hidden' ? '#475569' : '#92400e') }};">
                                <i class="fas fa-eye" style="font-size:10px;"></i>
                                {{ strtoupper($course->status ?? 'published') }}
                                @if ($course->available_from)
                                    · mở {{ $course->available_from->format('d/m/Y H:i') }}
                                @endif
                            </div>
                        @endif

                        <h2 class="card-title">{{ $course->title }}</h2>

                        <p class="card-desc">
                            {{ $course->description ?? 'Chưa có mô tả chi tiết cho khóa học này.' }}
                        </p>

                        <div class="teacher-row">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($course->teacher->name) }}&background=2563eb&color=fff&size=48"
                                alt="{{ $course->teacher->name }}">
                            <span>{{ $course->teacher->name }}</span>
                        </div>

                        <div class="stats-row">
                            <span><i class="fas fa-book-open"></i> {{ $course->lessons_count ?? 0 }} bài học</span>
                            <span><i class="fas fa-user-graduate"></i> {{ $course->students_count ?? 0 }} học sinh</span>
                        </div>

                        <div class="card-divider"></div>

                        @if (auth()->user()->role === 'student')
                            <div class="progress-label">
                                <span>Tiến độ</span>
                                <span>{{ $course->progress }}%</span>
                            </div>
                            <div class="progress-bar-wrap">
                                <div class="progress-bar-fill" style="width: {{ $course->progress }}%"></div>
                            </div>
                        @else
                            <div class="updated-at">
                                <i class="far fa-clock"></i>
                                Cập nhật {{ $course->updated_at->diffForHumans() }}
                            </div>
                        @endif

                        <a href="{{ route('courses.show', $course->id) }}" class="btn-enter">
                            Vào học ngay <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
