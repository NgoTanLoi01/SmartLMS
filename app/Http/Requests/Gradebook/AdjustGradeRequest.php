<?php

namespace App\Http\Requests\Gradebook;

use App\Models\Grade;
use App\Models\GradeAdjustment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $grade = $this->route('grade');

        return $grade instanceof Grade && $this->user()->can('update', $grade);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([
                GradeAdjustment::TYPE_BONUS,
                GradeAdjustment::TYPE_PENALTY,
                GradeAdjustment::TYPE_OVERRIDE,
            ])],
            'amount' => ['required', 'numeric', 'min:0', 'decimal:0,4'],
            'reason' => ['required', 'string', 'max:2000'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }
}
