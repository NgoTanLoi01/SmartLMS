<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function updatePassword(Request $request)
    {
        $request->validate(
            [
                'current_password' => 'required',
                'new_password' => 'required|min:6|confirmed',
            ],
            [
                'current_password.required' => 'Vui lòng nhập đúng mật khẩu hiện tại.',
                'new_password.required' => 'Mật khẩu mới không được để trống.',
                'new_password.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
                'new_password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
            ],
        );

        $user = auth()->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Mật khẩu hiện tại không chính xác. Vui lòng kiểm tra lại!'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return back()->with('success', 'Mật khẩu đã được thay đổi thành công.');
    }
}
