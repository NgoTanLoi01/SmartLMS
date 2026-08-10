@extends('layouts.app')

@section('title', 'Sổ điểm · ' . $course->title)

@section('content')
    <style>
        :root {
            --status-graded-bg: #ecfdf5;
            --status-graded-bd: #10b981;
            --status-missing-bg: #fef2f2;
            --status-missing-bd: #ef4444;
            --status-excused-bg: #eff6ff;
            --status-excused-bd: #3b82f6;
            --status-excluded-bg: #f3f4f6;
            --status-excluded-bd: #9ca3af;
            --status-ungraded-bg: #fff;
            --status-ungraded-bd: #e5e7eb;
        }

        .gradebook-table {
            border-collapse: separate;
            border-spacing: 0
        }

        .gradebook-table th:first-child,
        .gradebook-table td:first-child {
            position: sticky;
            left: 0;
            z-index: 2;
            background: #fff;
            min-width: 200px;
            box-shadow: 2px 0 4px -2px rgba(0, 0, 0, .08)
        }

        .gradebook-table thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            background: #f8fafc;
            vertical-align: top;
            padding-top: .6rem
        }

        .gradebook-table thead th:first-child {
            z-index: 4
        }

        .gradebook-table tbody tr:hover td {
            background: #fafafa
        }

        .gradebook-table tbody tr:hover td:first-child {
            background: #f5f5f5
        }

        .gradebook-cell {
            min-width: 190px
        }

        .gradebook-mobile {
            display: none
        }

        .grade-status-dot {
            display: inline-block;
            width: .55rem;
            height: .55rem;
            border-radius: 50%;
            margin-right: .35rem;
            vertical-align: middle
        }

        .grade-status-dot.graded {
            background: var(--status-graded-bd)
        }

        .grade-status-dot.missing {
            background: var(--status-missing-bd)
        }

        .grade-status-dot.excused {
            background: var(--status-excused-bd)
        }

        .grade-status-dot.excluded {
            background: var(--status-excluded-bd)
        }

        .grade-status-dot.ungraded {
            background: var(--status-ungraded-bd)
        }

        .formula-chip {
            display: inline-flex;
            align-items: center;
            background: #fff;
            border: 1px solid #dbeafe;
            color: #1d4ed8;
            border-radius: 999px;
            padding: .15rem .65rem;
            font-size: .8rem;
            font-weight: 600;
            margin: .15rem
        }

        .formula-plus {
            color: #94a3b8;
            margin: 0 .1rem;
            font-weight: 400
        }

        .item-header-actions {
            opacity: .55;
            transition: opacity .15s
        }

        .item-header-actions:hover,
        .item-header-actions:focus-within {
            opacity: 1
        }

        .avg-pill {
            font-size: 1.05rem;
            font-weight: 700
        }

        @media(max-width:767.98px) {
            .gradebook-desktop {
                display: none
            }

            .gradebook-mobile {
                display: block
            }
        }
    </style>
    <div class="lms-page">
        <x-ui.page-header title="Sổ điểm chính quy">
            <x-slot:meta><span><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>
                    {{ $course->title }}</span></x-slot:meta>
        </x-ui.page-header>

        @if ($periods->isEmpty())
            <div class="alert alert-info" role="status">
                <strong>Chưa thiết lập kỳ điểm cho khóa học này.</strong>
                Sổ điểm chính quy chỉ mở nhập và chốt điểm sau khi thành phần, trọng số và công thức tính điểm được duyệt.
                Dữ liệu điểm hiện tại vẫn được giữ nguyên trong bảng Điểm danh.
                <div class="mt-2 d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-primary" href="{{ route('gradebook.setup.create', $course) }}">Thiết lập Sổ
                        điểm</a>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('attendance.show', $course) }}">Mở bảng điểm
                        hiện tại</a>
                </div>
            </div>
        @else
            {{-- Toolbar: period switch + actions --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-sm btn-outline-primary"
                                href="{{ route('gradebook.setup.create', $course) }}"><i class="fa-solid fa-plus"
                                    aria-hidden="true"></i> Tạo kỳ điểm mới</a>
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('gradebook.history', $period) }}"><i
                                    class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Lịch sử thay đổi</a>
                        </div>
                        @if ($period->status === 'open')
                            <form method="POST" action="{{ route('gradebook.periods.close', $period) }}"
                                onsubmit="return confirm('Chỉ đóng kỳ khi mọi học viên đã chốt điểm. Tiếp tục?')">
                                @csrf<button class="btn btn-sm btn-outline-danger" type="submit"><i
                                        class="fa-solid fa-lock" aria-hidden="true"></i> Đóng toàn bộ kỳ</button>
                            </form>
                        @elseif($period->status === 'closed')
                            <form method="POST" action="{{ route('gradebook.periods.reopen', $period) }}"
                                class="d-flex gap-1">
                                @csrf
                                <input class="form-control form-control-sm" name="reason" required maxlength="2000"
                                    placeholder="Lý do mở lại kỳ" style="min-width:220px">
                                <button class="btn btn-sm btn-outline-warning text-nowrap" type="submit">Mở lại kỳ</button>
                            </form>
                        @endif
                    </div>

                    <form method="GET" class="d-flex flex-column flex-md-row align-items-md-end gap-3 mb-3">
                        <div class="flex-grow-1" style="max-width:320px">
                            <label for="gradebook-period" class="form-label mb-1">Kỳ điểm</label>
                            <select id="gradebook-period" name="period_id" class="form-select"
                                onchange="this.form.submit()">
                                @foreach ($periods as $availablePeriod)
                                    <option value="{{ $availablePeriod->id }}" @selected($period->id === $availablePeriod->id)>
                                        {{ $availablePeriod->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <span
                            class="badge {{ $period->status === 'open' ? 'text-bg-success' : ($period->status === 'closed' ? 'text-bg-secondary' : 'text-bg-warning') }} py-2 px-3">
                            {{ ['open' => '● Đang mở', 'closed' => '● Đã đóng', 'draft' => '● Bản nháp'][$period->status] ?? $period->status }}
                        </span>
                        <div class="flex-grow-1 ms-md-auto" style="max-width:260px">
                            <label for="gradebook-search" class="form-label mb-1">Tìm học viên</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"
                                        aria-hidden="true"></i></span>
                                <input type="search" id="gradebook-search" class="form-control"
                                    placeholder="Tên hoặc email…" data-gradebook-search autocomplete="off">
                            </div>
                        </div>
                    </form>

                    {{-- Formula as scannable chips instead of a run-on sentence --}}
                    <div class="d-flex flex-wrap align-items-center border rounded p-2 bg-light-subtle">
                        <span class="text-muted small me-2">Công thức:</span>
                        @foreach ($period->categories as $category)
                            <span class="formula-chip">{{ $category->name }} ·
                                {{ rtrim(rtrim($category->weight_percent, '0'), '.') }}%</span>
                            @unless ($loop->last)
                                <span class="formula-plus">+</span>
                            @endunless
                        @endforeach
                        <span class="text-muted small ms-3">
                            Điểm thiếu:
                            <strong>{{ ['block' => 'không cho chốt', 'exclude' => 'không tính', 'zero' => 'tính 0'][$period->missing_policy] ?? $period->missing_policy }}</strong>
                            · Làm tròn {{ $period->rounding_precision }} chữ số
                        </span>
                    </div>

                    {{-- Status color legend --}}
                    <div class="d-flex flex-wrap gap-3 small text-muted mt-2">
                        <span><span class="grade-status-dot graded"></span>Có điểm</span>
                        <span><span class="grade-status-dot missing"></span>Thiếu điểm</span>
                        <span><span class="grade-status-dot excused"></span>Được miễn</span>
                        <span><span class="grade-status-dot excluded"></span>Không tính</span>
                        <span><span class="grade-status-dot ungraded"></span>Chưa chấm</span>
                    </div>
                </div>
            </div>

            <div class="gradebook-desktop card border-0 shadow-sm mb-3">
                <div class="table-responsive" style="max-height:75vh">
                    <table class="table align-middle mb-0 gradebook-table">
                        <thead>
                            <tr>
                                <th>Học viên</th>
                                @foreach ($period->categories as $category)
                                    @foreach ($category->items as $item)
                                        <th>
                                            <span class="d-block">{{ $item->name }}</span>
                                            <small class="text-muted d-block">{{ $category->name }} ·
                                                {{ $item->item_weight }}× · /{{ $item->max_points }}</small>
                                            <small
                                                class="text-muted d-block mb-1">{{ ['legacy_attendance' => 'Điểm danh', 'assignment' => 'Bài tập', 'quiz' => 'Quiz', 'manual' => 'Nhập tay'][$item->source_type] ?? $item->source_type }}</small>
                                            @if ($period->status !== 'closed')
                                                <form method="POST"
                                                    action="{{ $item->is_locked ? route('gradebook.items.unlock', [$period, $item]) : route('gradebook.items.lock', [$period, $item]) }}"
                                                    class="item-header-actions">
                                                    @csrf
                                                    <input type="hidden" name="expected_version"
                                                        value="{{ $item->version }}">
                                                    <button
                                                        class="btn btn-sm {{ $item->is_locked ? 'btn-outline-warning' : 'btn-outline-secondary' }} px-2 py-0"
                                                        type="submit"
                                                        title="{{ $item->is_locked ? 'Mở khóa thành phần' : 'Khóa thành phần' }}">
                                                        <i class="fa-solid {{ $item->is_locked ? 'fa-lock' : 'fa-lock-open' }}"
                                                            aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="badge text-bg-secondary"><i class="fa-solid fa-lock"
                                                        aria-hidden="true"></i> Đã khóa</span>
                                            @endif
                                        </th>
                                    @endforeach
                                @endforeach
                                <th>Trung bình</th>
                                <th>Chốt điểm</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                                @php($student = $row['student'])
                                <tr data-gradebook-row data-search="{{ Str::lower($student->name . ' ' . $student->email) }}">
                                    <td><strong>{{ $student->name }}</strong>
                                        <div class="small text-muted">{{ $student->email }}</div>
                                    </td>
                                    @foreach ($period->categories as $category)
                                        @foreach ($category->items as $item)
                                            <td>@include(
                                                'gradebook.partials.grade-cell',
                                                compact('period', 'row', 'student', 'category', 'item'))</td>
                                        @endforeach
                                    @endforeach
                                    <td>
                                        @if ($row['calculation'])
                                            <span class="avg-pill">{{ $row['calculation']['final_score'] }}</span>
                                            <div class="small text-muted">Chưa làm tròn:
                                                {{ $row['calculation']['unrounded_score'] }}</div>
                                        @else
                                            <span class="text-warning" title="{{ $row['calculation_error'] }}"><i
                                                    class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Chưa
                                                đủ điểm</span>
                                        @endif
                                    </td>
                                    <td style="min-width:170px">
                                        @if ($row['finalization']?->state === 'finalized')
                                            <span class="badge text-bg-success mb-1"><i class="fa-solid fa-check"
                                                    aria-hidden="true"></i> Đã chốt ·
                                                {{ $row['finalization']->final_score }}</span>
                                            @if ($period->status === 'open')
                                                <form method="POST"
                                                    action="{{ route('gradebook.reopen', [$period, $student]) }}">
                                                    @csrf
                                                    <input class="form-control form-control-sm mb-1" name="reason"
                                                        required maxlength="2000" placeholder="Lý do mở lại">
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Mở lại
                                                        điểm</button>
                                                </form>
                                            @endif
                                        @else
                                            <form method="POST"
                                                action="{{ route('gradebook.finalize', [$period, $student]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-success" type="submit"
                                                    @disabled(!$row['calculation'] || $period->status !== 'open')>Chốt điểm</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="99"><x-ui.empty-state icon="fa-users" title="Chưa có học viên"
                                            description="Khóa học chưa có học viên trong lớp được gán." /></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div id="gradebook-no-results" class="text-center text-muted py-4 d-none">Không tìm thấy học viên phù hợp.
                </div>
            </div>

            {{-- Mobile: student card with average up top, categories collapsible --}}
            <div class="gradebook-mobile">
                @foreach ($rows as $row)
                    @php($student = $row['student'])
                    <article class="card border-0 shadow-sm mb-3" data-gradebook-row
                        data-search="{{ Str::lower($student->name . ' ' . $student->email) }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h2 class="h6 mb-0">{{ $student->name }}</h2>
                                    <div class="small text-muted">{{ $student->email }}</div>
                                </div>
                                <div class="text-end">
                                    @if ($row['calculation'])
                                        <div class="avg-pill">{{ $row['calculation']['final_score'] }}</div>
                                    @else
                                        <span class="badge text-bg-light border">Chưa đủ điểm</span>
                                    @endif
                                </div>
                            </div>

                            @foreach ($period->categories as $category)
                                <details class="border rounded mb-2">
                                    <summary class="p-2 fw-semibold">{{ $category->name }} ·
                                        {{ rtrim(rtrim($category->weight_percent, '0'), '.') }}%</summary>
                                    <div class="p-2 pt-0">
                                        @foreach ($category->items as $item)
                                            <div class="border-top py-2">
                                                <div class="mb-1 text-muted small">{{ $item->name }}</div>
                                                @include('gradebook.partials.grade-cell', [
                                                    'period' => $period,
                                                    'row' => $row,
                                                    'student' => $student,
                                                    'category' => $category,
                                                    'item' => $item,
                                                ])
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @endforeach

                            <div class="pt-2 border-top">
                                @if ($row['finalization']?->state === 'finalized')
                                    <span class="badge text-bg-success"><i class="fa-solid fa-check"
                                            aria-hidden="true"></i> Đã chốt ·
                                        {{ $row['finalization']->final_score }}</span>
                                    @if ($period->status === 'open')
                                        <form method="POST"
                                            action="{{ route('gradebook.reopen', [$period, $student]) }}" class="mt-2">
                                            @csrf
                                            <input class="form-control form-control-sm mb-1" name="reason" required
                                                maxlength="2000" placeholder="Lý do mở lại">
                                            <button class="btn btn-sm btn-outline-danger w-100" type="submit">Mở lại
                                                điểm</button>
                                        </form>
                                    @endif
                                @else
                                    <form method="POST" action="{{ route('gradebook.finalize', [$period, $student]) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-success w-100" type="submit"
                                            @disabled(!$row['calculation'] || $period->status !== 'open')>Chốt điểm</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($students)
                <div class="mt-3">{{ $students->links() }}</div>
            @endif
        @endif
    </div>

    @push('scripts')
        @vite('resources/js/pages/gradebook.js')
        <script>
            // Lightweight client-side filter — narrows currently-loaded rows only,
            // does not replace server-side pagination/search.
            document.getElementById('gradebook-search')?.addEventListener('input', function(e) {
                const term = e.target.value.trim().toLowerCase();
                const rows = document.querySelectorAll('[data-gradebook-row]');
                let visibleCount = 0;
                rows.forEach(function(row) {
                    const match = !term || row.dataset.search.includes(term);
                    row.classList.toggle('d-none', !match);
                    if (match) visibleCount++;
                });
                document.getElementById('gradebook-no-results')?.classList.toggle('d-none', visibleCount !== 0);
            });
        </script>
    @endpush
@endsection
