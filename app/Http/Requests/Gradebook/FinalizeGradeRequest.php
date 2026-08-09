<?php

namespace App\Http\Requests\Gradebook;

use App\Models\GradingPeriod;
use Illuminate\Foundation\Http\FormRequest;

class FinalizeGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $period = $this->route('period');

        return $period instanceof GradingPeriod && $this->user()->can('finalize', $period);
    }

    public function rules(): array
    {
        return [];
    }
}
