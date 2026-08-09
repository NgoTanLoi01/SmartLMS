<?php

namespace App\Http\Requests\Gradebook;

use App\Models\Course;
use App\Models\GradeItem;
use App\Models\GradingPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreGradebookSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $course instanceof Course && $this->user()->can('update', $course);
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in(['preview', 'apply'])],
            'period' => ['required', 'array'],
            'period.code' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'period.name' => ['required', 'string', 'max:255'],
            'period.starts_at' => ['nullable', 'date'],
            'period.ends_at' => ['nullable', 'date', 'after_or_equal:period.starts_at'],
            'period.missing_policy' => ['required', Rule::in([
                GradingPeriod::MISSING_BLOCK,
                GradingPeriod::MISSING_EXCLUDE,
                GradingPeriod::MISSING_ZERO,
            ])],
            'period.rounding_precision' => ['required', 'integer', 'between:0,4'],
            'categories' => ['required', 'array', 'min:1', 'max:10'],
            'categories.*.code' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9_-]+$/', 'distinct'],
            'categories.*.name' => ['required', 'string', 'max:255'],
            'categories.*.weight_percent' => ['required', 'numeric', 'gt:0', 'lte:100'],
            'categories.*.allow_over_max' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.enabled' => ['nullable', 'boolean'],
            'items.*.source_type' => ['required', Rule::in([
                GradeItem::SOURCE_LEGACY_ATTENDANCE,
                GradeItem::SOURCE_ASSIGNMENT,
                GradeItem::SOURCE_QUIZ,
            ])],
            'items.*.source_id' => ['required', 'integer', 'min:1'],
            'items.*.code' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.category_code' => ['required', 'string', 'max:80'],
            'items.*.item_type' => ['required', Rule::in([
                GradeItem::TYPE_MANUAL,
                GradeItem::TYPE_HS1,
                GradeItem::TYPE_HS2,
                GradeItem::TYPE_ASSIGNMENT,
                GradeItem::TYPE_QUIZ,
                GradeItem::TYPE_EXAM,
            ])],
            'items.*.item_weight' => ['required', 'numeric', 'gt:0'],
            'items.*.absence_policy' => ['nullable', Rule::in(['missing', 'excused', 'zero'])],
            'items.*.attempt_policy' => ['nullable', Rule::in(['highest_released', 'latest_released', 'first_released'])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $categories = collect($this->input('categories', []));
            $categoryCodes = $categories->pluck('code')->map(fn ($code): string => strtolower(trim((string) $code)));
            if ($categoryCodes->duplicates()->isNotEmpty()) {
                $validator->errors()->add('categories', 'Mã nhóm điểm không được trùng nhau.');
            }

            $weightTotal = $categories->reduce(
                fn (string $total, array $category): string => bcadd($total, (string) $category['weight_percent'], 4),
                '0'
            );
            if (bccomp($weightTotal, '100', 4) !== 0) {
                $validator->errors()->add('categories', "Tổng trọng số nhóm điểm phải bằng 100%, hiện là {$weightTotal}%.");
            }

            $enabled = collect($this->input('items', []))->filter(fn (array $item): bool => (bool) ($item['enabled'] ?? false));
            if ($enabled->isEmpty()) {
                $validator->errors()->add('items', 'Hãy chọn ít nhất một thành phần điểm.');
            }
            if ($enabled->pluck('code')->map(fn ($code): string => strtolower(trim((string) $code)))->duplicates()->isNotEmpty()) {
                $validator->errors()->add('items', 'Mã thành phần điểm được chọn không được trùng nhau.');
            }
            if ($enabled->contains(fn (array $item): bool => ! $categoryCodes->contains(strtolower(trim((string) $item['category_code']))))) {
                $validator->errors()->add('items', 'Mọi thành phần được chọn phải thuộc một nhóm điểm đã khai báo.');
            }
        }];
    }

    /** @return array<string,string> */
    public function messages(): array
    {
        return [
            'period.code.regex' => 'Mã kỳ điểm chỉ gồm chữ không dấu, số, gạch ngang hoặc gạch dưới.',
            'categories.*.code.regex' => 'Mã nhóm điểm chỉ gồm chữ không dấu, số, gạch ngang hoặc gạch dưới.',
            'items.*.code.regex' => 'Mã thành phần chỉ gồm chữ không dấu, số, gạch ngang hoặc gạch dưới.',
            'period.ends_at.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
        ];
    }
}
