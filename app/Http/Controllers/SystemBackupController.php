<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BackupRun;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

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

        $backup = $backupService->runDatabaseBackup([
            'user_id' => $request->user()->id,
            'triggered_by' => 'manual',
            'upload_r2' => $request->boolean('upload_r2'),
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $backup->isSuccessful() ? 'backup.created' : 'backup.failed',
            'auditable_type' => BackupRun::class,
            'auditable_id' => $backup->id,
            'description' => $backup->isSuccessful()
                ? 'Tạo backup database thủ công.'
                : 'Tạo backup database thủ công thất bại.',
            'metadata' => [
                'filename' => $backup->filename,
                'size_bytes' => $backup->size_bytes,
                'remote_disk' => $backup->remote_disk,
                'remote_path' => $backup->remote_path,
                'error' => $backup->error_message,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($backup->isSuccessful()) {
            return back()->with('success', 'Đã tạo backup database thành công.');
        }

        return back()->with('error', 'Backup thất bại: ' . $backup->error_message);
    }

    public function download(Request $request, BackupRun $backup)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if (!$backup->isSuccessful()) {
            abort(404);
        }

        if ($backup->localFileExists()) {
            return Response::download($backup->local_path, $backup->filename);
        }

        if ($backup->remote_disk && $backup->remote_path && Storage::disk($backup->remote_disk)->exists($backup->remote_path)) {
            return response()->streamDownload(function () use ($backup) {
                echo Storage::disk($backup->remote_disk)->get($backup->remote_path);
            }, $backup->filename);
        }

        return back()->with('error', 'Không tìm thấy file backup để tải xuống.');
    }

    private function summary(): array
    {
        $r2 = config('filesystems.disks.r2', []);

        return [
            'local_directory' => config('backup.local_directory'),
            'keep_local_copies' => config('backup.keep_local_copies'),
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
