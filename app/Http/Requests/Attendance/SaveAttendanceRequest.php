<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class SaveAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'data' => ['nullable', 'array', 'max:500'],
            'data.*' => ['array', 'max:500'],
            'data.*.*' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'array', 'max:500'],
            'notes.*' => ['array', 'max:500'],
            'notes.*.*' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'data.array' => 'Dữ liệu điểm danh không hợp lệ.',
            'data.max' => 'Mỗi lần chỉ được lưu tối đa 500 cột.',
            'data.*.array' => 'Danh sách học viên của cột không hợp lệ.',
            'data.*.max' => 'Mỗi cột chỉ được lưu tối đa 500 học viên.',
            'data.*.*.max' => 'Giá trị điểm danh không được vượt quá 255 ký tự.',
            'notes.array' => 'Dữ liệu ghi chú không hợp lệ.',
            'notes.*.array' => 'Danh sách ghi chú của cột không hợp lệ.',
            'notes.*.*.max' => 'Ghi chú không được vượt quá 2000 ký tự.',
        ];
    }
}
