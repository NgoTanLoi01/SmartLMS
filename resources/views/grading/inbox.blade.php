@extends('layouts.app')

@section('title', 'Hộp thư chờ chấm')

@section('content')
    <div class="lms-page">
        <x-ui.page-header title="Hộp thư chờ chấm">
            <x-slot:meta><span><i class="fa-solid fa-inbox" aria-hidden="true"></i> Bài cần xử lý xuyên nhiều lớp</span></x-slot:meta>
        </x-ui.page-header>

        <form method="GET" action="{{ route('grading.inbox') }}" class="card border-0 shadow-sm mb-3" role="search">
            <div class="card-body row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label for="work-q" class="form-label">Tìm bài hoặc học viên</label>
                    <input id="work-q" name="q" value="{{ request('q') }}" class="form-control" type="search">
                </div>
                <div class="col-6 col-lg-2">
                    <label for="work-type" class="form-label">Loại</label>
                    <select id="work-type" name="type" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="assignment" @selected(request('type') === 'assignment')>Bài tập</option>
                        <option value="quiz" @selected(request('type') === 'quiz')>Bài kiểm tra</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="work-urgency" class="form-label">Ưu tiên</label>
                    <select id="work-urgency" name="urgency" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="overdue" @selected(request('urgency') === 'overdue')>Chờ quá 7 ngày</option>
                        <option value="ready" @selected(request('urgency') === 'ready')>Sẵn sàng chấm</option>
                    </select>
                </div>
                <div class="col-12 col-lg-3">
                    <label for="work-course" class="form-label">Khóa học</label>
                    <select id="work-course" name="course_id" class="form-select">
                        <option value="">Tất cả khóa học</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" @selected((string) request('course_id') === (string) $course->id)>{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-1"><button class="btn btn-primary w-100" type="submit">Lọc</button></div>
            </div>
        </form>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Ready to Grade</strong><span class="badge text-bg-danger">{{ $items->total() }}</span>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($items as $item)
                    <article class="list-group-item py-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                            <div class="min-w-0">
                                <div class="d-flex flex-wrap gap-2 mb-1">
                                    <span class="badge {{ $item->urgency === 'overdue' ? 'text-bg-danger' : 'text-bg-warning' }}">{{ $item->urgency === 'overdue' ? 'Chờ quá 7 ngày' : 'Sẵn sàng chấm' }}</span>
                                    <span class="badge text-bg-light">{{ $item->type === 'assignment' ? 'Bài tập' : 'Bài kiểm tra · lượt '.$item->attempt_number }}</span>
                                </div>
                                <h2 class="h6 mb-1">{{ $item->title }}</h2>
                                <div class="text-muted small">{{ $item->student_name }} · {{ $item->course_title }}@if($item->class_name) · {{ $item->class_name }}@endif</div>
                                <div class="small mt-1">{{ $item->reason_label }} · chờ từ {{ \Carbon\Carbon::parse($item->queued_at)->diffForHumans() }}</div>
                            </div>
                            <div class="align-self-md-center"><a href="{{ $item->action_url }}" class="btn btn-primary">Chấm ngay <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
                        </div>
                    </article>
                @empty
                    <x-ui.empty-state icon="fa-circle-check" title="Không còn bài chờ chấm" description="Các bài mới nộp sẽ xuất hiện tại đây." />
                @endforelse
            </div>
        </div>
        <div class="mt-3">{{ $items->links() }}</div>
    </div>
@endsection
