<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
        public function handle($request, \Closure $next, ...$roles) {
            // Kiểm tra xem user đã đăng nhập chưa và role có nằm trong danh sách cho phép không
            if (!$request->user() || !in_array($request->user()->role, $roles)) {
                return response()->json([
                    'message' => 'Bạn không có quyền thực hiện hành động này.'
                ], 403);
            }

            return $next($request);
        }
}
