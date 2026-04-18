@extends('layouts.app')

@section('title', 'Quản lý người dùng hệ thống')

@section('content')
    <style>
        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
        }

        .table-custom tbody tr {
            transition: background-color 0.2s ease;
        }

        .table-custom tbody tr:hover {
            background-color: #f8f9fa;
        }
    </style>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-0 text-dark">Hệ thống Người dùng</h3>
                <p class="text-muted mb-0 small">Quản lý tài khoản Admin, Giảng viên và Học viên</p>
            </div>

            <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-user-plus me-1"></i> Cấp tài khoản mới
            </button>
        </div>

        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="px-4 py-3 border-0">Người dùng</th>
                                <th class="px-4 py-3 border-0">Liên hệ (Email)</th>
                                <th class="px-4 py-3 border-0">Vai trò</th>
                                <th class="px-4 py-3 border-0">Ngày tạo</th>
                                <th class="px-4 py-3 border-0 text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-secondary bg-opacity-10 text-secondary me-3">
                                                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                            </div>
                                            <div class="fw-bold text-dark">{{ $user->name }}</div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-muted">{{ $user->email }}</td>
                                    <td class="px-4 py-3">
                                        @if ($user->role === 'admin')
                                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3">Quản
                                                trị viên</span>
                                        @elseif($user->role === 'teacher')
                                            <span
                                                class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">Giảng
                                                viên</span>
                                        @else
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Học
                                                viên</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-muted small">{{ $user->created_at->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 text-end">
                                        @if (auth()->id() !== $user->id)
                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                                class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-link text-danger p-0 text-decoration-none shadow-none"
                                                    onclick="return confirm('Bạn có chắc chắn muốn xóa tài khoản này?')">
                                                    <i class="fas fa-trash-alt"></i> Xóa
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted small fst-italic">Bạn</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Thêm User -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('users.store') }}" method="POST" class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark">Cấp tài khoản mới</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Họ và Tên</label>
                        <input type="text" name="name" class="form-control bg-light border-0 py-2" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Email</label>
                        <input type="email" name="email" class="form-control bg-light border-0 py-2" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Mật khẩu</label>
                        <input type="password" name="password" class="form-control bg-light border-0 py-2" required
                            minlength="6">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small text-muted">Vai trò (Role)</label>
                        <select name="role" class="form-select bg-light border-0 py-2" required>
                            <option value="teacher">Giảng viên (Teacher)</option>
                            <option value="student">Học viên (Student)</option>
                            <option value="admin">Quản trị viên (Admin)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Tạo tài khoản</button>
                </div>
            </form>
        </div>
    </div>
@endsection
