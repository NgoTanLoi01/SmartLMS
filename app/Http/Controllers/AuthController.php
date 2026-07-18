<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required',
        ]);

        $login = trim($request->input('login'));
        $throttleKey = Str::lower($login).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'login' => "Bạn đã đăng nhập sai quá nhiều lần. Vui lòng thử lại sau {$seconds} giây.",
            ])->onlyInput('login');
        }

        $user = User::where('username', $login)
            ->orWhere('username', Str::lower($login))
            ->orWhere('username', Str::upper($login))
            ->orWhere('email', $login)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors([
                'login' => 'Thông tin đăng nhập hoặc mật khẩu không chính xác.',
            ])->onlyInput('login');
        }

        if (! $user->canAccessSystem()) {
            return back()->withErrors([
                'login' => 'Tài khoản đã bị vô hiệu hóa hoặc hết hạn. Vui lòng liên hệ quản trị viên.',
            ])->onlyInput('login');
        }

        RateLimiter::clear($throttleKey);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
