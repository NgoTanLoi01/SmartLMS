<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\StudentLoginCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $users = User::when($search, function ($query, $search) {
            return $query->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%")
                ->orWhere('username', 'LIKE', "%{$search}%")
                ->orWhere('student_code', 'LIKE', "%{$search}%");
        })
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
        ]);

        if ($request->role !== 'student' && !$request->filled('email')) {
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
        ]);

        $message = 'Đã tạo tài khoản ' . strtoupper($request->role) . ' thành công!';
        if ($username) {
            $message .= ' Tên đăng nhập: ' . $username;
        }

        return back()->with('success', $message);
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

        $loginName = $user->username ?: $user->email;

        return back()->with('success', "Đã cấp lại mật khẩu cho tài khoản {$loginName}. Mật khẩu mới là: {$defaultPassword}");
    }
}
