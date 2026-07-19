@extends('layouts.app')

@section('title', 'Giảng dạy')

@section('content')
    @php
        $statuses = \App\Models\TeachingRecord::statuses();
        $statusClasses = [
            'teaching' => 'success',
            'completed' => 'primary',
            'paused' => 'warning',
            'cancelled' => 'secondary',
        ];
    @endphp

    <style>
        /* ── Design tokens ── */
        :root {
            --t-bg: var(--sl-bg);
            --t-surface: var(--sl-surface);
            --t-border: var(--sl-border);
            --t-ink: var(--sl-text);
            --t-muted: var(--sl-text-muted);
            --t-accent: var(--sl-primary);
            --t-radius: var(--sl-radius-sm);
            --t-shadow: var(--sl-shadow-sm);
        }

        /* ── Page shell ── */
        .tp {
            padding-bottom: 40px;
        }

        /* ── Header ── */
        .tp-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .tp-eyebrow {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--t-accent);
            margin-bottom: 4px;
        }

        .tp-title {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            color: var(--t-ink);
            letter-spacing: -.3px;
        }

        .tp-sub {
            margin: 4px 0 0;
            font-size: 13px;
            color: var(--t-muted);
        }

        /* ── Stat strip ── */
        .tp-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        @media (max-width: 767px) {
            .tp-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .tp-stat {
            background: var(--t-surface);
            border: 1px solid var(--t-border);
            border-radius: var(--t-radius);
            padding: 16px 18px;
            box-shadow: var(--t-shadow);
            position: relative;
            overflow: hidden;
        }

        .tp-stat::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            border-radius: var(--t-radius) var(--t-radius) 0 0;
        }

        .tp-stat--blue::before {
            background: #2563eb;
        }

        .tp-stat--green::before {
            background: #16a34a;
        }

        .tp-stat--amber::before {
            background: #d97706;
        }

        .tp-stat--sky::before {
            background: #0284c7;
        }

        .tp-stat__lbl {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--t-muted);
        }

        .tp-stat__val {
            font-size: 30px;
            font-weight: 800;
            line-height: 1.1;
            margin-top: 6px;
            color: var(--t-ink);
        }

        .tp-stat--blue .tp-stat__val {
            color: #2563eb;
        }

        .tp-stat--green .tp-stat__val {
            color: #16a34a;
        }

        .tp-stat--amber .tp-stat__val {
            color: #d97706;
        }

        .tp-stat--sky .tp-stat__val {
            color: #0284c7;
        }

        /* ── Filter bar ── */
        .tp-filter {
            background: var(--t-surface);
            border: 1px solid var(--t-border);
            border-radius: var(--t-radius);
            padding: 16px 20px;
            margin-bottom: 16px;
            box-shadow: var(--t-shadow);
        }

        .tp-filter .form-control,
        .tp-filter .form-select {
            font-size: 13px;
            border-color: var(--t-border);
            border-radius: 8px;
            background: #f8fafc;
            transition: border-color .15s, box-shadow .15s;
        }

        .tp-filter .form-control:focus,
        .tp-filter .form-select:focus {
            border-color: var(--t-accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
            background: #fff;
        }

        .tp-filter .form-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--t-muted);
            margin-bottom: 5px;
        }

        /* ── Table card ── */
        .tp-card {
            background: var(--t-surface);
            border: 1px solid var(--t-border);
            border-radius: var(--t-radius);
            box-shadow: var(--t-shadow);
            overflow: hidden;
        }

        .tp-table {
            margin: 0;
        }

        .tp-table thead th {
            background: #f8fafc;
            border-bottom: 1px solid var(--t-border);
            color: var(--t-muted);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            white-space: nowrap;
            padding: 12px 20px;
        }

        .tp-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background .12s;
        }

        .tp-table tbody tr:last-child {
            border-bottom: none;
        }

        .tp-table tbody tr:hover {
            background: #f8fafc;
        }

        .tp-table td {
            padding: 14px 20px;
            vertical-align: middle;
            font-size: 13.5px;
            color: var(--t-ink);
        }

        /* ── Cell helpers ── */
        .cell-subject {
            font-weight: 700;
            color: var(--t-ink);
        }

        .cell-note {
            font-size: 12px;
            color: var(--t-muted);
            margin-top: 2px;
        }

        /* ── Pill badges ── */
        .tp-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-radius: 999px;
            padding: 3px 10px;
            font-size: 11.5px;
            font-weight: 700;
            line-height: 1.6;
        }

        .tp-badge--term {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .tp-badge--success {
            background: #f0fdf4;
            color: #15803d;
        }

        .tp-badge--primary {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .tp-badge--warning {
            background: #fffbeb;
            color: #b45309;
        }

        .tp-badge--secondary {
            background: #f1f5f9;
            color: #475569;
        }

        .tp-badge--linked {
            background: #ecfdf5;
            color: #047857;
        }

        .tp-badge--unlinked {
            background: #f8fafc;
            color: #94a3b8;
            border: 1px dashed #cbd5e1;
        }

        /* ── Action buttons ── */
        .tp-actions {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
        }

        .tp-btn-edit,
        .tp-btn-del {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 8px;
            padding: 5px 11px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid;
            cursor: pointer;
            transition: background .12s, color .12s;
            background: transparent;
            text-decoration: none;
        }

        .tp-btn-edit {
            border-color: #bfdbfe;
            color: #2563eb;
        }

        .tp-btn-edit:hover {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .tp-btn-del {
            border-color: #fecaca;
            color: #dc2626;
        }

        .tp-btn-del:hover {
            background: #fef2f2;
            color: #b91c1c;
        }

        /* ── Empty state ── */
        .tp-empty {
            text-align: center;
            padding: 56px 24px;
            color: var(--t-muted);
        }

        .tp-empty-icon {
            width: 56px;
            height: 56px;
            background: #f1f5f9;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #94a3b8;
            margin-bottom: 16px;
        }

        .tp-empty p {
            margin: 0;
            font-size: 14px;
        }

        /* ── Header buttons ── */
        .btn-tp-import {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #fff;
            border: 1px solid var(--t-border);
            border-radius: 9px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            color: var(--t-ink);
            transition: border-color .15s, box-shadow .15s;
            cursor: pointer;
        }

        .btn-tp-import:hover {
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .07);
        }

        .btn-tp-add {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--t-accent);
            border: none;
            border-radius: 9px;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            transition: background .15s, box-shadow .15s;
            cursor: pointer;
        }

        .btn-tp-add:hover {
            background: #1d4ed8;
            box-shadow: 0 4px 12px rgba(37, 99, 235, .3);
        }

        /* ── Pagination spacing ── */
        .tp-pagination {
            padding: 14px 20px;
            border-top: 1px solid var(--t-border);
        }

        @media (max-width: 575px) {
            .tp-actions {
                justify-content: flex-start;
            }

            .tp-header-actions {
                width: 100%;
            }

            .btn-tp-import,
            .btn-tp-add {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <div class="tp">

        {{-- Header --}}
        <div class="tp-header">
            <div>
                <div class="tp-eyebrow">Quản lý</div>
                <h1 class="tp-title">Giảng dạy</h1>
                <p class="tp-sub">Theo dõi môn đã dạy, số buổi, trung tâm, khóa và trạng thái.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap tp-header-actions">
                <button type="button" class="btn-tp-import" data-bs-toggle="modal" data-bs-target="#importTeachingModal">
                    <i class="fa-solid fa-file-import"></i>Import Excel/CSV
                </button>
                <button type="button" class="btn-tp-add" data-bs-toggle="modal" data-bs-target="#createTeachingModal">
                    <i class="fa-solid fa-plus"></i>Thêm dòng giảng dạy
                </button>
            </div>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="alert alert-success border-0 rounded-3 mb-4 d-flex align-items-center gap-2">
                <i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger border-0 rounded-3 mb-4 d-flex align-items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i><span>{{ $errors->first() }}</span>
            </div>
        @endif

        {{-- Stats --}}
        <div class="tp-stats">
            <div class="tp-stat tp-stat--blue">
                <div class="tp-stat__lbl">Tổng số môn</div>
                <div class="tp-stat__val">{{ $stats['total_subjects'] }}</div>
            </div>
            <div class="tp-stat tp-stat--green">
                <div class="tp-stat__lbl">Tổng số buổi</div>
                <div class="tp-stat__val">{{ $stats['total_sessions'] }}</div>
            </div>
            <div class="tp-stat tp-stat--amber">
                <div class="tp-stat__lbl">Đang dạy</div>
                <div class="tp-stat__val">{{ $stats['teaching'] }}</div>
            </div>
            <div class="tp-stat tp-stat--sky">
                <div class="tp-stat__lbl">Hoàn thành</div>
                <div class="tp-stat__val">{{ $stats['completed'] }}</div>
            </div>
        </div>

        {{-- Filters --}}
        <form action="{{ route('teaching.index') }}" method="GET" class="tp-filter">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-3">
                    <label class="form-label">Tìm kiếm</label>
                    <input type="search" name="search" value="{{ $filters['search'] }}" class="form-control"
                        placeholder="Tên môn, lớp, trung tâm...">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label">Trung tâm</label>
                    <select name="center_name" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($centers as $center)
                            <option value="{{ $center }}" @selected($filters['center_name'] === $center)>{{ $center }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label">Khóa</label>
                    <select name="term_code" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($terms as $term)
                            <option value="{{ $term }}" @selected($filters['term_code'] === $term)>{{ $term }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-1">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" name="from_date" value="{{ $filters['from_date'] }}" class="form-control">
                </div>
                <div class="col-6 col-md-3 col-lg-1">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] }}" class="form-control">
                </div>
                <div class="col-12 col-md-6 col-lg-1 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill rounded-3"
                        style="font-size:13px;font-weight:600">
                        <i class="fa-solid fa-filter me-1"></i>Lọc
                    </button>
                    <a href="{{ route('teaching.index') }}" class="btn btn-light border rounded-3" style="font-size:13px"
                        title="Đặt lại">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </div>
        </form>

        {{-- Table --}}
        <div class="tp-card">
            <div class="table-responsive">
                <table class="table tp-table">
                    <thead>
                        <tr>
                            <th>Tên môn học</th>
                            <th>Lớp / Trung tâm</th>
                            <th>Khóa</th>
                            <th>Số buổi</th>
                            <th>Thời gian</th>
                            <th>Trạng thái</th>
                            <th>Liên kết</th>
                            <th style="text-align:right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            <tr>
                                <td>
                                    <div class="cell-subject">{{ $record->subject_name }}</div>
                                    @if ($record->note)
                                        <div class="cell-note">{{ Str::limit($record->note, 80) }}</div>
                                    @endif
                                    @if (auth()->user()->role === 'admin')
                                        <div class="cell-note" style="margin-top:3px">
                                            <i class="fa-solid fa-user-tie"
                                                style="font-size:10px;margin-right:3px"></i>{{ $record->teacher?->name ?? 'N/A' }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-weight:600">{{ $record->class_name ?: '—' }}</div>
                                    <div class="cell-note">{{ $record->center_name ?: 'Chưa cập nhật' }}</div>
                                </td>
                                <td>
                                    <span class="tp-badge tp-badge--term">{{ $record->term_code ?: 'N/A' }}</span>
                                </td>
                                <td>
                                    <span
                                        style="font-weight:700;font-size:16px;color:var(--t-ink)">{{ $record->planned_sessions }}</span>
                                    <span class="cell-note" style="font-size:11px"> buổi</span>
                                </td>
                                <td>
                                    <div style="font-size:13px">{{ $record->start_date?->format('d/m/Y') ?: '—' }}</div>
                                    <div class="cell-note">→ {{ $record->end_date?->format('d/m/Y') ?: '—' }}</div>
                                </td>
                                <td>
                                    @php $sc = $statusClasses[$record->status] ?? 'secondary'; @endphp
                                    <span class="tp-badge tp-badge--{{ $sc }}">
                                        {{ $statuses[$record->status] ?? $record->status }}
                                    </span>
                                </td>
                                <td>
                                    @if ($record->course_id || $record->class_id)
                                        <span class="tp-badge tp-badge--linked">
                                            <i class="fa-solid fa-link" style="font-size:9px"></i>
                                            {{ collect([$record->course_id ? 'Course' : null, $record->class_id ? 'Class' : null])->filter()->join(' + ') }}
                                        </span>
                                    @else
                                        <span class="tp-badge tp-badge--unlinked">
                                            <i class="fa-solid fa-unlink" style="font-size:9px"></i>Chưa khớp
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="tp-actions">
                                        <button type="button" class="tp-btn-edit" data-bs-toggle="modal"
                                            data-bs-target="#editTeachingModal{{ $record->id }}">
                                            <i class="fa-solid fa-pen-to-square"></i>Sửa
                                        </button>
                                        <form action="{{ route('teaching.destroy', $record->id) }}" method="POST"
                                            onsubmit="return confirm('Xóa dòng giảng dạy này?');" style="margin:0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="tp-btn-del">
                                                <i class="fa-solid fa-trash-can"></i>Xóa
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="tp-empty">
                                        <div class="tp-empty-icon"><i class="fa-solid fa-chalkboard-teacher"></i></div>
                                        <p>Chưa có dữ liệu giảng dạy.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($records->hasPages())
                <div class="tp-pagination">{{ $records->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Create modal --}}
    <div class="modal fade" id="createTeachingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form action="{{ route('teaching.store') }}" method="POST" class="modal-content border-0 shadow-lg">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Thêm dòng giảng dạy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    @include('teaching.partials.form', ['record' => null])
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-600">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Import modal --}}
    <div class="modal fade" id="importTeachingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('teaching.import') }}" method="POST" enctype="multipart/form-data"
                class="modal-content border-0 shadow-lg">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Import giảng dạy từ Excel / CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    @if (auth()->user()->role === 'admin')
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Giáo viên</label>
                            <select name="teacher_id" class="form-select" required>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">File Excel / CSV</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv,.txt"
                            required>
                    </div>
                    <div class="alert alert-info border-0 rounded-3 small mb-0" style="background:#eff6ff;color:#1e40af">
                        <i class="fa-solid fa-circle-info me-2"></i>File cần có các cột:
                        <strong>Tên môn học, Lớp, Trung tâm, Khóa, Số buổi, Ngày bắt đầu, Ngày kết thúc, Trạng thái, Ghi
                            chú</strong>.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Import</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit modals --}}
    @foreach ($records as $record)
        <div class="modal fade" id="editTeachingModal{{ $record->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form action="{{ route('teaching.update', $record->id) }}" method="POST"
                    class="modal-content border-0 shadow-lg">
                    @csrf
                    @method('PUT')
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Sửa dòng giảng dạy</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body py-4">
                        @include('teaching.partials.form', ['record' => $record])
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary rounded-3 px-4">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.teaching-form').forEach((form) => {
                const courseSelect = form.querySelector('[name="course_id"]');
                const classSelect = form.querySelector('[name="class_id"]');
                const subjectInput = form.querySelector('[name="subject_name"]');
                const classInput = form.querySelector('[name="class_name"]');

                courseSelect?.addEventListener('change', () => {
                    const option = courseSelect.selectedOptions[0];
                    if (option?.dataset.title && !subjectInput.value.trim())
                        subjectInput.value = option.dataset.title;
                });

                classSelect?.addEventListener('change', () => {
                    const option = classSelect.selectedOptions[0];
                    if (option?.dataset.name && !classInput.value.trim())
                        classInput.value = option.dataset.name;
                });
            });
        });
    </script>
@endsection
