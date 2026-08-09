<?php

namespace App\Http\Requests\Assignment;

use Illuminate\Foundation\Http\FormRequest;

class CreateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'exists:courses,id'],
            'lesson_id' => ['required', 'exists:lessons,id'],
            'type' => ['required', 'in:file,essay,mixed'],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string'],
            'grading_rubric' => ['nullable', 'string'],
            'grading_scale' => ['nullable', 'integer', 'min:1', 'max:100'],
            'ai_grading_enabled' => ['nullable', 'boolean'],
            'due_date' => ['required', 'date'],
            'allowed_extensions' => ['nullable', 'string'],
            'max_file_size' => ['nullable', 'integer'],
            'status' => ['required', 'in:draft,published,hidden,archived'],
            'available_from' => ['nullable', 'date'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'course_id.required' => 'Vui lòng chọn khóa học.',
            'course_id.exists' => 'Khóa học đã chọn không tồn tại.',
            'lesson_id.required' => 'Vui lòng chọn bài học.',
            'lesson_id.exists' => 'Bài học đã chọn không tồn tại.',
            'type.in' => 'Loại bài tập không hợp lệ.',
            'title.required' => 'Vui lòng nhập tên bài tập.',
            'instructions.required' => 'Vui lòng nhập yêu cầu bài tập.',
            'due_date.required' => 'Vui lòng nhập hạn nộp.',
            'status.in' => 'Trạng thái bài tập không hợp lệ.',
        ];
    }

    /** @return array<string, mixed> */
    public function assignmentData(): array
    {
        return array_merge($this->validated(), [
            'ai_grading_enabled' => $this->boolean('ai_grading_enabled'),
        ]);
    }
}
