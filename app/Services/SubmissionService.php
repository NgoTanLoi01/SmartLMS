<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\Assignment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class SubmissionService
{
    public function submitWork($request, $studentId)
    {
        $assignment = Assignment::findOrFail($request->assignment_id);

        // Kiểm tra deadline
        if (now()->gt($assignment->due_date)) {
            throw new Exception('Đã quá hạn nộp bài!');
        }

        // Validate file
        $request->validate([
            'file' => ['required', 'file', 'max:' . $assignment->max_file_size, 'mimes:' . ($assignment->allowed_extensions ?? 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,html,htm,zip,rar')],
        ]);

        // Kiểm tra bài cũ
        $oldSubmission = Submission::where([
            'assignment_id' => $assignment->id,
            'student_id' => $studentId,
        ])->first();

        // Xóa file cũ
        if ($oldSubmission && $oldSubmission->file_path) {
            Storage::disk('public')->delete($oldSubmission->file_path);
        }

        // Upload file
        $file = $request->file('file');

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs('submissions', $filename, 'public');

        return Submission::updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $studentId,
            ],
            [
                'file_path' => $path,
                'submitted_at' => now(),
            ],
        );
    }

    public function gradeWork($submissionId, $gradeData)
    {
        $submission = Submission::findOrFail($submissionId);

        if ($gradeData['grade'] < 0 || $gradeData['grade'] > 10) {
            throw new Exception('Điểm số không hợp lệ.');
        }

        return $submission->update([
            'grade' => $gradeData['grade'],
            'feedback' => $gradeData['feedback'] ?? null,
        ]);
    }
}
