<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $users = User::when($search, function ($query, $search) {
            return $query->where('name', 'LIKE', "%{$search}%")->orWhere('email', 'LIKE', "%{$search}%");
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
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,teacher,student',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return back()->with('success', 'Đã tạo tài khoản ' . strtoupper($request->role) . ' thành công!');
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

        return back()->with('success', "Đã cấp lại mật khẩu cho tài khoản {$user->email}. Mật khẩu mới là: {$defaultPassword}");
    }
}
