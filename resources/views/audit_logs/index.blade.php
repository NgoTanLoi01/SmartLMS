@extends('layouts.app')

@section('title', 'Audit log')

@push('styles')
    <style>
        .audit-page {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .audit-header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .audit-title {
            font-size: 26px;
            font-weight: 800;
            margin: 0;
        }

        .audit-subtitle {
            color: #64748b;
            margin: 6px 0 0;
        }

        .audit-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .audit-filter {
            padding: 18px;
        }

        .audit-table {
            margin: 0;
            vertical-align: top;
        }

        .audit-table th {
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
            background: #f8fafc;
            white-space: nowrap;
        }

        .audit-table td {
            font-size: 13px;
        }

        .audit-action {
            display: inline-flex;
            border-radius: 999px;
            background: #eef2ff;
            color: #3730a3;
            padding: 4px 10px;
            font-weight: 700;
            font-size: 12px;
        }

        .audit-json {
            max-width: 360px;
            max-height: 160px;
            overflow: auto;
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 8px;
            padding: 10px;
            font-size: 12px;
            white-space: pre-wrap;
        }

        .audit-danger-zone {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        @media (max-width: 767.98px) {
            .audit-title {
                font-size: 22px;
            }

            .audit-filter .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="audit-page">
        <div class="audit-header">
            <div>
                <h1 class="audit-title">Audit log</h1>
                <p class="audit-subtitle">Theo dõi thao tác nhạy cảm: điểm số, AI, import, lịch học và thanh toán.</p>
            </div>
            <form method="POST" action="{{ route('audit-logs.bulk-destroy') }}"
                onsubmit="return confirm('Bạn chắc chắn muốn xóa các audit log theo bộ lọc hiện tại? Nếu chưa chọn bộ lọc, toàn bộ audit log sẽ bị xóa.');">
                @csrf
                @method('DELETE')
                <input type="hidden" name="action" value="{{ $filters['action'] }}">
                <input type="hidden" name="user_id" value="{{ $filters['user_id'] }}">
                <input type="hidden" name="from_date" value="{{ $filters['from_date'] }}">
                <input type="hidden" name="to_date" value="{{ $filters['to_date'] }}">
                <button type="submit" class="btn btn-outline-danger">
                    <i class="fas fa-trash-alt me-1"></i> Xóa log theo bộ lọc
                </button>
            </form>
        </div>

        <div class="audit-card audit-filter">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label">Hành động</label>
                    <select name="action" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ $action }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Người thao tác</label>
                    <select name="user_id" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) $filters['user_id'] === (string) $user->id)>
                                {{ $user->name }}{{ $user->email ? ' - ' . $user->email : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" name="from_date" class="form-control" value="{{ $filters['from_date'] }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" name="to_date" class="form-control" value="{{ $filters['to_date'] }}">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">Lọc</button>
                    <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-secondary">Xóa</a>
                </div>
            </form>
        </div>

        <div class="audit-card">
            <div class="table-responsive">
                <table class="table audit-table">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Người thao tác</th>
                            <th>Hành động</th>
                            <th>Đối tượng</th>
                            <th>Mô tả</th>
                            <th>Trước</th>
                            <th>Sau</th>
                            <th>Thông tin thêm</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="text-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $log->user?->name ?? 'Hệ thống' }}</div>
                                    <div class="text-muted small">{{ $log->ip_address }}</div>
                                </td>
                                <td><span class="audit-action">{{ $log->action }}</span></td>
                                <td>
                                    <div>{{ class_basename($log->auditable_type) ?: 'N/A' }}</div>
                                    <div class="text-muted small">ID: {{ $log->auditable_id ?? 'N/A' }}</div>
                                </td>
                                <td>{{ $log->description }}</td>
                                <td>
                                    @if ($log->old_values)
                                        <pre class="audit-json">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($log->new_values)
                                        <pre class="audit-json">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($log->metadata)
                                        <pre class="audit-json">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('audit-logs.destroy', $log) }}"
                                        onsubmit="return confirm('Xóa audit log này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">
                                            <i class="fas fa-trash"></i> Xóa
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">Chưa có audit log phù hợp.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $logs->links() }}
    </div>
@endsection
