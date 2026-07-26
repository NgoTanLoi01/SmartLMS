@extends('layouts.app')

@section('title', 'Kho lưu trữ bài kiểm tra')

@push('styles')
    @vite('resources/css/pages/quiz-sessions.css')
@endpush

@section('content')
    <div class="exam-admin-page">
        <nav class="exam-breadcrumb" aria-label="Điều hướng">
            <a href="{{ route('courses.show', $course) }}">{{ $course->title }}</a>
            <i class="fa-solid fa-chevron-right"></i>
            <strong>Kho lưu trữ bài kiểm tra</strong>
        </nav>

        <header class="exam-hero">
            <div class="exam-hero-content">
                <div class="exam-eyebrow">Quản lý dữ liệu đã ẩn</div>
                <h1>Kho lưu trữ bài kiểm tra</h1>
                <div class="exam-hero-meta">
                    <span><i class="fa-solid fa-book"></i>{{ $course->title }}</span>
                    <span><i class="fa-solid fa-box-archive"></i>{{ $archivedQuizzes->count() }} bài đã lưu trữ</span>
                </div>
            </div>
            <div class="exam-hero-actions">
                <a href="{{ route('courses.show', $course) }}" class="exam-action exam-action-primary">
                    <i class="fa-solid fa-arrow-left"></i> Quay lại khóa học
                </a>
            </div>
        </header>

        <div class="exam-alert" style="border-color:var(--sl-info-border);background:var(--sl-info-soft);color:#075985">
            <i class="fa-solid fa-circle-info"></i>
            <div>
                Bài lưu trữ không xuất hiện với học viên nhưng vẫn giữ cấu hình và kết quả. Bạn có thể xóa vĩnh viễn bài tạo sai nếu chưa có học viên làm bài.
            </div>
        </div>

        <div class="exam-section-heading">
            <div><h2>Các bài đã lưu trữ</h2><p>Khôi phục về bản nháp hoặc xóa vĩnh viễn khỏi database.</p></div>
        </div>

        <div class="session-grid">
            @forelse($archivedQuizzes as $quiz)
                <article class="session-card" data-state="ended">
                    <div class="session-card-accent"></div>
                    <div class="session-card-body">
                        <div class="session-card-top">
                            <div>
                                <span class="session-state ended">Đã lưu trữ</span>
                                <h3>{{ $quiz->title }}</h3>
                            </div>
                            <span class="session-info-chip"><i class="fa-regular fa-clock"></i>{{ $quiz->time_limit }} phút</span>
                        </div>

                        <div class="session-info-row mt-3">
                            <span class="session-info-chip"><i class="fa-solid fa-calendar-days"></i>{{ $quiz->sessions_count }} ca thi</span>
                            <span class="session-info-chip" style="color:{{ $quiz->attempts_count > 0 ? 'var(--sl-danger)' : 'var(--sl-success)' }}">
                                <i class="fa-solid fa-file-pen"></i>{{ $quiz->attempts_count }} bài làm
                            </span>
                            <span class="session-info-chip"><i class="fa-regular fa-calendar"></i>Lưu trữ {{ $quiz->updated_at->format('d/m/Y H:i') }}</span>
                        </div>

                        @if($quiz->attempts_count > 0)
                            <div class="exam-alert mb-0" style="padding:10px 12px">
                                <i class="fa-solid fa-lock"></i>
                                <span>Không thể xóa vì đang lưu {{ $quiz->attempts_count }} kết quả học viên.</span>
                            </div>
                        @else
                            <div class="session-progress"><span style="width:100%;background:var(--sl-success)"></span></div>
                            <div class="session-progress-label"><span>Không có bài làm</span><span>Có thể xóa an toàn</span></div>
                        @endif

                        <div class="session-actions">
                            <form method="POST" action="{{ route('quizzes.restore', $quiz->id) }}">
                                @csrf @method('PATCH')
                                <button class="exam-action exam-action-success" type="submit">
                                    <i class="fa-solid fa-rotate-left"></i> Khôi phục về bản nháp
                                </button>
                            </form>

                            @if($quiz->attempts_count === 0)
                                <form method="POST" action="{{ route('quizzes.force-destroy', $quiz->id) }}"
                                    class="permanent-delete-form" data-quiz-title="{{ $quiz->title }}">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="confirmation" value="">
                                    <button class="exam-action exam-action-danger" type="submit">
                                        <i class="fa-solid fa-trash-can"></i> Xóa vĩnh viễn
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="exam-empty" style="grid-column:1/-1">
                    <div class="exam-empty-icon"><i class="fa-solid fa-box-open"></i></div>
                    <h3>Kho lưu trữ đang trống</h3>
                    <p>Không có bài kiểm tra nào đang được lưu trữ trong khóa học này.</p>
                </div>
            @endforelse
        </div>
    </div>

    <script>
        document.querySelectorAll('.permanent-delete-form').forEach(form => {
            form.addEventListener('submit', event => {
                event.preventDefault();
                const title = form.dataset.quizTitle;
                const confirmation = window.prompt(`Xóa vĩnh viễn sẽ không thể khôi phục.\nNhập chính xác tên bài kiểm tra để xác nhận:\n${title}`);
                if (confirmation === null) return;
                if (confirmation !== title) {
                    window.alert('Tên xác nhận không chính xác. Bài kiểm tra chưa bị xóa.');
                    return;
                }
                form.querySelector('[name="confirmation"]').value = confirmation;
                form.submit();
            });
        });
    </script>
@endsection
