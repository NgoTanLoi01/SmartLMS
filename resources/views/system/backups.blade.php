@extends('layouts.app')

@section('title', 'Backup dữ liệu')

@section('content')
    <style>
        .backup-page {
            max-width: 1180px;
            margin: 0 auto;
        }

        .backup-header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .backup-title {
            margin: 0 0 5px;
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
        }

        .backup-subtitle {
            margin: 0;
            color: #64748b;
            font-size: 13.5px;
        }

        .backup-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .backup-btn {
            border: 0;
            border-radius: 10px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 800;
            background: #2563eb;
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .backup-btn:hover {
            color: #fff;
            background: #1d4ed8;
        }

        .backup-btn.secondary {
            background: #f8fafc;
            color: #334155;
            border: 1px solid #e2e8f0;
        }

        .backup-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .backup-card,
        .backup-panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
        }

        .backup-card {
            padding: 18px;
        }

        .backup-label {
            color: #64748b;
            font-size: 11.5px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .backup-value {
            color: #0f172a;
            font-size: 18px;
            font-weight: 800;
            word-break: break-word;
        }

        .backup-muted {
            color: #64748b;
            font-size: 13px;
            margin-top: 6px;
            word-break: break-word;
        }

        .backup-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 12.5px;
            font-weight: 800;
            white-space: nowrap;
        }

        .backup-badge.success {
            color: #047857;
            background: #ecfdf5;
        }

        .backup-badge.failed {
            color: #b91c1c;
            background: #fef2f2;
        }

        .backup-badge.running {
            color: #1d4ed8;
            background: #eff6ff;
        }

        .backup-badge.neutral {
            color: #475569;
            background: #f1f5f9;
        }

        .backup-panel {
            overflow: hidden;
            margin-bottom: 18px;
        }

        .backup-panel-header {
            padding: 16px 18px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .backup-panel-title {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
        }

        .backup-table {
            width: 100%;
            min-width: 980px;
            border-collapse: collapse;
        }

        .backup-table th {
            background: #f8fafc;
            color: #64748b;
            font-size: 11.5px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            padding: 13px 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .backup-table td {
            padding: 15px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #0f172a;
            vertical-align: top;
            font-size: 13.5px;
        }

        .backup-code {
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
            overflow-x: auto;
            font-size: 13px;
            margin: 0;
        }

        .backup-note {
            color: #475569;
            font-size: 13.5px;
            line-height: 1.65;
        }

        @media (max-width: 991.98px) {
            .backup-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .backup-grid {
                grid-template-columns: 1fr;
            }

            .backup-btn,
            .backup-actions form {
                width: 100%;
            }

            .backup-btn {
                justify-content: center;
            }
        }
    </style>

    <div class="backup-page">
        <div class="backup-header">
            <div>
                <h1 class="backup-title">Backup dữ liệu</h1>
                <p class="backup-subtitle">Theo dõi backup database, tải bản sao lưu và kiểm tra cấu hình lưu trữ dự phòng.</p>
            </div>

            <div class="backup-actions">
                <form method="POST" action="{{ route('system.backups.store') }}">
                    @csrf
                    <button type="submit" class="backup-btn">
                        <i class="fas fa-database"></i> Backup ngay
                    </button>
                </form>

                <form method="POST" action="{{ route('system.backups.store') }}">
                    @csrf
                    <input type="hidden" name="upload_r2" value="1">
                    <button type="submit" class="backup-btn secondary">
                        <i class="fas fa-cloud-upload-alt"></i> Backup + upload R2
                    </button>
                </form>
            </div>
        </div>

        @if (session('success'))
            <div class="alert-success">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert-error">
                <i class="fas fa-triangle-exclamation"></i> {{ session('error') }}
            </div>
        @endif

        <div class="backup-grid">
            <div class="backup-card">
                <div class="backup-label">Backup thành công gần nhất</div>
                <div class="backup-value">
                    {{ $latestSuccessful?->finished_at?->timezone(config('backup.timezone'))->format('H:i d/m/Y') ?? 'Chưa có' }}
                </div>
                <div class="backup-muted">{{ $latestSuccessful?->filename ?? 'Chưa tạo bản backup nào.' }}</div>
            </div>

            <div class="backup-card">
                <div class="backup-label">Dung lượng gần nhất</div>
                <div class="backup-value">{{ $latestSuccessful?->formattedSize() ?? '---' }}</div>
                <div class="backup-muted">Local giữ {{ $summary['keep_local_copies'] }} bản mới nhất.</div>
            </div>

            <div class="backup-card">
                <div class="backup-label">Backup tự động</div>
                <span class="backup-badge {{ $summary['schedule_enabled'] ? 'success' : 'neutral' }}">
                    <i class="fas {{ $summary['schedule_enabled'] ? 'fa-check-circle' : 'fa-clock' }}"></i>
                    {{ $summary['schedule_enabled'] ? 'Đã bật' : 'Chưa bật' }}
                </span>
                <div class="backup-muted">Giờ chạy: {{ $summary['schedule_time'] }}</div>
            </div>

            <div class="backup-card">
                <div class="backup-label">Cloudflare R2</div>
                <span class="backup-badge {{ $summary['r2_ready'] ? 'success' : 'failed' }}">
                    <i class="fas fa-cloud"></i>
                    {{ $summary['r2_ready'] ? 'Sẵn sàng' : 'Chưa đủ cấu hình' }}
                </span>
                <div class="backup-muted">{{ $summary['r2_bucket'] ?: 'Chưa cấu hình bucket' }}</div>
            </div>
        </div>

        @if ($latestFailed)
            <div class="backup-panel">
                <div class="backup-panel-header">
                    <h2 class="backup-panel-title text-danger">
                        <i class="fas fa-triangle-exclamation me-2"></i> Backup lỗi gần nhất
                    </h2>
                    <span class="backup-muted">
                        {{ $latestFailed->finished_at?->timezone(config('backup.timezone'))->format('H:i d/m/Y') }}
                    </span>
                </div>
                <div class="p-3 backup-note text-danger">
                    {{ $latestFailed->error_message }}
                </div>
            </div>
        @endif

        <div class="backup-panel">
            <div class="backup-panel-header">
                <h2 class="backup-panel-title">Lịch sử backup</h2>
                <div class="backup-muted">Thư mục local: {{ $summary['local_directory'] }}</div>
            </div>

            <div class="table-responsive">
                <table class="backup-table">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Trạng thái</th>
                            <th>File</th>
                            <th>Dung lượng</th>
                            <th>Nguồn chạy</th>
                            <th>R2</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($backups as $backup)
                            <tr>
                                <td>
                                    <strong>{{ $backup->started_at?->timezone(config('backup.timezone'))->format('H:i d/m/Y') }}</strong>
                                    <div class="backup-muted">{{ $backup->duration_seconds ? $backup->duration_seconds . ' giây' : '---' }}</div>
                                </td>
                                <td>
                                    <span class="backup-badge {{ $backup->status }}">
                                        @if ($backup->status === 'success')
                                            <i class="fas fa-check-circle"></i> Thành công
                                        @elseif ($backup->status === 'failed')
                                            <i class="fas fa-times-circle"></i> Lỗi
                                        @else
                                            <i class="fas fa-spinner"></i> Đang chạy
                                        @endif
                                    </span>
                                    @if ($backup->error_message)
                                        <div class="backup-muted text-danger">{{ $backup->error_message }}</div>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $backup->filename ?? '---' }}</strong>
                                    <div class="backup-muted">{{ $backup->localFileExists() ? 'Có file local' : 'Không thấy file local' }}</div>
                                </td>
                                <td>{{ $backup->formattedSize() }}</td>
                                <td>
                                    {{ $backup->triggered_by === 'manual' ? 'Admin' : 'Command/Cron' }}
                                    <div class="backup-muted">{{ $backup->user?->name }}</div>
                                </td>
                                <td>
                                    @if ($backup->remote_path)
                                        <span class="backup-badge success">
                                            <i class="fas fa-cloud"></i> {{ strtoupper($backup->remote_disk) }}
                                        </span>
                                        <div class="backup-muted">{{ $backup->remote_path }}</div>
                                    @else
                                        <span class="backup-muted">Chưa upload</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($backup->isSuccessful())
                                        <a class="backup-btn secondary" href="{{ route('system.backups.download', $backup) }}">
                                            <i class="fas fa-download"></i> Tải
                                        </a>
                                    @else
                                        <span class="backup-muted">---</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center backup-muted py-4">Chưa có lịch sử backup.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3">
                {{ $backups->links() }}
            </div>
        </div>

        <div class="backup-panel">
            <div class="backup-panel-header">
                <h2 class="backup-panel-title">Hướng dẫn khôi phục khi có sự cố</h2>
            </div>
            <div class="p-3 backup-note">
                <p class="mb-2">
                    Giai đoạn hiện tại hệ thống chỉ hỗ trợ tạo và tải backup. Việc khôi phục nên chạy bằng lệnh server để tránh bấm nhầm làm ghi đè dữ liệu thật.
                </p>
                <pre class="backup-code"><code>gunzip smartlms-db-YYYYMMDD-HHMMSS.sql.gz
docker compose exec -T db mysql -u lms_user -plms_password lms_db &lt; smartlms-db-YYYYMMDD-HHMMSS.sql</code></pre>
                <p class="mb-0 mt-3">
                    Nếu username/database khác với môi trường thật, thay `lms_user`, `lms_password` và `lms_db` theo `.env`. Trước khi restore nên tạo thêm một bản backup mới của dữ liệu hiện tại.
                </p>
            </div>
        </div>
    </div>
@endsection
