<?php

namespace App\Http\Controllers;

use App\Models\QuizAttempt;
use App\Models\QuizAttemptAttachment;
use App\Models\QuizAttemptQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuizAttemptAttachmentController extends Controller
{
    public function store(Request $request, QuizAttempt $attempt, QuizAttemptQuestion $question)
    {
        Gate::authorize('update', $attempt);
        abort_unless($attempt->isInProgress() && $attempt->expires_at?->isFuture(), 409, 'Bài thi đã kết thúc, không thể thay đổi tệp đính kèm.');
        abort_unless((int) $question->quiz_attempt_id === (int) $attempt->id, 404);
        abort_unless($question->question_type === 'essay' && data_get($question->response_schema_snapshot, 'allow_attachments'), 422, 'Câu hỏi không cho phép đính kèm.');

        $maxFiles = (int) data_get($question->response_schema_snapshot, 'max_files', 3);
        if ($question->attachments()->count() >= $maxFiles) {
            throw ValidationException::withMessages(['file' => "Mỗi câu chỉ được đính kèm tối đa {$maxFiles} tệp."]);
        }

        $maxKilobytes = (int) data_get($question->response_schema_snapshot, 'max_file_size_kb', 10240);
        $allowed = data_get($question->response_schema_snapshot, 'allowed_extensions', ['pdf', 'doc', 'docx', 'txt', 'png', 'jpg', 'jpeg']);
        $data = $request->validate([
            'file' => ['required', 'file', 'max:'.$maxKilobytes, 'mimes:'.implode(',', $allowed)],
        ]);

        $file = $data['file'];
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs(
            "quiz-attempts/{$attempt->id}/{$question->id}",
            Str::uuid().'.'.$extension,
            'local'
        );

        $attachment = QuizAttemptAttachment::create([
            'quiz_attempt_id' => $attempt->id,
            'quiz_attempt_question_id' => $question->id,
            'uploaded_by' => $request->user()->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json([
            'id' => $attachment->id,
            'name' => $attachment->original_name,
            'size' => $attachment->size,
            'download_url' => route('quiz-attempt-attachments.download', $attachment),
            'delete_url' => route('quiz-attempt-attachments.destroy', $attachment),
        ], 201);
    }

    public function download(QuizAttemptAttachment $attachment)
    {
        $attachment->load('attempt.quiz.course');
        Gate::authorize('view', $attachment->attempt);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function destroy(QuizAttemptAttachment $attachment)
    {
        $attachment->load('attempt');
        Gate::authorize('update', $attachment->attempt);
        abort_unless($attachment->attempt->isInProgress() && $attachment->attempt->expires_at?->isFuture(), 409, 'Bài thi đã kết thúc, không thể xóa tệp đính kèm.');
        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        return response()->json(['deleted' => true]);
    }
}
