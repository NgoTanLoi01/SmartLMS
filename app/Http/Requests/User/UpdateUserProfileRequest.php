<?php

namespace App\Http\Requests\User;

use App\Models\User;
use App\Support\StudentLoginCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserProfileRequest extends FormRequest
{
    protected $errorBag = 'editUser';

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        /** @var User|null $target */
        $target = $this->route('user');

        $this->merge([
            'editing_user_id' => $target?->getKey(),
            'name' => trim((string) $this->input('name')),
            'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email'))) : null,
            'username' => $this->filled('username') ? mb_strtolower(trim((string) $this->input('username'))) : null,
            'student_code' => StudentLoginCode::normalizeStudentCode($this->input('student_code')),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var User|null $target */
        $target = $this->route('user');
        $isStudent = $target?->isStudent() === true;

        return [
            'editing_user_id' => ['required', 'integer', Rule::in([$target?->getKey()])],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                Rule::requiredIf(! $isStudent),
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($target),
            ],
            'username' => [
                Rule::requiredIf($isStudent),
                Rule::prohibitedIf(! $isStudent),
                'nullable',
                'string',
                'max:220',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($target),
            ],
            'student_code' => [
                Rule::prohibitedIf(! $isStudent),
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'student_code')->ignore($target),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'editing_user_id.in' => 'Tài khoản cần sửa không hợp lệ.',
            'name.required' => 'Vui lòng nhập họ và tên.',
            'email.required' => 'Email là bắt buộc với giáo viên và quản trị viên.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'username.required' => 'Vui lòng nhập tên đăng nhập cho học viên.',
            'username.regex' => 'Tên đăng nhập chỉ gồm chữ thường không dấu, số, dấu chấm, gạch ngang hoặc gạch dưới.',
            'username.unique' => 'Tên đăng nhập này đã được sử dụng.',
            'student_code.unique' => 'Mã học viên này đã được sử dụng.',
            'username.prohibited' => 'Chỉ tài khoản học viên mới có tên đăng nhập riêng.',
            'student_code.prohibited' => 'Chỉ tài khoản học viên mới có mã học viên.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var User|null $target */
            $target = $this->route('user');
            if (! $target?->isStudent() || $this->filled('email') || ! $this->filled('username')) {
                return;
            }

            $internalEmail = StudentLoginCode::emailFromUsername((string) $this->input('username'));
            $emailExists = User::query()
                ->where('email', $internalEmail)
                ->whereKeyNot($target->getKey())
                ->exists();

            if ($emailExists) {
                $validator->errors()->add(
                    'email',
                    'Email nội bộ tạo từ tên đăng nhập đã được sử dụng. Vui lòng chọn tên đăng nhập khác.'
                );
            }
        });
    }

    /** @return array<string, string|null> */
    public function profileData(): array
    {
        /** @var User $target */
        $target = $this->route('user');
        $data = [
            'name' => $this->validated('name'),
            'email' => $this->validated('email'),
        ];

        if ($target->isStudent()) {
            $data['username'] = $this->validated('username');
            $data['student_code'] = $this->validated('student_code');
        }

        return $data;
    }
}
