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
        .teaching-page {
            color: #0f172a;
        }

        .teaching-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .teaching-title {
            margin: 0 0 4px;
            font-size: 22px;
            font-weight: 800;
        }

        .teaching-subtitle {
            margin: 0;
            color: #64748b;
            font-size: 13.5px;
        }

        .teaching-stat {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
            height: 100%;
        }

        .teaching-stat__label {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .teaching-stat__value {
            margin-top: 8px;
            font-size: 28px;
            font-weight: 800;
            line-height: 1;
        }

        .teaching-filter,
        .teaching-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .teaching-filter {
            padding: 16px;
            margin: 18px 0;
        }

        .teaching-table th {
            color: #64748b;
            font-size: 11.5px;
            letter-spacing: .04em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .teaching-table td {
            vertical-align: middle;
            font-size: 13.5px;
        }

        .teaching-sub {
            color: #64748b;
            font-size: 12px;
        }

        .linked-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: 11.5px;
            font-weight: 700;
            background: #ecfdf5;
            color: #047857;
        }

        .unlinked-badge {
            background: #f8fafc;
            color: #64748b;
        }

        .teaching-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        @media (max-width: 767.98px) {
            .teaching-header .btn,
            .teaching-filter .btn {
                width: 100%;
            }

            .teaching-actions {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="teaching-page">
        <div class="teaching-header">
            <div>
                <h1 class="teaching-title">Giảng dạy</h1>
                <p class="teaching-subtitle">Theo dõi môn đã dạy, số buổi, trung tâm, khóa và trạng thái hoàn thành.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-toggle="modal"
                    data-bs-target="#importTeachingModal">
                    <i class="fas fa-file-import me-2"></i>Import Excel/CSV
                </button>
                <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal"
                    data-bs-target="#createTeachingModal">
                    <i class="fas fa-plus me-2"></i>Thêm dòng giảng dạy
                </button>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success border-0 rounded-3">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-0 rounded-3">
                <i class="fas fa-exclamation-circle me-2"></i>{{ $errors->first() }}
            </div>
        @endif

        <div class="row g-3">
            <div class="col-6 col-lg-3">
                <div class="teaching-stat">
                    <div class="teaching-stat__label">Tổng số môn</div>
                    <div class="teaching-stat__value text-primary">{{ $stats['total_subjects'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="teaching-stat">
                    <div class="teaching-stat__label">Tổng số buổi</div>
                    <div class="teaching-stat__value text-success">{{ $stats['total_sessions'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="teaching-stat">
                    <div class="teaching-stat__label">Đang dạy</div>
                    <div class="teaching-stat__value text-warning">{{ $stats['teaching'] }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="teaching-stat">
                    <div class="teaching-stat__label">Hoàn thành</div>
                    <div class="teaching-stat__value text-info">{{ $stats['completed'] }}</div>
                </div>
            </div>
        </div>

        <form action="{{ route('teaching.index') }}" method="GET" class="teaching-filter">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-3">
                    <label class="form-label small fw-bold text-muted">Tìm kiếm</label>
                    <input type="search" name="search" value="{{ $filters['search'] }}" class="form-control"
                        placeholder="Tên môn, lớp, trung tâm...">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small fw-bold text-muted">Trung tâm</label>
                    <select name="center_name" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($centers as $center)
                            <option value="{{ $center }}" @selected($filters['center_name'] === $center)>{{ $center }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small fw-bold text-muted">Khóa</label>
                    <select name="term_code" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($terms as $term)
                            <option value="{{ $term }}" @selected($filters['term_code'] === $term)>{{ $term }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small fw-bold text-muted">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-1">
                    <label class="form-label small fw-bold text-muted">Từ ngày</label>
                    <input type="date" name="from_date" value="{{ $filters['from_date'] }}" class="form-control">
                </div>
                <div class="col-6 col-lg-1">
                    <label class="form-label small fw-bold text-muted">Đến ngày</label>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] }}" class="form-control">
                </div>
                <div class="col-6 col-lg-1 d-grid">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </div>
        </form>

        <div class="teaching-card">
            <div class="table-responsive">
                <table class="table teaching-table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">Tên môn học</th>
                            <th class="px-4 py-3">Lớp / Trung tâm</th>
                            <th class="px-4 py-3">Khóa</th>
                            <th class="px-4 py-3">Số buổi</th>
                            <th class="px-4 py-3">Thời gian</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-4 py-3">Liên kết</th>
                            <th class="px-4 py-3 text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="fw-bold">{{ $record->subject_name }}</div>
                                    @if ($record->note)
                                        <div class="teaching-sub">{{ Str::limit($record->note, 80) }}</div>
                                    @endif
                                    @if (auth()->user()->role === 'admin')
                                        <div class="teaching-sub">
                                            <i class="fas fa-user-tie me-1"></i>{{ $record->teacher?->name ?? 'N/A' }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="fw-semibold">{{ $record->class_name ?: 'Chưa cập nhật lớp' }}</div>
                                    <div class="teaching-sub">{{ $record->center_name ?: 'Chưa cập nhật trung tâm' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">
                                        {{ $record->term_code ?: 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 fw-bold">{{ $record->planned_sessions }}</td>
                                <td class="px-4 py-3">
                                    <div>{{ $record->start_date?->format('d/m/Y') ?: '--' }}</div>
                                    <div class="teaching-sub">đến {{ $record->end_date?->format('d/m/Y') ?: '--' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="badge bg-{{ $statusClasses[$record->status] ?? 'secondary' }} bg-opacity-10 text-{{ $statusClasses[$record->status] ?? 'secondary' }} rounded-pill px-3">
                                        {{ $statuses[$record->status] ?? $record->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($record->course_id || $record->class_id)
                                        <span class="linked-badge">
                                            <i class="fas fa-link"></i>
                                            {{ collect([$record->course_id ? 'Course' : null, $record->class_id ? 'Class' : null])->filter()->join(' + ') }}
                                        </span>
                                    @else
                                        <span class="linked-badge unlinked-badge">
                                            <i class="fas fa-unlink"></i>Chưa khớp
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="teaching-actions">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editTeachingModal{{ $record->id }}">
                                            <i class="fas fa-edit me-1"></i>Sửa
                                        </button>
                                        <form action="{{ route('teaching.destroy', $record->id) }}" method="POST"
                                            onsubmit="return confirm('Xóa dòng giảng dạy này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">
                                                <i class="fas fa-trash-alt me-1"></i>Xóa
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-briefcase fa-3x mb-3 opacity-25"></i>
                                    <p class="mb-0">Chưa có dữ liệu giảng dạy.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($records->hasPages())
                <div class="p-3 border-top">
                    {{ $records->links() }}
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="createTeachingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form action="{{ route('teaching.store') }}" method="POST" class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Thêm dòng giảng dạy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    @include('teaching.partials.form', ['record' => null])
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="importTeachingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('teaching.import') }}" method="POST" enctype="multipart/form-data"
                class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Import giảng dạy từ Excel/CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    @if (auth()->user()->role === 'admin')
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Giáo viên</label>
                            <select name="teacher_id" class="form-select" required>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-semibold">File Excel/CSV</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv,.txt" required>
                    </div>
                    <div class="alert alert-info small mb-0">
                        File cần có các cột:
                        <strong>Tên môn học, Lớp, Trung tâm, Khóa, Số buổi, Ngày bắt đầu, Ngày kết thúc, Trạng thái, Ghi chú</strong>.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Import</button>
                </div>
            </form>
        </div>
    </div>

    @foreach ($records as $record)
        <div class="modal fade" id="editTeachingModal{{ $record->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form action="{{ route('teaching.update', $record->id) }}" method="POST"
                    class="modal-content border-0 shadow">
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
                        <button type="button" class="btn btn-light rounded-pill px-4"
                            data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Lưu thay đổi</button>
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
                    if (option?.dataset.title && !subjectInput.value.trim()) {
                        subjectInput.value = option.dataset.title;
                    }
                });

                classSelect?.addEventListener('change', () => {
                    const option = classSelect.selectedOptions[0];
                    if (option?.dataset.name && !classInput.value.trim()) {
                        classInput.value = option.dataset.name;
                    }
                });
            });
        });
    </script>
@endsection
