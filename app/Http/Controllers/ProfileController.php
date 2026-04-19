<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function updatePassword(Request $request)
    {
        // 1. Validate dữ liệu
        $request->validate(
            [
                'current_password' => ['required', 'current_password'], // Laravel tự động check mật khẩu cũ
                'new_password' => ['required', 'min:6', 'confirmed'], // Yêu cầu nhập lại mật khẩu khớp nhau
            ],
            [
                'current_password.current_password' => 'Mật khẩu hiện tại không đúng.',
                'new_password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
                'new_password.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            ],
        );

        // 2. Cập nhật mật khẩu mới vào Database
        auth()
            ->user()
            ->update([
                'password' => Hash::make($request->new_password),
            ]);

        return back()->with('success', 'Đã đổi mật khẩu thành công!');
    }
}
