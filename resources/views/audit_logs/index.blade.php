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
            letter-spacing: -0.02em;
        }

        .audit-subtitle {
            color: #64748b;
            margin: 6px 0 0;
            font-size: 13.5px;
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

        .audit-table thead th {
            position: sticky;
            top: 0;
            color: #64748b;
            font-size: 11.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            background: #f8fafc;
            white-space: nowrap;
            border-bottom: 1px solid #e2e8f0;
            z-index: 1;
        }

        .audit-table td {
            font-size: 13px;
        }

        .audit-table tbody tr {
            transition: background-color .12s ease;
        }

        .audit-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .audit-table-scroll {
            max-height: 640px;
            overflow-y: auto;
        }

        .audit-action {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-radius: 999px;
            background: #eef2ff;
            color: #3730a3;
            padding: 4px 10px;
            font-weight: 700;
            font-size: 11.5px;
            white-space: nowrap;
        }

        .audit-action--create {
            background: #ecfdf5;
            color: #047857;
        }

        .audit-action--update {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .audit-action--delete {
            background: #fef2f2;
            color: #b91c1c;
        }

        .audit-action--login {
            background: #fefce8;
            color: #a16207;
        }

        .audit-ip {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 11.5px;
        }

        .audit-json {
            max-width: 320px;
            max-height: 160px;
            overflow: auto;
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 8px;
            padding: 10px;
            font-size: 11.5px;
            line-height: 1.5;
            white-space: pre-wrap;
        }

        .audit-json::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .audit-json::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 999px;
        }

        .audit-empty-cell {
            color: #cbd5e1;
            font-size: 12px;
        }

        .audit-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            padding: 56px 16px;
            color: #94a3b8;
        }

        .audit-empty-state i {
            font-size: 28px;
            opacity: .6;
        }

        .audit-danger-zone {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .audit-row-actions form {
            display: inline-block;
        }

        @media (max-width: 767.98px) {
            .audit-title {
                font-size: 22px;
            }

            .audit-filter .btn {
                width: 100%;
            }

            .audit-header>form {
                width: 100%;
            }

            .audit-header>form .btn {
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
                <button type="submit" class="btn btn-outline-danger rounded-pill px-4">
                    <i class="fa-solid fa-trash-can me-1"></i> Xóa log theo bộ lọc
                </button>
            </form>
        </div>

        <div class="audit-card audit-filter">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold text-muted">Hành động</label>
                    <select name="action" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ $action }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold text-muted">Người thao tác</label>
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
                    <label class="form-label small fw-bold text-muted">Từ ngày</label>
                    <input type="date" name="from_date" class="form-control" value="{{ $filters['from_date'] }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-bold text-muted">Đến ngày</label>
                    <input type="date" name="to_date" class="form-control" value="{{ $filters['to_date'] }}">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="fa-solid fa-filter me-1"></i>Lọc
                    </button>
                    <a href="{{ route('audit-logs.index') }}" class="btn btn-light border" title="Đặt lại bộ lọc">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>
        </div>

        <div class="audit-card">
            <div class="table-responsive audit-table-scroll">
                <table class="table audit-table">
                    <thead>
                        <tr>
                            <th class="px-3 py-3">Thời gian</th>
                            <th class="px-3 py-3">Người thao tác</th>
                            <th class="px-3 py-3">Hành động</th>
                            <th class="px-3 py-3">Đối tượng</th>
                            <th class="px-3 py-3">Mô tả</th>
                            <th class="px-3 py-3">Trước</th>
                            <th class="px-3 py-3">Sau</th>
                            <th class="px-3 py-3">Thông tin thêm</th>
                            <th class="px-3 py-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            @php
                                $actionLower = strtolower($log->action);
                                $actionClass = match (true) {
                                    str_contains($actionLower, 'create') || str_contains($actionLower, 'import')
                                        => 'audit-action--create',
                                    str_contains($actionLower, 'update') || str_contains($actionLower, 'edit')
                                        => 'audit-action--update',
                                    str_contains($actionLower, 'delete') || str_contains($actionLower, 'destroy')
                                        => 'audit-action--delete',
                                    str_contains($actionLower, 'login') || str_contains($actionLower, 'logout')
                                        => 'audit-action--login',
                                    default => '',
                                };
                            @endphp
                            <tr>
                                <td class="px-3 py-3 text-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                <td class="px-3 py-3">
                                    <div class="fw-semibold">{{ $log->user?->name ?? 'Hệ thống' }}</div>
                                    <div class="audit-ip text-muted">{{ $log->ip_address }}</div>
                                </td>
                                <td class="px-3 py-3"><span
                                        class="audit-action {{ $actionClass }}">{{ $log->action }}</span></td>
                                <td class="px-3 py-3">
                                    <div class="fw-semibold">{{ class_basename($log->auditable_type) ?: 'N/A' }}</div>
                                    <div class="text-muted small">ID: {{ $log->auditable_id ?? 'N/A' }}</div>
                                </td>
                                <td class="px-3 py-3">{{ $log->description }}</td>
                                <td class="px-3 py-3">
                                    @if ($log->old_values)
                                        <pre class="audit-json">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @else
                                        <span class="audit-empty-cell">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if ($log->new_values)
                                        <pre class="audit-json">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @else
                                        <span class="audit-empty-cell">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if ($log->metadata)
                                        <pre class="audit-json">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @else
                                        <span class="audit-empty-cell">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 audit-row-actions">
                                    <form method="POST" action="{{ route('audit-logs.destroy', $log) }}"
                                        onsubmit="return confirm('Xóa audit log này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">
                                            <i class="fa-solid fa-trash"></i> Xóa
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="audit-empty-state">
                                        <i class="fa-solid fa-shield-halved"></i>
                                        <span>Chưa có audit log phù hợp.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $logs->links() }}
    </div>
@endsection
