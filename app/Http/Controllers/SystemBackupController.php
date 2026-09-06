<?php

namespace App\Http\Controllers;

use App\Models\BackupRun;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\BackupRestoreService;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class SystemBackupController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $backups = BackupRun::query()
            ->with('user')
            ->latest('started_at')
            ->paginate(12)
            ->withQueryString();

        $latestSuccessful = BackupRun::query()
            ->where('status', 'success')
            ->latest('finished_at')
            ->first();

        $latestFailed = BackupRun::query()
            ->where('status', 'failed')
            ->latest('finished_at')
            ->first();

        return view('system.backups', [
            'backups' => $backups,
            'latestSuccessful' => $latestSuccessful,
            'latestFailed' => $latestFailed,
            'summary' => $this->summary(),
        ]);
    }

    public function store(Request $request, BackupService $backupService)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        set_time_limit(0);

        $lock = Cache::lock(BackupService::LOCK_NAME, max(60, (int) config('backup.restore_lock_seconds', 3600)));
        if (! $lock->get()) {
            return back()->with('error', 'Một tiến trình backup/khôi phục khác đang chạy. Vui lòng thử lại sau.');
        }

        try {
            $backup = $backupService->runFullBackup([
                'user_id' => $request->user()->id,
                'triggered_by' => 'manual',
                'upload_r2' => $request->boolean('upload_r2'),
            ]);
        } finally {
            $lock->release();
        }

        $this->writeAudit(
            $request,
            $backup->isSuccessful() ? 'backup.created' : 'backup.failed',
            $backup,
            $backup->isSuccessful()
                ? 'Tạo gói sao lưu toàn hệ thống thủ công.'
                : 'Tạo gói sao lưu toàn hệ thống thủ công thất bại.',
            ['error' => $backup->error_message]
        );

        if ($backup->isSuccessful()) {
            $missingFiles = (int) ($backup->metadata['missing_files'] ?? 0);
            $message = 'Đã sao lưu database và file hệ thống thành công.';
            if ($missingFiles > 0) {
                $message .= " Có {$missingFiles} file nguồn không tồn tại và đã được ghi trong manifest.";
            }
            if ($backup->metadata['remote_upload_error'] ?? null) {
                $message .= ' Tuy nhiên, upload lên kho từ xa thất bại: '.$backup->metadata['remote_upload_error'];
            }

            return back()->with('success', $message);
        }

        return back()->with('error', 'Sao lưu thất bại: '.$backup->error_message);
    }

    public function verify(Request $request, BackupRun $backup, BackupRestoreService $restoreService)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        set_time_limit(0);

        $result = $restoreService->verifyBackup($backup);
        $this->writeAudit(
            $request,
            $result['valid'] ? 'backup.verified' : 'backup.verification_failed',
            $backup,
            $result['valid'] ? 'Kiểm tra tính toàn vẹn gói backup thành công.' : 'Kiểm tra tính toàn vẹn gói backup thất bại.',
            $result
        );

        return back()->with($result['valid'] ? 'success' : 'error', $result['message']);
    }

    public function restore(
        Request $request,
        BackupRun $backup,
        BackupService $backupService,
        BackupRestoreService $restoreService
    ) {
        abort_unless($request->user()?->isAdmin(), 403);

        $validator = Validator::make($request->all(), [
            'confirmation' => ['required', 'string', 'in:KHOI PHUC'],
            'current_password' => ['required', 'string'],
        ], [
            'confirmation.in' => 'Vui lòng nhập chính xác KHOI PHUC để xác nhận.',
            'confirmation.required' => 'Vui lòng nhập cụm từ xác nhận.',
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
        ]);
        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'restoreBackup')
                ->with('restore_backup_id', $backup->id);
        }
        $validated = $validator->validated();

        if (! Hash::check($validated['current_password'], $request->user()->password)) {
            return back()
                ->withErrors(['current_password' => 'Mật khẩu hiện tại không chính xác.'], 'restoreBackup')
                ->with('restore_backup_id', $backup->id);
        }

        if (! $backup->isRestorable()) {
            return back()->with('error', 'Backup này là định dạng cũ hoặc không đủ dữ liệu để phục hồi an toàn.');
        }

        $lock = Cache::lock(BackupService::LOCK_NAME, max(60, (int) config('backup.restore_lock_seconds', 3600)));
        if (! $lock->get()) {
            return back()->with('error', 'Một tiến trình backup/khôi phục khác đang chạy. Vui lòng thử lại sau.');
        }

        set_time_limit(0);
        $preRestoreBackup = null;
        $targetSnapshot = $backup->getAttributes();
        $maintenanceEnabled = false;

        try {
            $verification = $restoreService->verifyBackup($backup);
            if (! $verification['valid']) {
                $this->writeAudit($request, 'backup.restore_rejected', $backup, 'Từ chối phục hồi do gói backup không toàn vẹn.', $verification);

                return back()->with('error', $verification['message']);
            }

            $targetSnapshot = $backup->getAttributes();
            Artisan::call('down', ['--secret' => Str::random(48), '--retry' => 60]);
            $maintenanceEnabled = true;

            $preRestoreBackup = $backupService->runFullBackup([
                'user_id' => $request->user()->id,
                'triggered_by' => 'pre_restore',
                'skip_prune' => true,
            ]);
            if (! $preRestoreBackup->isSuccessful()) {
                $this->writeAudit(
                    $request,
                    'backup.restore_rejected',
                    $backup,
                    'Từ chối phục hồi vì không tạo được backup dự phòng trước thao tác.',
                    ['error' => $preRestoreBackup->error_message]
                );

                return back()->with('error', 'Không thể tạo backup dự phòng trước khi phục hồi: '.$preRestoreBackup->error_message);
            }

            $preRestoreSnapshot = $preRestoreBackup->getAttributes();
            $result = $restoreService->restoreBackup($backup);

            $this->preserveBackupRecord($targetSnapshot);
            $this->preserveBackupRecord($preRestoreSnapshot);
            $restoredBackup = BackupRun::find($backup->id);
            $this->writeAudit(
                $request,
                'backup.restored',
                $restoredBackup,
                'Phục hồi hệ thống từ gói backup thành công.',
                [
                    'restored_filename' => $targetSnapshot['filename'] ?? null,
                    'pre_restore_filename' => $preRestoreSnapshot['filename'] ?? null,
                    'restored_files' => $result['restored_files'],
                    'restored_vector_rows' => $result['restored_vector_rows'] ?? 0,
                    'missing_files_at_backup' => $result['missing_files_at_backup'],
                ]
            );

            return redirect()->route('system.backups.index')
                ->with('success', "Đã phục hồi hệ thống thành công, gồm {$result['restored_files']} file và ".($result['restored_vector_rows'] ?? 0).' đoạn dữ liệu AI. Bản dự phòng trước phục hồi đã được giữ lại.');
        } catch (Throwable $e) {
            report($e);
            $rollbackSucceeded = false;
            $rollbackError = null;

            if ($preRestoreBackup?->isSuccessful()) {
                try {
                    $preRestoreSnapshot ??= $preRestoreBackup->getAttributes();
                    $restoreService->restoreBackup($preRestoreBackup);
                    $this->preserveBackupRecord($targetSnapshot);
                    $this->preserveBackupRecord($preRestoreSnapshot);
                    $rollbackSucceeded = true;
                } catch (Throwable $rollbackException) {
                    report($rollbackException);
                    $rollbackError = $rollbackException->getMessage();
                }
            }

            $this->writeAudit(
                $request,
                'backup.restore_failed',
                BackupRun::find($backup->id),
                'Phục hồi hệ thống thất bại.'.($rollbackSucceeded ? ' Dữ liệu trước thao tác đã được hoàn tác.' : ''),
                [
                    'restored_filename' => $targetSnapshot['filename'] ?? null,
                    'error' => $e->getMessage(),
                    'rollback_succeeded' => $rollbackSucceeded,
                    'rollback_error' => $rollbackError,
                ]
            );

            $message = 'Phục hồi thất bại: '.$e->getMessage();
            $message .= $rollbackSucceeded
                ? ' Hệ thống đã tự động quay lại trạng thái trước thao tác.'
                : ' Không thể tự động hoàn tác; cần kiểm tra bản backup dự phòng và log máy chủ ngay.';

            return redirect()->route('system.backups.index')->with('error', $message);
        } finally {
            if ($maintenanceEnabled) {
                rescue(fn () => Artisan::call('up'), report: true);
            }
            $lock->release();
        }
    }

    public function download(Request $request, BackupRun $backup)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if (! $backup->isSuccessful()) {
            abort(404);
        }

        if ($backup->localFileExists()) {
            return Response::download($backup->local_path, $backup->filename);
        }

        if ($backup->remote_disk && $backup->remote_path && Storage::disk($backup->remote_disk)->exists($backup->remote_path)) {
            return response()->streamDownload(function () use ($backup) {
                $stream = Storage::disk($backup->remote_disk)->readStream($backup->remote_path);
                if (is_resource($stream)) {
                    fpassthru($stream);
                    fclose($stream);
                }
            }, $backup->filename);
        }

        return back()->with('error', 'Không tìm thấy file backup để tải xuống.');
    }

    private function preserveBackupRecord(array $attributes): void
    {
        $id = $attributes['id'] ?? null;
        if (! $id) {
            return;
        }

        unset($attributes['id']);
        if (! empty($attributes['user_id']) && ! User::whereKey($attributes['user_id'])->exists()) {
            $attributes['user_id'] = null;
        }

        BackupRun::query()->updateOrCreate(['id' => $id], $attributes);
    }

    private function writeAudit(
        Request $request,
        string $action,
        ?BackupRun $backup,
        string $description,
        array $metadata = []
    ): void {
        try {
            AuditLogger::log(
                $action,
                $backup,
                null,
                null,
                array_merge([
                    'filename' => $backup?->filename,
                    'size_bytes' => $backup?->size_bytes,
                    'remote_disk' => $backup?->remote_disk,
                    'remote_path' => $backup?->remote_path,
                ], $metadata),
                $description
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function summary(): array
    {
        $r2 = config('filesystems.disks.r2', []);

        return [
            'local_directory' => config('backup.local_directory'),
            'keep_local_copies' => config('backup.keep_local_copies'),
            'file_disks' => config('backup.file_disks', []),
            'schedule_enabled' => (bool) config('backup.schedule.enabled'),
            'schedule_time' => config('backup.schedule.time'),
            'schedule_upload_r2' => (bool) config('backup.schedule.upload_to_r2'),
            'r2_ready' => filled($r2['key'] ?? null)
                && filled($r2['secret'] ?? null)
                && filled($r2['bucket'] ?? null)
                && filled($r2['endpoint'] ?? null),
            'r2_bucket' => $r2['bucket'] ?? null,
        ];
    }
}
