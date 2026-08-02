@extends('layouts.app')

@section('title', auth()->user()->role === 'student' ? 'Khóa học của bạn' : 'Quản lý khóa học')

@push('styles')
    @vite('resources/css/pages/catalog-index.css')
@endpush

@section('content')
    @php
        $isStudent = auth()->user()->role === 'student';
        $hasFilters = collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty();
    @endphp

    <div class="lms-page catalog-page courses-catalog-page">
        <x-ui.page-header :title="$isStudent ? 'Khóa học của bạn' : 'Quản lý khóa học'">
            <x-slot:meta>
                <span>
                    <i class="fa-solid {{ $isStudent ? 'fa-book-open-reader' : 'fa-graduation-cap' }}" aria-hidden="true"></i>
                    {{ $isStudent
                        ? 'Mở bài học để xem lại nội dung đã học trên lớp'
                        : 'Quản lý khóa triển khai và nội dung mẫu trong một nơi' }}
                </span>
            </x-slot:meta>

            @unless ($isStudent)
                <x-slot:actions>
                    <x-ui.button :href="route('courses.create')" icon="fa-plus">Tạo khóa học</x-ui.button>
                </x-slot:actions>
            @endunless
        </x-ui.page-header>

        <section class="catalog-summary {{ $isStudent ? 'catalog-summary-three' : '' }}" aria-label="Tổng quan khóa học">
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
        </section>

        @unless ($isStudent)
            <form action="{{ route('courses.index') }}" method="GET" class="catalog-filter-panel"
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
            @if ($deliveryCourses->isNotEmpty())
                <section class="catalog-section" aria-labelledby="delivery-courses-title">
                    <header class="catalog-section-header">
                        <div>
                            <span class="catalog-section-kicker">{{ $isStudent ? 'Nội dung của lớp' : 'Đang vận hành' }}</span>
                            <h2 id="delivery-courses-title">{{ $isStudent ? 'Khóa học đang tham gia' : 'Khóa triển khai' }}</h2>
                            <p>{{ $isStudent ? 'Chọn một khóa để xem lại chương và bài học.' : 'Các khóa đang được sử dụng cho lớp học thực tế.' }}</p>
                        </div>
                        <span class="catalog-count">{{ $deliveryCourses->count() }} khóa</span>
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
                        <span class="catalog-count">{{ $templateCourses->count() }} mẫu</span>
                    </header>
                    <div class="catalog-grid">
                        @foreach ($templateCourses as $course)
                            @include('courses.partials.course-card', ['course' => $course])
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
    </div>
@endsection
