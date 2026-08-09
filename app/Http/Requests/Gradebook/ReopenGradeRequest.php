<?php

namespace App\Http\Requests\Gradebook;

class ReopenGradeRequest extends FinalizeGradeRequest
{
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:2000']];
    }
}
