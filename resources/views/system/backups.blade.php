@extends('layouts.app')

@section('title', 'Sao lưu dữ liệu')

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

        .backup-btn.warning {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #fed7aa;
        }

        .backup-btn.danger {
            background: #dc2626;
            color: #fff;
        }

        .backup-btn.small {
            padding: 7px 10px;
            border-radius: 8px;
            font-size: 12px;
        }

        .backup-btn:disabled {
            cursor: not-allowed;
            opacity: .55;
        }

        .backup-row-actions {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
            min-width: 235px;
        }

        .backup-row-actions form {
            margin: 0;
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

        .backup-badge.warning {
            color: #b45309;
            background: #fffbeb;
        }

        .backup-badge.invalid {
            color: #b91c1c;
            background: #fef2f2;
        }

        .restore-warning {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13px;
            line-height: 1.6;
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
                <h1 class="backup-title">Sao lưu dữ liệu</h1>
                <p class="backup-subtitle">Sao lưu database, bài nộp, học liệu và các file quan trọng trong một gói có checksum.</p>
            </div>

            <div class="backup-actions">
                <form method="POST" action="{{ route('system.backups.store') }}">
                    @csrf
                    <button type="submit" class="backup-btn">
                        <i class="fa-solid fa-box-archive"></i> Sao lưu toàn hệ thống
                    </button>
                </form>

                <form method="POST" action="{{ route('system.backups.store') }}">
                    @csrf
                    <input type="hidden" name="upload_r2" value="1">
                    <button type="submit" class="backup-btn secondary">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Sao lưu toàn bộ lên R2
                    </button>
                </form>
            </div>
        </div>

        <div class="backup-grid">
            <div class="backup-card">
                <div class="backup-label">Bản sao lưu thành công gần nhất</div>
                <div class="backup-value">
                    {{ $latestSuccessful?->finished_at?->timezone(config('backup.timezone'))->format('H:i d/m/Y') ?? 'Chưa có' }}
                </div>
                <div class="backup-muted">{{ $latestSuccessful?->filename ?? 'Chưa tạo bản backup nào.' }}</div>
            </div>

            <div class="backup-card">
                <div class="backup-label">Dung lượng gần nhất</div>
                <div class="backup-value">{{ $latestSuccessful?->formattedSize() ?? '---' }}</div>
                <div class="backup-muted">Local giữ {{ $summary['keep_local_copies'] }} bản mới nhất · disk: {{ implode(', ', $summary['file_disks']) }}.</div>
            </div>

            <div class="backup-card">
                <div class="backup-label">Sao lưu tự động</div>
                <span class="backup-badge {{ $summary['schedule_enabled'] ? 'success' : 'neutral' }}">
                    <i class="fa-solid {{ $summary['schedule_enabled'] ? 'fa-circle-check' : 'fa-clock' }}"></i>
                    {{ $summary['schedule_enabled'] ? 'Đã bật' : 'Chưa bật' }}
                </span>
                <div class="backup-muted">Giờ chạy: {{ $summary['schedule_time'] }}</div>
            </div>

            <div class="backup-card">
                <div class="backup-label">Cloudflare R2</div>
                <span class="backup-badge {{ $summary['r2_ready'] ? 'success' : 'failed' }}">
                    <i class="fa-solid fa-cloud"></i>
                    {{ $summary['r2_ready'] ? 'Sẵn sàng' : 'Chưa đủ cấu hình' }}
                </span>
                <div class="backup-muted">{{ $summary['r2_bucket'] ?: 'Chưa cấu hình bucket' }}</div>
            </div>
        </div>

        @if ($latestFailed)
            <div class="backup-panel">
                <div class="backup-panel-header">
                    <h2 class="backup-panel-title text-danger">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> Lần sao lưu lỗi gần nhất
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
                <div class="backup-muted">Thư mục nội bộ: {{ $summary['local_directory'] }}</div>
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
                            <th>Toàn vẹn</th>
                            <th>Lưu trữ</th>
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
                                            <i class="fa-solid fa-circle-check"></i> Thành công
                                        @elseif ($backup->status === 'failed')
                                            <i class="fa-solid fa-circle-xmark"></i> Lỗi
                                        @else
                                            <i class="fa-solid fa-spinner"></i> Đang chạy
                                        @endif
                                    </span>
                                    @if ($backup->error_message)
                                        <div class="backup-muted text-danger">{{ $backup->error_message }}</div>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $backup->filename ?? '---' }}</strong>
                                    <div class="backup-muted">{{ $backup->localFileExists() ? 'Có tệp nội bộ' : 'Không thấy tệp nội bộ' }}</div>
                                    @if ($backup->isRestorable())
                                        <div class="backup-muted">
                                            {{ (int) ($backup->metadata['included_files'] ?? 0) }} file trong gói
                                            · {{ (int) ($backup->metadata['vector_database_rows'] ?? 0) }} đoạn dữ liệu AI
                                            @if ((int) ($backup->metadata['missing_files'] ?? 0) > 0)
                                                · thiếu {{ (int) $backup->metadata['missing_files'] }} file nguồn
                                            @endif
                                        </div>
                                    @else
                                        <div class="backup-muted">Backup database định dạng cũ</div>
                                    @endif
                                </td>
                                <td>{{ $backup->formattedSize() }}</td>
                                <td>
                                    {{ match ($backup->triggered_by) {
                                        'manual' => 'Quản trị viên',
                                        'pre_restore' => 'Trước phục hồi',
                                        default => 'Tác vụ tự động',
                                    } }}
                                    <div class="backup-muted">{{ $backup->user?->name }}</div>
                                </td>
                                <td>
                                    @if (!$backup->isRestorable())
                                        <span class="backup-badge neutral"><i class="fa-solid fa-ban"></i> Không hỗ trợ</span>
                                    @elseif ($backup->integrityStatus() === 'valid')
                                        <span class="backup-badge success"><i class="fa-solid fa-circle-check"></i> Hợp lệ</span>
                                    @elseif ($backup->integrityStatus() === 'invalid')
                                        <span class="backup-badge invalid"><i class="fa-solid fa-circle-xmark"></i> Không hợp lệ</span>
                                    @else
                                        <span class="backup-badge warning"><i class="fa-solid fa-shield-halved"></i> Chưa kiểm tra</span>
                                    @endif
                                    @if ($backup->metadata['last_verified_at'] ?? null)
                                        <div class="backup-muted">{{ \Illuminate\Support\Carbon::parse($backup->metadata['last_verified_at'])->timezone(config('backup.timezone'))->format('H:i d/m/Y') }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($backup->remote_path)
                                        <span class="backup-badge success">
                                            <i class="fa-solid fa-cloud"></i> {{ strtoupper($backup->remote_disk) }}
                                        </span>
                                        <div class="backup-muted">{{ $backup->remote_path }}</div>
                                    @else
                                        <span class="backup-muted">Chưa upload</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($backup->isSuccessful())
                                        <div class="backup-row-actions">
                                            <a class="backup-btn secondary small" data-file-download href="{{ route('system.backups.download', $backup) }}">
                                                <i class="fa-solid fa-download"></i> Tải
                                            </a>
                                            @if ($backup->isRestorable())
                                                <form method="POST" action="{{ route('system.backups.verify', $backup) }}">
                                                    @csrf
                                                    <button class="backup-btn warning small" type="submit">
                                                        <i class="fa-solid fa-shield"></i> Kiểm tra
                                                    </button>
                                                </form>
                                                @if ($backup->integrityStatus() === 'valid')
                                                    <button class="backup-btn danger small js-open-restore" type="button"
                                                        data-bs-toggle="modal" data-bs-target="#restoreBackupModal"
                                                        data-restore-url="{{ route('system.backups.restore', $backup) }}"
                                                        data-backup-id="{{ $backup->id }}"
                                                        data-filename="{{ $backup->filename }}">
                                                        <i class="fa-solid fa-clock-rotate-left"></i> Phục hồi
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    @else
                                        <span class="backup-muted">---</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center backup-muted py-4">Chưa có lịch sử backup.</td>
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
                <h2 class="backup-panel-title">Cơ chế khôi phục an toàn</h2>
            </div>
            <div class="p-3 backup-note">
                <p class="mb-2">Chỉ gói backup mới đã vượt qua kiểm tra checksum mới hiển thị nút phục hồi. Khi thực hiện, hệ thống sẽ:</p>
                <ol class="mb-0 ps-3">
                    <li>Kiểm tra lại toàn bộ manifest, checksum database và từng file.</li>
                    <li>Tự tạo một gói dự phòng của trạng thái hiện tại.</li>
                    <li>Bật chế độ bảo trì, phục hồi database và ghi đè các file có trong backup.</li>
                    <li>Nếu có lỗi, tự động dùng gói dự phòng để quay lại trạng thái trước thao tác.</li>
                </ol>
            </div>
        </div>

        <div class="modal fade" id="restoreBackupModal" tabindex="-1" aria-labelledby="restoreBackupTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content border-0 shadow-lg" id="restoreBackupForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="restoreBackupTitle">
                            <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>Phục hồi hệ thống
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div class="restore-warning mb-3">
                            Database hiện tại sẽ được thay bằng dữ liệu trong <strong id="restoreBackupFilename"></strong>.
                            Hệ thống có thể tạm thời không truy cập được trong quá trình phục hồi.
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="restoreConfirmation">Nhập <code>KHOI PHUC</code> để xác nhận</label>
                            <input class="form-control @error('confirmation', 'restoreBackup') is-invalid @enderror"
                                id="restoreConfirmation" name="confirmation" autocomplete="off" required>
                            @error('confirmation', 'restoreBackup')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label fw-semibold" for="restoreCurrentPassword">Mật khẩu hiện tại của quản trị viên</label>
                            <input type="password" class="form-control @error('current_password', 'restoreBackup') is-invalid @enderror"
                                id="restoreCurrentPassword" name="current_password" autocomplete="current-password" required>
                            @error('current_password', 'restoreBackup')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="backup-btn secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="backup-btn danger">
                            <i class="fa-solid fa-clock-rotate-left"></i> Tạo dự phòng và phục hồi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modalElement = document.getElementById('restoreBackupModal');
            const form = document.getElementById('restoreBackupForm');
            const filename = document.getElementById('restoreBackupFilename');
            if (!modalElement || !form || !filename) return;

            document.querySelectorAll('.js-open-restore').forEach(button => {
                button.addEventListener('click', () => {
                    form.action = button.dataset.restoreUrl;
                    filename.textContent = button.dataset.filename;
                    document.getElementById('restoreConfirmation').value = '';
                    document.getElementById('restoreCurrentPassword').value = '';
                });
            });

            @if ($errors->restoreBackup->isNotEmpty() && session('restore_backup_id'))
                const failedButton = document.querySelector(`[data-backup-id="{{ (int) session('restore_backup_id') }}"]`);
                if (failedButton) {
                    form.action = failedButton.dataset.restoreUrl;
                    filename.textContent = failedButton.dataset.filename;
                    bootstrap.Modal.getOrCreateInstance(modalElement).show();
                }
            @endif
        });
    </script>
@endpush
