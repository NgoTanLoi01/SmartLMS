<div class="course-card {{ $course->isTemplate() ? 'course-card-template' : '' }}">
    <div class="card-thumb">
        <i class="fas {{ $course->isTemplate() ? 'fa-layer-group' : 'fa-laptop-code' }} thumb-icon"></i>

        @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
            <div class="dropdown">
                <button class="card-menu-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end card-dropdown">
                    <li>
                        <a class="dropdown-item" href="{{ route('courses.edit', $course->id) }}">
                            <i class="fas fa-edit" style="color:#f59e0b;"></i> Sửa khóa học
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('courses.create', ['template_course_id' => $course->id]) }}">
                            <i class="fas fa-copy" style="color:#2563eb;"></i>
                            {{ $course->isTemplate() ? 'Tạo khóa từ mẫu' : 'Dùng làm mẫu' }}
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    @if ($course->status === \App\Models\Course::STATUS_ARCHIVED)
                        @if (auth()->user()->role === 'admin')
                            <li>
                                <form action="{{ route('courses.permanent-destroy', $course) }}" method="POST"
                                    onsubmit="return confirm('XÓA VĨNH VIỄN khóa học này? Toàn bộ chương, bài học, bài tập, điểm và bài nộp sẽ bị xóa, không thể khôi phục.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger"
                                        style="background:none; border:none; width:100%; text-align:left;">
                                        <i class="fas fa-trash-can"></i> Xóa vĩnh viễn
                                    </button>
                                </form>
                            </li>
                        @endif
                    @else
                        <li>
                            <form action="{{ route('courses.destroy', $course->id) }}" method="POST"
                                onsubmit="return confirm('Lưu trữ khóa học này? Học sinh sẽ không còn thấy khóa học nhưng dữ liệu vẫn được giữ lại.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger"
                                    style="background:none; border:none; width:100%; text-align:left;">
                                    <i class="fas fa-archive"></i> Lưu trữ khóa học
                                </button>
                            </form>
                        </li>
                    @endif
                </ul>
            </div>
        @endif
    </div>

    <div class="card-body-inner">
        @if ($course->isTemplate())
            <div class="card-badge card-badge-template">
                <i class="fas fa-layer-group" style="font-size:10px;"></i> Khóa mẫu
            </div>
        @else
            <div class="card-badge">
                <i class="fas fa-users" style="font-size:10px;"></i>
                {{ $course->classes->first()->name ?? 'Chưa gắn lớp' }}
            </div>
        @endif

        @if ($course->learningProgram)
            <div class="card-badge" style="background:#f0fdf4;color:#15803d;">
                <i class="fas fa-sitemap" style="font-size:10px;"></i>
                {{ $course->learningProgram->name }}
            </div>
        @endif

        @if (auth()->user()->role !== 'student')
            @php
                $statusStyles = [
                    'published' => ['bg' => '#ecfdf5', 'color' => '#047857'],
                    'hidden' => ['bg' => '#f1f5f9', 'color' => '#475569'],
                    'archived' => ['bg' => '#fef2f2', 'color' => '#b91c1c'],
                    'draft' => ['bg' => '#fffbeb', 'color' => '#92400e'],
                ];
                $courseStatusStyle = $statusStyles[$course->status ?? 'published'] ?? $statusStyles['draft'];
            @endphp
            <div class="card-badge" style="background:{{ $courseStatusStyle['bg'] }};color:{{ $courseStatusStyle['color'] }};">
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
            @if ($course->isTemplate())
                <span><i class="fas fa-folder-tree"></i> {{ $course->modules_count ?? 0 }} chương</span>
            @else
                <span><i class="fas fa-user-graduate"></i> {{ $course->students_count ?? 0 }} học sinh</span>
            @endif
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

        @if ($course->isTemplate())
            <a href="{{ route('courses.create', ['template_course_id' => $course->id]) }}" class="btn-enter btn-enter-template">
                Tạo khóa từ mẫu <i class="fas fa-copy"></i>
            </a>
        @else
            <a href="{{ route('courses.show', $course->id) }}" class="btn-enter">
                Vào khóa học <i class="fas fa-arrow-right"></i>
            </a>
        @endif
    </div>
</div>
