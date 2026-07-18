<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use App\Support\StudentLoginCode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $role = $request->input('role');
        $status = $request->input('status');

        $users = User::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('username', 'LIKE', "%{$search}%")
                        ->orWhere('student_code', 'LIKE', "%{$search}%");
                });
            })
            ->when(in_array($role, [User::ROLE_ADMIN, User::ROLE_TEACHER, User::ROLE_STUDENT], true),
                fn ($query) => $query->where('role', $role))
            ->when($status === 'active', fn ($query) => $query
                ->where('is_active', true)
                ->where(fn ($nested) => $nested->whereNull('expires_at')->orWhere('expires_at', '>', now())))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($status === 'expired', fn ($query) => $query
                ->where('is_active', true)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now()))
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return back()->with('error', 'Chỉ Quản trị viên mới được tạo tài khoản.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'student_code' => 'nullable|string|max:50',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,teacher,student',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($request->role !== 'student' && ! $request->filled('email')) {
            return back()->withErrors(['email' => 'Email là bắt buộc với tài khoản quản trị viên và giáo viên.'])->withInput();
        }

        $studentCode = $request->role === 'student'
            ? StudentLoginCode::normalizeStudentCode($request->student_code)
            : null;
        if ($studentCode && User::where('student_code', $studentCode)->exists()) {
            return back()->withErrors(['student_code' => 'Mã học sinh này đã tồn tại.'])->withInput();
        }

        $username = $request->role === 'student'
            ? StudentLoginCode::generateFromName($request->name, $studentCode)
            : null;

        User::create([
            'name' => $request->name,
            'username' => $username,
            'student_code' => $studentCode,
            'email' => $request->filled('email') ? $request->email : StudentLoginCode::emailFromUsername($username),
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
            'expires_at' => $request->filled('expires_at') ? Carbon::parse($request->expires_at) : null,
        ]);

        $message = 'Đã tạo tài khoản '.strtoupper($request->role).' thành công!';
        if ($username) {
            $message .= ' Tên đăng nhập: '.$username;
        }

        return back()->with('success', $message);
    }

    public function updateLifecycle(Request $request, User $user)
    {
        $data = $request->validate([
            'is_active' => 'required|boolean',
            'expires_at' => 'nullable|date|after:now',
            'deactivation_reason' => 'nullable|required_if:is_active,0|string|max:1000',
        ]);

        $isActive = $request->boolean('is_active');
        if ($user->is(auth()->user()) && ! $isActive) {
            return back()->with('error', 'Bạn không thể tự vô hiệu hóa tài khoản đang sử dụng.');
        }

        $hasExpiration = filled($data['expires_at'] ?? null);
        if ($user->isAdmin() && (! $isActive || $hasExpiration)) {
            $hasAnotherActiveAdmin = User::query()
                ->whereKeyNot($user->getKey())
                ->where('role', User::ROLE_ADMIN)
                ->where('is_active', true)
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->exists();

            if (! $hasAnotherActiveAdmin) {
                return back()->with('error', 'Không thể khóa hoặc đặt hạn cho quản trị viên hoạt động cuối cùng.');
            }
        }

        $oldValues = AuditLogger::snapshot($user, [
            'is_active', 'expires_at', 'deactivated_at', 'deactivation_reason',
        ]);

        $user->forceFill([
            'is_active' => $isActive,
            'expires_at' => $hasExpiration ? Carbon::parse($data['expires_at']) : null,
            'deactivated_at' => $isActive ? null : now(),
            'deactivation_reason' => $isActive ? null : trim($data['deactivation_reason']),
        ])->save();

        if (! $user->canAccessSystem()) {
            $this->revokeSessionsAndTokens($user);
        }

        AuditLogger::log(
            AuditLogger::ACCOUNT_LIFECYCLE_UPDATED,
            $user,
            $oldValues,
            AuditLogger::snapshot($user, [
                'is_active', 'expires_at', 'deactivated_at', 'deactivation_reason',
            ]),
            description: "Cập nhật vòng đời tài khoản {$user->name}"
        );

        return back()->with('success', $isActive
            ? 'Đã cập nhật trạng thái và thời hạn tài khoản.'
            : 'Đã vô hiệu hóa tài khoản và thu hồi các phiên đăng nhập.');
    }

    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $user = User::findOrFail($id);

        // Không cho phép admin tự xóa chính mình
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Bạn không thể tự xóa tài khoản của mình!');
        }

        $user->delete();

        return back()->with('success', 'Đã xóa người dùng thành công.');
    }

    public function resetPassword($id)
    {
        if (auth()->user()->role !== 'admin') {
            return back()->with('error', 'Chỉ Quản trị viên mới có quyền cấp lại mật khẩu!');
        }

        $user = User::findOrFail($id);

        $defaultPassword = '123456';

        $user->update([
            'password' => Hash::make($defaultPassword),
        ]);
        $this->revokeSessionsAndTokens($user);

        $loginName = $user->username ?: $user->email;

        return back()->with('success', "Đã cấp lại mật khẩu cho tài khoản {$loginName}. Mật khẩu mới là: {$defaultPassword}");
    }

    private function revokeSessionsAndTokens(User $user): void
    {
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        if (Schema::hasTable('personal_access_tokens')) {
            $user->tokens()->delete();
        }
    }
}
