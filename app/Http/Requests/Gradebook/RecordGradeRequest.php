<?php

namespace App\Http\Requests\Gradebook;

use App\Models\Grade;
use App\Models\GradeItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $item instanceof GradeItem
            && $item->course
            && $this->user()->can('update', $item->course);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                Grade::STATUS_UNGRADED, Grade::STATUS_MISSING, Grade::STATUS_EXCUSED,
                Grade::STATUS_GRADED, Grade::STATUS_EXCLUDED,
            ])],
            'raw_points' => ['nullable', 'required_if:status,'.Grade::STATUS_GRADED, 'numeric', 'min:0'],
            'expected_version' => ['nullable', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
