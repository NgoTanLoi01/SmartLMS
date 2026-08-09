<?php

namespace App\Services;

use App\Models\AssignmentSubmission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionFileService
{
    /** @return array{path:string,disk:string,original_filename:string,mime_type:?string,file_size:int,checksum_sha256:string} */
    public function store(UploadedFile $file, int $assignmentId, int $userId): array
    {
        $diskName = (string) config('filesystems.submission_disk', 'local');
        $originalFilename = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $safeName = Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) ?: 'submission';
        $storedName = $safeName.'-'.now()->format('YmdHis').'-'.Str::random(8).($extension ? '.'.$extension : '');
        $directory = "assignments/{$assignmentId}/students/{$userId}";
        $expectedSize = (int) $file->getSize();
        $checksum = hash_file('sha256', $file->getRealPath());
        if ($checksum === false) {
            throw new RuntimeException('Không thể tạo checksum cho file bài nộp.');
        }

        $path = retry(3, function () use ($file, $diskName, $directory, $storedName, $expectedSize): string {
            $storedPath = $file->storeAs($directory, $storedName, $diskName);
            if (! is_string($storedPath) || $storedPath === '') {
                throw new RuntimeException('Storage không trả về đường dẫn file bài nộp.');
            }

            $disk = Storage::disk($diskName);
            if (! $disk->exists($storedPath) || $disk->size($storedPath) !== $expectedSize) {
                $disk->delete($storedPath);
                throw new RuntimeException('File bài nộp không vượt qua bước kiểm tra sau upload.');
            }

            return $storedPath;
        }, 100);

        return [
            'path' => $path,
            'disk' => $diskName,
            'original_filename' => $originalFilename,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $expectedSize,
            'checksum_sha256' => $checksum,
        ];
    }

    public function url(?AssignmentSubmission $submission): ?string
    {
        return $submission?->file_path
            ? route('assignments.submissions.file', $submission->id)
            : null;
    }

    public function previewUrl(?AssignmentSubmission $submission): ?string
    {
        return $submission?->file_path && $this->previewType($submission)
            ? route('assignments.submissions.preview', $submission->id)
            : null;
    }

    public function previewType(?AssignmentSubmission $submission): ?string
    {
        if (! $submission?->file_path) {
            return null;
        }

        $extension = strtolower(pathinfo($submission->original_filename ?: $submission->file_path, PATHINFO_EXTENSION));
        $mimeType = strtolower((string) $submission->mime_type);

        return match (true) {
            $extension === 'pdf' || $mimeType === 'application/pdf' => 'pdf',
            in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true) || str_starts_with($mimeType, 'image/') => 'image',
            in_array($extension, ['html', 'htm'], true) || in_array($mimeType, ['text/html', 'application/xhtml+xml'], true) => 'html',
            default => null,
        };
    }

    public function diskName(AssignmentSubmission $submission): string
    {
        return $submission->file_disk ?: 'public';
    }

    public function delete(AssignmentSubmission $submission): void
    {
        if (! $submission->file_path) {
            return;
        }

        $disk = Storage::disk($this->diskName($submission));
        if ($disk->exists($submission->file_path)) {
            $disk->delete($submission->file_path);
        }
    }

    public function deletePath(?string $path, ?string $diskName): void
    {
        if (! $path) {
            return;
        }

        $disk = Storage::disk($diskName ?: 'public');
        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    public function download(AssignmentSubmission $submission): StreamedResponse
    {
        abort_unless($submission->file_path, 404, 'Bài nộp không có file đính kèm.');

        $disk = Storage::disk($this->diskName($submission));
        abort_unless($disk->exists($submission->file_path), 404, 'Không tìm thấy file bài nộp.');

        return $disk->download(
            $submission->file_path,
            $submission->original_filename ?: basename($submission->file_path)
        );
    }

    public function preview(AssignmentSubmission $submission): StreamedResponse
    {
        abort_unless($submission->file_path && $this->previewType($submission), 404, 'File này không hỗ trợ xem trước.');

        $disk = Storage::disk($this->diskName($submission));
        abort_unless($disk->exists($submission->file_path), 404, 'Không tìm thấy file bài nộp.');

        $stream = $disk->readStream($submission->file_path);
        abort_if($stream === false, 404, 'Không thể đọc file bài nộp.');

        $fileName = str_replace(["\r", "\n", '"'], ['', '', "'"], $submission->original_filename ?: basename($submission->file_path));

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $this->previewContentType($submission),
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; img-src data:; font-src data:",
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function previewContentType(AssignmentSubmission $submission): string
    {
        return match ($this->previewType($submission)) {
            'pdf' => 'application/pdf',
            'image' => $submission->mime_type ?: $this->imageContentTypeFromExtension($submission),
            'html' => 'text/html; charset=UTF-8',
            default => 'application/octet-stream',
        };
    }

    private function imageContentTypeFromExtension(AssignmentSubmission $submission): string
    {
        return match (strtolower(pathinfo($submission->original_filename ?: $submission->file_path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }
}
