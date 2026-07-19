<?php

namespace App\Services;

use App\Models\AssignmentSubmission;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionFileService
{
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
