<?php

namespace App\Services;

use App\Models\Assignments;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\ZipStream;

class SubmissionArchiveService
{
    public function __construct(private SubmissionFileService $files) {}

    public function download(Assignments $assignment, Collection $submissions): StreamedResponse
    {
        $archiveName = 'Bai_nop_'.Str::slug($assignment->title, '_').'_'.now()->format('Y-m-d_His').'.zip';

        return response()->streamDownload(function () use ($submissions, $assignment) {
            $zip = new ZipStream(outputStream: fopen('php://output', 'wb'), sendHttpHeaders: false);
            $usedNames = [];
            $csv = fopen('php://temp', 'w+b');
            fwrite($csv, "\xEF\xBB\xBF");
            fputcsv($csv, ['Mã học sinh', 'Họ và tên', 'Email', 'Thời gian nộp', 'Trạng thái', 'Tên file', 'Điểm', 'Nhận xét'], ',', '"', '');

            foreach ($submissions as $submission) {
                $student = $submission->user;
                $isLate = $assignment->due_date && $submission->submitted_at?->gt($assignment->due_date);
                $archiveFileName = '';

                if ($submission->file_path) {
                    $disk = Storage::disk($this->files->diskName($submission));
                    if ($disk->exists($submission->file_path)) {
                        $originalName = $submission->original_filename ?: basename($submission->file_path);
                        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                        $studentName = Str::slug($student?->name ?: 'hoc_sinh', '_');
                        $archiveFileName = $studentName.($extension ? '.'.strtolower($extension) : '');
                        $archiveFileName = $this->uniqueName($archiveFileName, $usedNames);
                        $stream = $disk->readStream($submission->file_path);

                        if (is_resource($stream)) {
                            $zip->addFileFromStream(fileName: 'files/'.$archiveFileName, stream: $stream);
                            fclose($stream);
                        } else {
                            $archiveFileName = '';
                        }
                    }
                }

                fputcsv($csv, [
                    $student?->student_code,
                    $student?->name,
                    $student?->email,
                    $submission->formatSubmittedAt('d/m/Y H:i:s'),
                    $isLate ? 'Nộp muộn' : 'Đúng hạn',
                    $archiveFileName ?: ($submission->file_path ? 'Không tìm thấy file' : 'Chỉ nộp nội dung tự luận'),
                    $submission->grade,
                    $submission->feedback,
                ], ',', '"', '');
            }

            rewind($csv);
            $zip->addFileFromStream(fileName: 'Danh_sach_bai_nop.csv', stream: $csv);
            fclose($csv);
            $zip->finish();
        }, $archiveName, ['Content-Type' => 'application/zip']);
    }

    private function uniqueName(string $name, array &$usedNames): string
    {
        $candidate = $name;
        $counter = 2;
        while (isset($usedNames[Str::lower($candidate)])) {
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $stem = pathinfo($name, PATHINFO_FILENAME);
            $candidate = $stem.'_'.$counter++.($extension ? '.'.$extension : '');
        }

        $usedNames[Str::lower($candidate)] = true;

        return $candidate;
    }
}
