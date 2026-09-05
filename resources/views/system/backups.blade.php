@extends('layouts.app')

@section('title', 'Sao lưu dữ liệu')

@push('styles')
    @vite('resources/css/pages/system-operations.css')
@endpush

@section('content')
    <div class="lms-page system-operations-page backup-page">
        <section class="system-hero backup-hero" aria-label="Tổng quan sao lưu dữ liệu">
            <div class="system-hero__accent" aria-hidden="true"></div>
            <x-ui.page-header title="Sao lưu dữ liệu">
                <x-slot:meta>
                    <span><i class="fa-solid fa-database"></i> Database, bài nộp, học liệu và dữ liệu AI</span>
                    <span><i class="fa-solid fa-shield-halved"></i> Kiểm tra toàn vẹn bằng checksum</span>
                </x-slot:meta>
                <x-slot:actions>
                    <form method="POST" action="{{ route('system.backups.store') }}" class="js-backup-create-form">
                        @csrf
                        <button type="submit" class="system-button system-button--primary" data-busy-label="Đang tạo bản sao lưu…">
                            <i class="fa-solid fa-box-archive"></i> Sao lưu hệ thống
                        </button>
                    </form>
                    <form method="POST" action="{{ route('system.backups.store') }}" class="js-backup-create-form">
                        @csrf
                        <input type="hidden" name="upload_r2" value="1">
                        <button type="submit" class="system-button system-button--cloud" data-busy-label="Đang sao lưu lên R2…" @disabled(!$summary['r2_ready']) title="{{ $summary['r2_ready'] ? 'Tạo và tải bản sao lưu lên R2' : 'Cần cấu hình Cloudflare R2 trước' }}">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Sao lưu lên R2
                        </button>
                    </form>
                </x-slot:actions>
            </x-ui.page-header>

            <div class="system-stats">
                <article class="system-stat stat-green"><span><i class="fa-solid fa-clock-rotate-left"></i></span><div><strong>{{ $latestSuccessful?->finished_at?->timezone(config('backup.timezone'))->format('H:i d/m') ?? 'Chưa có' }}</strong><small>Lần sao lưu thành công gần nhất</small></div></article>
                <article class="system-stat stat-blue"><span><i class="fa-solid fa-hard-drive"></i></span><div><strong>{{ $latestSuccessful?->formattedSize() ?? '—' }}</strong><small>Dung lượng gói gần nhất</small></div></article>
                <article class="system-stat {{ $summary['schedule_enabled'] ? 'stat-violet' : 'stat-slate' }}"><span><i class="fa-solid fa-calendar-check"></i></span><div><strong>{{ $summary['schedule_enabled'] ? 'Đã bật' : 'Chưa bật' }}</strong><small>Tự động lúc {{ $summary['schedule_time'] }}</small></div></article>
                <article class="system-stat {{ $summary['r2_ready'] ? 'stat-cyan' : 'stat-red' }}"><span><i class="fa-solid fa-cloud"></i></span><div><strong>{{ $summary['r2_ready'] ? 'Sẵn sàng' : 'Chưa cấu hình' }}</strong><small>{{ $summary['r2_bucket'] ?: 'Cloudflare R2' }}</small></div></article>
            </div>
        </section>

        @if ($latestFailed)
            <section class="backup-failure-alert" aria-label="Lần sao lưu lỗi gần nhất">
                <span class="backup-failure-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <div><span class="system-eyebrow">LẦN SAO LƯU LỖI GẦN NHẤT</span><strong>{{ $latestFailed->error_message ?: 'Không xác định được nguyên nhân lỗi.' }}</strong><small>{{ $latestFailed->finished_at?->timezone(config('backup.timezone'))->format('H:i · d/m/Y') }}</small></div>
            </section>
        @endif

        <section class="system-list-card" aria-labelledby="backup-history-title">
            <header class="system-section-header system-list-header">
                <div><span class="system-section-icon icon-cyan"><i class="fa-solid fa-clock-rotate-left"></i></span><div><h2 id="backup-history-title">Lịch sử sao lưu</h2><p>Lưu cục bộ tối đa {{ $summary['keep_local_copies'] }} bản · {{ implode(', ', $summary['file_disks']) }}</p></div></div>
                <span class="system-result-count">{{ $backups->firstItem() ?? 0 }}–{{ $backups->lastItem() ?? 0 }} / {{ $backups->total() }}</span>
            </header>

            <div class="backup-list">
                @forelse ($backups as $backup)
                    @php
                        $status = $backup->status;
                        $statusLabel = match ($status) { 'success' => 'Thành công', 'failed' => 'Thất bại', default => 'Đang chạy' };
                        $statusIcon = match ($status) { 'success' => 'fa-circle-check', 'failed' => 'fa-circle-xmark', default => 'fa-spinner' };
                        $integrity = $backup->integrityStatus();
                        $integrityLabel = match (true) {
                            !$backup->isRestorable() => 'Định dạng cũ',
                            $integrity === 'valid' => 'Đã xác minh',
                            $integrity === 'invalid' => 'Không toàn vẹn',
                            default => 'Chưa kiểm tra',
                        };
                        $integrityClass = match (true) {
                            !$backup->isRestorable() => 'neutral',
                            $integrity === 'valid' => 'success',
                            $integrity === 'invalid' => 'danger',
                            default => 'warning',
                        };
                        $triggerLabel = match ($backup->triggered_by) {
                            'manual' => 'Quản trị viên',
                            'pre_restore' => 'Tự động trước phục hồi',
                            default => 'Lịch tự động',
                        };
                    @endphp
                    <article class="backup-entry backup-status-{{ $status }}">
                        <div class="backup-entry-status"><i class="fa-solid {{ $statusIcon }} {{ $status === 'running' ? 'fa-spin' : '' }}"></i></div>
                        <div class="backup-entry-body">
                            <header class="backup-entry-header">
                                <div class="backup-file-heading">
                                    <div><h3>{{ $backup->filename ?? 'Đang chuẩn bị tên tệp…' }}</h3><span>#{{ $backup->id }} · {{ $backup->type === 'full' ? 'Gói sao lưu toàn hệ thống' : 'Sao lưu database' }}</span></div>
                                    <span class="system-status status-{{ $status }}">{{ $statusLabel }}</span>
                                </div>
                                <time datetime="{{ $backup->started_at?->toIso8601String() }}"><strong>{{ $backup->started_at?->timezone(config('backup.timezone'))->format('H:i · d/m/Y') ?? '—' }}</strong><span>{{ $backup->duration_seconds ? $backup->duration_seconds . ' giây' : 'Chưa có thời gian chạy' }}</span></time>
                            </header>

                            @if ($backup->error_message)<div class="backup-entry-error"><i class="fa-solid fa-triangle-exclamation"></i>{{ $backup->error_message }}</div>@endif

                            <div class="backup-entry-metrics">
                                <div><span><i class="fa-solid fa-weight-hanging"></i> Dung lượng</span><strong>{{ $backup->formattedSize() }}</strong></div>
                                <div><span><i class="fa-solid fa-user-gear"></i> Nguồn chạy</span><strong>{{ $triggerLabel }}</strong><small>{{ $backup->user?->name }}</small></div>
                                <div><span><i class="fa-solid fa-shield-halved"></i> Toàn vẹn</span><strong class="backup-integrity integrity-{{ $integrityClass }}">{{ $integrityLabel }}</strong>@if ($backup->metadata['last_verified_at'] ?? null)<small>{{ \Illuminate\Support\Carbon::parse($backup->metadata['last_verified_at'])->timezone(config('backup.timezone'))->format('H:i · d/m/Y') }}</small>@endif</div>
                                <div><span><i class="fa-solid fa-box"></i> Vị trí lưu</span><strong>{{ $backup->remote_path ? strtoupper((string) $backup->remote_disk) : ($backup->localFileExists() ? 'Nội bộ' : 'Không tìm thấy tệp') }}</strong><small>{{ $backup->remote_path ? Str::limit($backup->remote_path, 45) : null }}</small></div>
                            </div>

                            @if ($backup->isRestorable())
                                <details class="system-details backup-package-details">
                                    <summary><span><i class="fa-solid fa-box-open"></i> Nội dung gói sao lưu</span><i class="fa-solid fa-chevron-down"></i></summary>
                                    <div class="backup-package-grid">
                                        <span><i class="fa-solid fa-file"></i><strong>{{ number_format((int) ($backup->metadata['included_files'] ?? 0)) }}</strong> file</span>
                                        <span><i class="fa-solid fa-brain"></i><strong>{{ number_format((int) ($backup->metadata['vector_database_rows'] ?? 0)) }}</strong> đoạn dữ liệu AI</span>
                                        <span class="{{ (int) ($backup->metadata['missing_files'] ?? 0) > 0 ? 'has-warning' : '' }}"><i class="fa-solid fa-file-circle-question"></i><strong>{{ number_format((int) ($backup->metadata['missing_files'] ?? 0)) }}</strong> file nguồn bị thiếu</span>
                                        <span><i class="fa-solid fa-fingerprint"></i><strong>SHA-256</strong> checksum</span>
                                    </div>
                                </details>
                            @endif

                            <footer class="backup-entry-actions">
                                <div class="backup-storage-note"><i class="fa-solid {{ $backup->localFileExists() ? 'fa-hard-drive' : 'fa-circle-info' }}"></i>{{ $backup->localFileExists() ? 'Tệp nội bộ khả dụng' : ($backup->remote_path ? 'Có thể tải từ kho từ xa' : 'Không có tệp để tải') }}</div>
                                @if ($backup->isSuccessful())
                                    <div class="backup-action-group">
                                        <a class="system-button system-button--small system-button--neutral" data-file-download href="{{ route('system.backups.download', $backup) }}"><i class="fa-solid fa-download"></i> Tải xuống</a>
                                        @if ($backup->isRestorable())
                                            <form method="POST" action="{{ route('system.backups.verify', $backup) }}">@csrf<button class="system-button system-button--small system-button--verify" type="submit"><i class="fa-solid fa-shield"></i> Kiểm tra</button></form>
                                            @if ($integrity === 'valid')
                                                <button class="system-button system-button--small system-button--danger js-open-restore" type="button" data-bs-toggle="modal" data-bs-target="#restoreBackupModal" data-restore-url="{{ route('system.backups.restore', $backup) }}" data-backup-id="{{ $backup->id }}" data-filename="{{ $backup->filename }}"><i class="fa-solid fa-clock-rotate-left"></i> Phục hồi</button>
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            </footer>
                        </div>
                    </article>
                @empty
                    <div class="system-empty-state"><span><i class="fa-solid fa-box-archive"></i></span><h3>Chưa có bản sao lưu</h3><p>Tạo bản sao lưu đầu tiên để bảo vệ dữ liệu SmartLMS.</p></div>
                @endforelse
            </div>

            @if ($backups->hasPages())<div class="system-pagination">{{ $backups->links() }}</div>@endif
        </section>

        <section class="restore-safety-card" aria-labelledby="restore-safety-title">
            <header><span><i class="fa-solid fa-shield-heart"></i></span><div><span class="system-eyebrow">QUY TRÌNH AN TOÀN</span><h2 id="restore-safety-title">Hệ thống bảo vệ dữ liệu thế nào khi phục hồi?</h2></div></header>
            <div class="restore-safety-steps">
                <div><b>1</b><span><strong>Kiểm tra gói</strong><small>Xác minh manifest và checksum của database cùng từng file.</small></span></div>
                <i class="fa-solid fa-arrow-right"></i>
                <div><b>2</b><span><strong>Tạo bản dự phòng</strong><small>Sao lưu trạng thái hiện tại trước khi thay đổi dữ liệu.</small></span></div>
                <i class="fa-solid fa-arrow-right"></i>
                <div><b>3</b><span><strong>Phục hồi bảo trì</strong><small>Tạm đóng hệ thống và khôi phục database cùng file.</small></span></div>
                <i class="fa-solid fa-arrow-right"></i>
                <div><b>4</b><span><strong>Tự động hoàn tác</strong><small>Quay về bản dự phòng nếu quá trình gặp sự cố.</small></span></div>
            </div>
        </section>
    </div>

    <div class="modal fade" id="restoreBackupModal" tabindex="-1" aria-labelledby="restoreBackupTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content restore-backup-modal" id="restoreBackupForm">
                @csrf
                <div class="modal-body">
                    <span class="restore-modal-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
                    <span class="system-eyebrow text-danger">THAO TÁC QUẢN TRỊ NHẠY CẢM</span>
                    <h2 id="restoreBackupTitle">Phục hồi toàn bộ hệ thống</h2>
                    <p>Database hiện tại sẽ được thay bằng dữ liệu trong <strong id="restoreBackupFilename"></strong>. Hệ thống có thể tạm ngừng truy cập trong quá trình này.</p>
                    <div class="restore-modal-warning"><i class="fa-solid fa-shield-halved"></i><span>SmartLMS sẽ tự tạo một bản dự phòng mới trước khi bắt đầu phục hồi.</span></div>
                    <div class="restore-modal-field">
                        <label for="restoreConfirmation">Nhập <code>KHOI PHUC</code> để xác nhận</label>
                        <input class="form-control @error('confirmation', 'restoreBackup') is-invalid @enderror" id="restoreConfirmation" name="confirmation" autocomplete="off" required>
                        @error('confirmation', 'restoreBackup')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="restore-modal-field">
                        <label for="restoreCurrentPassword">Mật khẩu hiện tại của quản trị viên</label>
                        <input type="password" class="form-control @error('current_password', 'restoreBackup') is-invalid @enderror" id="restoreCurrentPassword" name="current_password" autocomplete="current-password" required>
                        @error('current_password', 'restoreBackup')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="system-button system-button--neutral" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="system-button system-button--danger"><i class="fa-solid fa-clock-rotate-left"></i> Tạo dự phòng và phục hồi</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modalElement = document.getElementById('restoreBackupModal');
            const form = document.getElementById('restoreBackupForm');
            const filename = document.getElementById('restoreBackupFilename');

            document.querySelectorAll('.js-backup-create-form').forEach(createForm => {
                createForm.addEventListener('submit', () => {
                    document.querySelectorAll('.js-backup-create-form button').forEach(button => {
                        button.disabled = true;
                        button.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> ${button.dataset.busyLabel}`;
                    });
                });
            });

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
