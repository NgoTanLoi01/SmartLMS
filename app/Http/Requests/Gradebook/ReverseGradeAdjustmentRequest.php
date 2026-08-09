<?php

namespace App\Http\Requests\Gradebook;

use App\Models\GradeAdjustment;
use Illuminate\Foundation\Http\FormRequest;

class ReverseGradeAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $adjustment = $this->route('adjustment');

        return $adjustment instanceof GradeAdjustment
            && $adjustment->grade
            && $this->user()->can('update', $adjustment->grade);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }
}
