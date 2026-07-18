<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * End an authenticated session as soon as the account is disabled or expired.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->canAccessSystem()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tài khoản đã bị vô hiệu hóa hoặc hết hạn.',
                ], 403);
            }

            return redirect()->route('login')->withErrors([
                'login' => 'Tài khoản đã bị vô hiệu hóa hoặc hết hạn. Vui lòng liên hệ quản trị viên.',
            ]);
        }

        return $next($request);
    }
}
