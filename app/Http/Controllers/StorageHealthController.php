<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageHealthController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();

        return view('system.storage', [
            'summary' => $this->buildSummary(),
            'lastResult' => session('storage_test_result'),
        ]);
    }

    public function test(Request $request)
    {
        $this->authorizeAdmin();

        $diskName = $request->input('disk', 'r2');
        if (! in_array($diskName, ['r2', 'public'], true)) {
            abort(422, 'Disk không hợp lệ.');
        }

        $path = 'health-checks/smartlms-storage-test-'.now()->format('YmdHis').'-'.Str::random(8).'.txt';
        $content = 'SmartLMS storage test at '.now()->toDateTimeString();

        try {
            $disk = Storage::disk($diskName);
            $disk->put($path, $content);

            $exists = $disk->exists($path);
            if ($exists) {
                $disk->delete($path);
            }

            return back()->with('storage_test_result', [
                'ok' => $exists,
                'disk' => $diskName,
                'message' => $exists
                    ? 'Kết nối thành công. Hệ thống đã upload, kiểm tra và xóa file test.'
                    : 'Upload xong nhưng không kiểm tra được file test.',
                'path' => $path,
                'checked_at' => now()->format('H:i d/m/Y'),
            ]);
        } catch (\Throwable $e) {
            return back()->with('storage_test_result', [
                'ok' => false,
                'disk' => $diskName,
                'message' => $e->getMessage(),
                'path' => $path,
                'checked_at' => now()->format('H:i d/m/Y'),
            ]);
        }
    }

    private function buildSummary(): array
    {
        $r2 = config('filesystems.disks.r2', []);
        $submissionDisk = config('filesystems.submission_disk', 'public');

        return [
            'submission_disk' => $submissionDisk,
            'r2_ready' => filled($r2['key'] ?? null)
                && filled($r2['secret'] ?? null)
                && filled($r2['bucket'] ?? null)
                && filled($r2['endpoint'] ?? null),
            'r2_bucket' => $r2['bucket'] ?? null,
            'r2_endpoint' => $r2['endpoint'] ?? null,
            'r2_region' => $r2['region'] ?? 'auto',
            'r2_key' => $this->maskValue($r2['key'] ?? null),
            'r2_secret_configured' => filled($r2['secret'] ?? null),
        ];
    }

    private function maskValue(?string $value): string
    {
        if (! filled($value)) {
            return 'Chưa cấu hình';
        }

        return Str::limit($value, 8, '').'...'.Str::substr($value, -6);
    }

    private function authorizeAdmin(): void
    {
        if (auth()->user()?->role !== 'admin') {
            abort(403, 'Chỉ quản trị viên được kiểm tra cấu hình lưu trữ.');
        }
    }
}
