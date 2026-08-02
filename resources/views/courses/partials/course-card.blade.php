@php
    $isManager = auth()->user()->role !== 'student';
    $isTemplate = $course->isTemplate();
    $statusMeta = [
        'published' => ['label' => 'Đã xuất bản', 'icon' => 'fa-circle-check'],
        'draft' => ['label' => 'Bản nháp', 'icon' => 'fa-pen-ruler'],
        'hidden' => ['label' => 'Tạm ẩn', 'icon' => 'fa-eye-slash'],
        'archived' => ['label' => 'Đã lưu trữ', 'icon' => 'fa-box-archive'],
    ][$course->status ?? 'draft'] ?? ['label' => 'Bản nháp', 'icon' => 'fa-pen-ruler'];
    $primaryClass = $course->classes->first();
    $extraClassCount = max(0, $course->classes->count() - 1);
    $teacherName = $course->teacher?->name ?? 'Chưa phân công';
    $teacherInitial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($teacherName, 0, 1));
@endphp

<article class="catalog-card course-catalog-card {{ $isTemplate ? 'is-template' : '' }}">
    <div class="catalog-card-visual" aria-hidden="true">
        <span class="catalog-card-icon">
            <i class="fa-solid {{ $isTemplate ? 'fa-layer-group' : 'fa-book-open-reader' }}"></i>
        </span>
        <span class="catalog-card-type">{{ $isTemplate ? 'Khóa mẫu' : 'Khóa triển khai' }}</span>
    </div>

    @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
        <div class="dropdown catalog-card-menu">
            <button class="catalog-icon-button" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                aria-label="Mở thao tác cho khóa {{ $course->title }}">
                <i class="fa-solid fa-ellipsis" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end catalog-dropdown">
                <li>
                    <a class="dropdown-item" href="{{ route('courses.edit', $course) }}">
                        <i class="fa-solid fa-pen" aria-hidden="true"></i>Sửa khóa học
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('courses.create', ['template_course_id' => $course->id]) }}">
                        <i class="fa-solid fa-copy" aria-hidden="true"></i>
                        {{ $isTemplate ? 'Tạo khóa từ mẫu' : 'Dùng làm mẫu' }}
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                @if ($course->status === \App\Models\Course::STATUS_ARCHIVED)
                    @if (auth()->user()->role === 'admin')
                        <li>
                            <form action="{{ route('courses.permanent-destroy', $course) }}" method="POST"
                                onsubmit="return confirm('Xóa vĩnh viễn khóa học này? Toàn bộ dữ liệu liên quan sẽ bị xóa và không thể khôi phục.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i>Xóa vĩnh viễn
                                </button>
                            </form>
                        </li>
                    @endif
                @else
                    <li>
                        <form action="{{ route('courses.destroy', $course) }}" method="POST"
                            onsubmit="return confirm('Lưu trữ khóa học này? Học viên sẽ không còn thấy khóa học nhưng dữ liệu vẫn được giữ lại.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fa-solid fa-box-archive" aria-hidden="true"></i>Lưu trữ khóa học
                            </button>
                        </form>
                    </li>
                @endif
            </ul>
        </div>
    @endif

    <div class="catalog-card-body">
        <div class="catalog-card-tags">
            @if ($isManager)
                <span class="catalog-status status-{{ $course->status ?? 'draft' }}">
                    <i class="fa-solid {{ $statusMeta['icon'] }}" aria-hidden="true"></i>{{ $statusMeta['label'] }}
                </span>
            @endif
            @if ($course->learningProgram)
                <span class="catalog-tag" title="{{ $course->learningProgram->name }}">
                    <i class="fa-solid fa-diagram-project" aria-hidden="true"></i>
                    {{ $course->learningProgram->name }}
                </span>
            @endif
        </div>

        <h3 class="catalog-card-title">
            @if ($isTemplate)
                <a href="{{ route('courses.create', ['template_course_id' => $course->id]) }}">{{ $course->title }}</a>
            @else
                <a href="{{ route('courses.show', $course) }}">{{ $course->title }}</a>
            @endif
        </h3>
        <p class="catalog-card-description">
            {{ $course->description ?: 'Khóa học chưa có mô tả. Bạn có thể mở khóa học để xem nội dung chi tiết.' }}
        </p>

        <div class="catalog-owner">
            <span class="catalog-avatar" aria-hidden="true">{{ $teacherInitial }}</span>
            <span>
                <small>Giáo viên</small>
                <strong>{{ $teacherName }}</strong>
            </span>
        </div>

        <dl class="catalog-metrics" aria-label="Thông tin khóa học">
            <div>
                <dt><i class="fa-solid fa-folder-tree" aria-hidden="true"></i>Chương</dt>
                <dd>{{ $course->modules_count ?? 0 }}</dd>
            </div>
            <div>
                <dt><i class="fa-solid fa-file-lines" aria-hidden="true"></i>Bài học</dt>
                <dd>{{ $course->lessons_count ?? 0 }}</dd>
            </div>
            @unless ($isTemplate)
                <div>
                    <dt><i class="fa-solid fa-user-graduate" aria-hidden="true"></i>Học viên</dt>
                    <dd>{{ $course->students_count ?? 0 }}</dd>
                </div>
            @endunless
        </dl>

        <div class="catalog-card-context">
            @if ($isTemplate)
                <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                Dùng làm nội dung chuẩn cho khóa mới
            @elseif ($primaryClass)
                <i class="fa-solid fa-school" aria-hidden="true"></i>
                <span>{{ $primaryClass->name }}</span>
                @if ($extraClassCount > 0)
                    <strong>+{{ $extraClassCount }} lớp</strong>
                @endif
            @else
                <i class="fa-solid fa-link-slash" aria-hidden="true"></i>Chưa gắn với lớp học
            @endif
        </div>

        <div class="catalog-card-footer">
            @if ($isManager)
                <span class="catalog-updated">
                    <i class="fa-regular fa-clock" aria-hidden="true"></i>{{ $course->updated_at->diffForHumans() }}
                </span>
            @endif

            @if ($isTemplate)
                <a href="{{ route('courses.create', ['template_course_id' => $course->id]) }}" class="catalog-card-action">
                    Dùng mẫu <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            @else
                <a href="{{ route('courses.show', $course) }}" class="catalog-card-action">
                    {{ $isManager ? 'Quản lý' : 'Vào khóa học' }}
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    </div>
</article>
