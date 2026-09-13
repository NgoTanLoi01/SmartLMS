@extends('layouts.app')

@section('title', auth()->user()->role === 'student' ? 'Khóa học của bạn' : 'Quản lý khóa học')

@push('styles')
    @vite('resources/css/pages/catalog-index.css')
@endpush

@section('content')
    @php
        $isStudent = auth()->user()->role === 'student';
        $hasFilters = collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty();
        $activeFilters = collect([
            filled($filters['search'] ?? null) ? ['label' => 'Từ khóa', 'value' => $filters['search']] : null,
            filled($filters['program_id'] ?? null) ? [
                'label' => 'Chương trình',
                'value' => $filterPrograms->firstWhere('id', $filters['program_id'])?->name,
            ] : null,
            filled($filters['course_type'] ?? null) ? [
                'label' => 'Loại khóa',
                'value' => ($filters['course_type'] ?? null) === 'template' ? 'Khóa mẫu' : 'Khóa triển khai',
            ] : null,
            filled($filters['status'] ?? null) ? [
                'label' => 'Trạng thái',
                'value' => [
                    'published' => 'Đã xuất bản',
                    'draft' => 'Bản nháp',
                    'hidden' => 'Tạm ẩn',
                    'archived' => 'Đã lưu trữ',
                ][$filters['status']] ?? $filters['status'],
            ] : null,
            filled($filters['class_id'] ?? null) ? [
                'label' => 'Lớp học',
                'value' => $filterClasses->firstWhere('id', $filters['class_id'])?->name,
            ] : null,
        ])->filter(fn ($filter) => filled($filter['value'] ?? null));
    @endphp

    <div class="lms-page catalog-page courses-catalog-page">
        <section class="catalog-hero">
            <span class="catalog-hero__accent" aria-hidden="true"></span>
            <x-ui.page-header :title="$isStudent ? 'Khóa học của bạn' : 'Quản lý khóa học'">
                <x-slot:meta>
                    <span>
                        <i class="fa-solid {{ $isStudent ? 'fa-book-open-reader' : 'fa-graduation-cap' }}" aria-hidden="true"></i>
                        {{ $isStudent
                            ? 'Mở bài học để xem lại nội dung đã học trên lớp'
                            : 'Theo dõi khóa triển khai, khóa mẫu và nội dung giảng dạy tại một nơi' }}
                    </span>
                </x-slot:meta>

                @unless ($isStudent)
                    <x-slot:actions>
                        <x-ui.button :href="route('trash.index')" tone="outline" icon="fa-box-archive">Thùng rác</x-ui.button>
                        <x-ui.button :href="route('courses.create')" icon="fa-plus">Tạo khóa học</x-ui.button>
                    </x-slot:actions>
                @endunless
            </x-ui.page-header>

            <div class="catalog-summary {{ $isStudent ? 'catalog-summary-three' : '' }}" aria-label="Tổng quan khóa học">
                <article class="catalog-summary-item">
                    <span class="catalog-summary-icon tone-blue"><i class="fa-solid fa-book-open" aria-hidden="true"></i></span>
                    <span><strong>{{ $courseStats['total'] }}</strong><small>Tổng khóa học</small></span>
                </article>
                <article class="catalog-summary-item">
                    <span class="catalog-summary-icon tone-green"><i class="fa-solid fa-chalkboard-user" aria-hidden="true"></i></span>
                    <span><strong>{{ $courseStats['delivery'] }}</strong><small>{{ $isStudent ? 'Đang tham gia' : 'Khóa triển khai' }}</small></span>
                </article>
                @unless ($isStudent)
                    <article class="catalog-summary-item">
                        <span class="catalog-summary-icon tone-violet"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></span>
                        <span><strong>{{ $courseStats['templates'] }}</strong><small>Khóa mẫu</small></span>
                    </article>
                @endunless
                <article class="catalog-summary-item">
                    <span class="catalog-summary-icon tone-amber"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span>
                    <span><strong>{{ $courseStats['lessons'] }}</strong><small>Bài học để xem lại</small></span>
                </article>
            </div>
        </section>

        @unless ($isStudent)
            <section class="course-filter-shell" aria-labelledby="course-filter-title">
                <header class="course-filter-heading">
                    <div>
                        <span class="course-filter-icon" aria-hidden="true"><i class="fa-solid fa-sliders"></i></span>
                        <span>
                            <strong id="course-filter-title">Tìm và lọc khóa học</strong>
                            <small>Thu hẹp danh sách theo chương trình, loại khóa, trạng thái hoặc lớp</small>
                        </span>
                    </div>
                    <span class="course-filter-result">{{ $courses->total() }} kết quả</span>
                </header>

                <form action="{{ route('courses.index') }}" method="GET" class="catalog-filter-panel course-filter-panel"
                    aria-label="Bộ lọc khóa học">
                    <div class="catalog-search-field">
                        <label for="course-search">Tìm kiếm</label>
                        <div class="catalog-input-with-icon">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                            <input id="course-search" type="search" name="search" class="form-control"
                                placeholder="Tên hoặc mô tả khóa học" value="{{ $filters['search'] ?? '' }}">
                        </div>
                    </div>

                    <div class="catalog-filter-field">
                        <label for="course-program">Chương trình</label>
                        <select id="course-program" name="program_id" class="form-select">
                            <option value="">Tất cả chương trình</option>
                            @foreach ($filterPrograms as $program)
                                <option value="{{ $program->id }}" @selected(($filters['program_id'] ?? '') == $program->id)>
                                    {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="catalog-filter-field">
                        <label for="course-type">Loại khóa</label>
                        <select id="course-type" name="course_type" class="form-select">
                            <option value="">Tất cả loại khóa</option>
                            <option value="delivery" @selected(($filters['course_type'] ?? '') === 'delivery')>Khóa triển khai</option>
                            <option value="template" @selected(($filters['course_type'] ?? '') === 'template')>Khóa mẫu</option>
                        </select>
                    </div>

                    <div class="catalog-filter-field">
                        <label for="course-status">Trạng thái</label>
                        <select id="course-status" name="status" class="form-select">
                            <option value="">Tất cả trạng thái</option>
                            <option value="published" @selected(($filters['status'] ?? '') === 'published')>Đã xuất bản</option>
                            <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Bản nháp</option>
                            <option value="hidden" @selected(($filters['status'] ?? '') === 'hidden')>Tạm ẩn</option>
                            <option value="archived" @selected(($filters['status'] ?? '') === 'archived')>Đã lưu trữ</option>
                        </select>
                    </div>

                    <div class="catalog-filter-field">
                        <label for="course-class">Lớp học</label>
                        <select id="course-class" name="class_id" class="form-select">
                            <option value="">Tất cả lớp học</option>
                            @foreach ($filterClasses as $classroom)
                                <option value="{{ $classroom->id }}" @selected(($filters['class_id'] ?? '') == $classroom->id)>
                                    {{ $classroom->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="catalog-filter-actions">
                        <x-ui.button type="submit" icon="fa-filter">Áp dụng</x-ui.button>
                        @if ($hasFilters)
                            <x-ui.button :href="route('courses.index')" tone="outline" icon="fa-rotate-left">Đặt lại</x-ui.button>
                        @endif
                    </div>
                </form>

                @if ($activeFilters->isNotEmpty())
                    <div class="course-active-filters" aria-label="Bộ lọc đang áp dụng">
                        <span class="course-active-filters__label">Đang lọc:</span>
                        @foreach ($activeFilters as $filter)
                            <span class="course-filter-chip">
                                <small>{{ $filter['label'] }}</small>{{ $filter['value'] }}
                            </span>
                        @endforeach
                        <a href="{{ route('courses.index') }}">Xóa tất cả</a>
                    </div>
                @endif
            </section>
        @endunless

        @if ($courses->isEmpty())
            <div class="catalog-empty-panel">
                <x-ui.empty-state
                    :title="$hasFilters ? 'Không tìm thấy khóa học phù hợp' : 'Chưa có khóa học nào'"
                    :description="$hasFilters
                        ? 'Hãy thay đổi từ khóa hoặc bộ lọc để xem thêm kết quả.'
                        : ($isStudent ? 'Khi giáo viên gắn khóa học với lớp, khóa học sẽ xuất hiện tại đây.' : 'Tạo khóa học đầu tiên để bắt đầu xây dựng nội dung.')"
                    icon="fa-graduation-cap">
                    @if ($hasFilters)
                        <x-ui.button :href="route('courses.index')" tone="outline" size="sm" icon="fa-rotate-left">Xóa bộ lọc</x-ui.button>
                    @elseif (! $isStudent)
                        <x-ui.button :href="route('courses.create')" size="sm" icon="fa-plus">Tạo khóa học</x-ui.button>
                    @endif
                </x-ui.empty-state>
            </div>
        @else
            <div class="course-results-bar" aria-label="Kết quả khóa học">
                <span>
                    <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
                    Hiển thị <strong>{{ $courses->firstItem() }}–{{ $courses->lastItem() }}</strong>
                    trong <strong>{{ $courses->total() }}</strong> khóa học
                </span>
                <small>Sắp xếp theo cập nhật mới nhất</small>
            </div>

            @if ($deliveryCourses->isNotEmpty())
                <section class="catalog-section" aria-labelledby="delivery-courses-title">
                    <header class="catalog-section-header">
                        <div>
                            <span class="catalog-section-kicker">{{ $isStudent ? 'Nội dung của lớp' : 'Đang vận hành' }}</span>
                            <h2 id="delivery-courses-title">{{ $isStudent ? 'Khóa học đang tham gia' : 'Khóa triển khai' }}</h2>
                            <p>{{ $isStudent ? 'Chọn một khóa để xem lại chương và bài học.' : 'Các khóa đang được sử dụng cho lớp học thực tế.' }}</p>
                        </div>
                        <span class="catalog-count">{{ $courseStats['delivery'] }} khóa</span>
                    </header>
                    <div class="catalog-grid">
                        @foreach ($deliveryCourses as $course)
                            @include('courses.partials.course-card', ['course' => $course])
                        @endforeach
                    </div>
                </section>
            @endif

            @if (! $isStudent && $templateCourses->isNotEmpty())
                <section class="catalog-section" aria-labelledby="template-courses-title">
                    <header class="catalog-section-header">
                        <div>
                            <span class="catalog-section-kicker tone-violet-text">Thư viện nội dung</span>
                            <h2 id="template-courses-title">Khóa mẫu</h2>
                            <p>Nội dung chuẩn có thể tái sử dụng để tạo nhanh khóa triển khai.</p>
                        </div>
                        <span class="catalog-count">{{ $courseStats['templates'] }} mẫu</span>
                    </header>
                    <div class="catalog-grid">
                        @foreach ($templateCourses as $course)
                            @include('courses.partials.course-card', ['course' => $course])
                        @endforeach
                    </div>
                </section>
            @endif

            <x-ui.pagination :paginator="$courses" item-label="khóa học" />
        @endif
    </div>
@endsection
