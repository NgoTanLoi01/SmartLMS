<?php

namespace App\Http\Requests\Gradebook;

use App\Models\GradeItem;
use Illuminate\Foundation\Http\FormRequest;

class SetGradeItemLockRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $item instanceof GradeItem && $item->course && $this->user()->can('update', $item->course);
    }

    public function rules(): array
    {
        return ['expected_version' => ['nullable', 'integer', 'min:1']];
    }
}
