<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\Assignment;
use Illuminate\Support\Facades\Storage;
use Exception;

class SubmissionService 
{
    /**
     * Xử lý nộp bài tập
     */
    public function submitWork($data, $studentId) 
    {
        $assignment = Assignment::findOrFail($data['assignment_id']);

        // 1. Kiểm tra hạn nộp (Logic nghiệp vụ)
        if (now()->gt($assignment->due_date)) {
            throw new Exception("Đã quá hạn nộp bài!");
        }

        // 2. Xử lý lưu file vào thư mục bảo mật 'private/submissions'
        // Không dùng public để tránh học sinh khác vào xem trộm bài
        if ($data->hasFile('file')) {
            $path = $data->file('file')->store('submissions', 'local');
        }

        // 3. Lưu vào Database
        return Submission::updateOrCreate(
            [
                'assignment_id' => $data['assignment_id'],
                'student_id' => $studentId,
            ],
            [
                'file_path' => $path,
                'submitted_at' => now(),
            ]
        );
    }

    /**
     * Xử lý chấm điểm (Chỉ Teacher/Admin)
     */
    public function gradeWork($submissionId, $gradeData) 
    {
        $submission = Submission::findOrFail($submissionId);

        // Logic: Điểm phải nằm trong khoảng 0-10
        if ($gradeData['grade'] < 0 || $gradeData['grade'] > 10) {
            throw new Exception("Điểm số không hợp lệ.");
        }

        return $submission->update([
            'grade' => $gradeData['grade'],
            'feedback' => $gradeData['feedback'] ?? null
        ]);
    }
}