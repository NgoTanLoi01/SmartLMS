@extends('layouts.app')

@section('title', 'Hộp thư chờ chấm')

@section('content')
    <style>
        .grading-item {
            border-left: 4px solid transparent;
            transition: background .1s
        }

        .grading-item.urgency-overdue {
            border-left-color: #ef4444;
            background: #fef8f8
        }

        .grading-item.urgency-ready {
            border-left-color: #f59e0b
        }

        .grading-item:hover {
            background: #fafafa
        }

        .urgency-chip {
            border: 1px solid #dee2e6;
            background: #fff;
            border-radius: 999px;
            padding: .3rem .9rem;
            font-size: .85rem;
            font-weight: 600;
            color: #495057;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .35rem
        }

        .urgency-chip.active {
            border-color: transparent
        }

        .urgency-chip.active.chip-all {
            background: #0d6efd;
            color: #fff
        }

        .urgency-chip.active.chip-overdue {
            background: #dc3545;
            color: #fff
        }

        .urgency-chip.active.chip-ready {
            background: #f59e0b;
            color: #212529
        }

        .stat-tile {
            border: 1px solid #eee;
            border-radius: .5rem;
            padding: .75rem 1rem;
            flex: 1;
            min-width: 130px
        }

        .stat-tile .num {
            font-size: 1.4rem;
            font-weight: 700;
            line-height: 1
        }
    </style>

    <div class="lms-page">
        <x-ui.page-header title="Hộp thư chờ chấm">
            <x-slot:meta><span><i class="fa-solid fa-inbox" aria-hidden="true"></i> Bài cần xử lý xuyên nhiều
                    lớp</span></x-slot:meta>
        </x-ui.page-header>

        {{-- At-a-glance summary --}}
        <div class="d-flex flex-wrap gap-2 mb-3">
            <div class="stat-tile bg-white">
                <div class="num">{{ $items->total() }}</div>
                <div class="text-muted small">Tổng số bài chờ</div>
            </div>
            <div class="stat-tile" style="background:#fef2f2;border-color:#fecaca">
                <div class="num text-danger">{{ $overdueCount ?? '—' }}</div>
                <div class="text-muted small">Chờ quá 7 ngày</div>
            </div>
            <div class="stat-tile" style="background:#fffbeb;border-color:#fde68a">
                <div class="num" style="color:#b45309">{{ $readyCount ?? '—' }}</div>
                <div class="text-muted small">Sẵn sàng chấm</div>
            </div>
        </div>

        {{-- Quick urgency chips — fast one-click filter, dropdown below still covers full filter set --}}
        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="{{ route('grading.inbox', array_merge(request()->except(['urgency', 'page']))) }}"
                class="urgency-chip chip-all {{ !request('urgency') ? 'active' : '' }}">Tất cả</a>
            <a href="{{ route('grading.inbox', array_merge(request()->except('page'), ['urgency' => 'overdue'])) }}"
                class="urgency-chip chip-overdue {{ request('urgency') === 'overdue' ? 'active' : '' }}">
                <i class="fa-solid fa-clock" aria-hidden="true"></i> Chờ quá 7 ngày
            </a>
            <a href="{{ route('grading.inbox', array_merge(request()->except('page'), ['urgency' => 'ready'])) }}"
                class="urgency-chip chip-ready {{ request('urgency') === 'ready' ? 'active' : '' }}">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Sẵn sàng chấm
            </a>
        </div>

        <form method="GET" action="{{ route('grading.inbox') }}" class="card border-0 shadow-sm mb-3" role="search">
            <div class="card-body row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label for="work-q" class="form-label">Tìm bài hoặc học viên</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"
                                aria-hidden="true"></i></span>
                        <input id="work-q" name="q" value="{{ request('q') }}" class="form-control"
                            type="search" placeholder="Tên bài, tên học viên…">
                    </div>
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

                @if (request()->only(['q', 'type', 'urgency', 'course_id']) !==
                        array_fill_keys(['q', 'type', 'urgency', 'course_id'], null) &&
                        request()->anyFilled(['q', 'type', 'urgency', 'course_id']))
                    <div class="col-12">
                        <a href="{{ route('grading.inbox') }}" class="small text-muted"><i class="fa-solid fa-xmark"
                                aria-hidden="true"></i> Xóa tất cả bộ lọc</a>
                    </div>
                @endif
            </div>
        </form>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Bài sẵn sàng chấm</strong>
                <span class="badge text-bg-danger">{{ $items->total() }}</span>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($items as $item)
                    <article class="list-group-item grading-item py-3 urgency-{{ $item->urgency }}">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                            <div class="min-w-0">
                                <div class="d-flex flex-wrap gap-2 mb-1">
                                    <span
                                        class="badge {{ $item->urgency === 'overdue' ? 'text-bg-danger' : 'text-bg-warning' }}">
                                        {{ $item->urgency === 'overdue' ? 'Chờ quá 7 ngày' : 'Sẵn sàng chấm' }}
                                    </span>
                                    <span
                                        class="badge text-bg-light border">{{ $item->type === 'assignment' ? 'Bài tập' : 'Bài kiểm tra · lượt ' . $item->attempt_number }}</span>
                                </div>
                                <h2 class="h6 mb-1">{{ $item->title }}</h2>
                                <div class="text-muted small">
                                    <i class="fa-solid fa-user" aria-hidden="true"></i> {{ $item->student_name }}
                                    · {{ $item->course_title }}@if ($item->class_name)
                                        · {{ $item->class_name }}
                                    @endif
                                </div>
                                <div class="small mt-1 {{ $item->urgency === 'overdue' ? 'text-danger' : 'text-muted' }}">
                                    {{ $item->reason_label }} · chờ từ
                                    {{ \Carbon\Carbon::parse($item->queued_at)->diffForHumans() }}
                                </div>
                            </div>
                            <div class="align-self-md-center">
                                <a href="{{ $item->action_url }}" class="btn btn-primary text-nowrap">Chấm ngay <i
                                        class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                            </div>
                        </div>
                    </article>
                    @empty
                        <x-ui.empty-state icon="fa-circle-check" title="Không còn bài chờ chấm"
                            description="Các bài mới nộp sẽ xuất hiện tại đây." />
                    @endforelse
                </div>
            </div>
            <div class="mt-3">{{ $items->links() }}</div>
        </div>
    @endsection
