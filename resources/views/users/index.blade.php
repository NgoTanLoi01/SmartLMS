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

        /* Pagination Styling */
        .pagination-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }

        .pagination-wrapper nav div:first-child {
            display: none !important;
        }

        .pagination {
            margin-bottom: 0;
            gap: 5px;
        }

        .page-item .page-link {
            border: none;
            border-radius: 50% !important;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .page-item .page-link:hover {
            background-color: #e9ecef;
            color: #0d6efd;
            transform: translateY(-2px);
        }

        .page-item.active .page-link {
            background-color: #0d6efd;
            color: white;
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
        }

        .page-item:first-child .page-link,
        .page-item:last-child .page-link {
            border-radius: 10px !important;
            width: auto;
            padding: 0 15px;
        }

        @media (max-width: 767.98px) {
            .user-toolbar-actions {
                width: 100%;
                flex-wrap: wrap;
            }

            .user-toolbar-actions form,
            .user-toolbar-actions button {
                width: 100%;
            }

            .user-filter-form {
                border-radius: 1rem !important;
                flex-wrap: wrap;
                padding: 0.5rem;
            }

            .user-toolbar-actions input[name="search"] {
                width: 100% !important;
                min-width: 0;
            }

            .table-custom {
                min-width: 760px;
            }

            .user-row td:last-child {
                white-space: nowrap;
            }

            .pagination-wrapper {
                overflow-x: auto;
                justify-content: flex-start;
                padding-bottom: 0.25rem;
            }

            .modal-footer {
                align-items: stretch;
                flex-direction: column;
            }

            .modal-footer .btn {
                width: 100%;
            }
        }
    </style>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h3 class="fw-bold mb-0 text-dark">Hệ thống Người dùng</h3>
                <p class="text-muted mb-0 small">Quản lý tài khoản Admin, Giáo viên và Học viên</p>
            </div>

            <div class="user-toolbar-actions d-flex align-items-center gap-2">
                <form action="{{ route('users.index') }}" method="GET"
                    class="user-filter-form input-group rounded-pill overflow-hidden shadow-sm"
                    style="background: white; border: 1px solid #dee2e6;">
                    <span class="input-group-text bg-white border-0 text-muted ps-3"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control border-0 shadow-none px-2"
                        placeholder="Tìm tên, tên đăng nhập, mã HS hoặc email..." value="{{ request('search') }}"
                        style="width: 220px; font-size: 0.9rem;">
                    <select name="role" class="form-select border-0 border-start shadow-none" aria-label="Lọc vai trò"
                        style="max-width: 140px; font-size: 0.9rem;">
                        <option value="">Mọi vai trò</option>
                        <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                        <option value="teacher" @selected(request('role') === 'teacher')>Giáo viên</option>
                        <option value="student" @selected(request('role') === 'student')>Học viên</option>
                    </select>
                    <select name="status" class="form-select border-0 border-start shadow-none" aria-label="Lọc trạng thái"
                        style="max-width: 140px; font-size: 0.9rem;">
                        <option value="">Mọi trạng thái</option>
                        <option value="active" @selected(request('status') === 'active')>Đang hoạt động</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Đã vô hiệu hóa</option>
                        <option value="expired" @selected(request('status') === 'expired')>Đã hết hạn</option>
                    </select>
                    <button class="btn bg-white border-0 text-primary" type="submit" title="Áp dụng bộ lọc">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    @if (request()->filled('search') || request()->filled('role') || request()->filled('status'))
                        <a href="{{ route('users.index') }}" class="btn bg-white border-0 text-muted" title="Xóa bộ lọc"><i
                                class="fa-solid fa-times"></i></a>
                    @endif
                </form>

                <button class="btn btn-primary rounded-pill px-4 shadow-sm text-nowrap" data-bs-toggle="modal"
                    data-bs-target="#addUserModal">
                    <i class="fa-solid fa-user-plus me-1"></i> Cấp tài khoản mới
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="px-4 py-3 border-0">Người dùng</th>
                                <th class="px-4 py-3 border-0">Tên đăng nhập</th>
                                <th class="px-4 py-3 border-0">Liên hệ (Email)</th>
                                <th class="px-4 py-3 border-0">Vai trò</th>
                                <th class="px-4 py-3 border-0">Vòng đời</th>
                                <th class="px-4 py-3 border-0">Hoạt động gần nhất</th>
                                <th class="px-4 py-3 border-0 text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody">
                            @forelse ($users as $user)
                                <tr class="user-row">
                                    <td class="px-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-secondary bg-opacity-10 text-secondary me-3">
                                                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                            </div>
                                            <div class="fw-bold text-dark user-name">{{ $user->name }}</div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($user->username)
                                            <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3">
                                                {{ $user->username }}
                                            </span>
                                            @if ($user->student_code)
                                                <div class="text-muted small mt-1">Mã HS: {{ $user->student_code }}</div>
                                            @endif
                                        @else
                                            <span class="text-muted small">Dùng email</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-muted user-email">{{ $user->email }}</td>
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
                                    <td class="px-4 py-3 small">
                                        @if (! $user->is_active)
                                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3">Đã vô hiệu hóa</span>
                                        @elseif ($user->isExpired())
                                            <span class="badge bg-warning bg-opacity-10 text-warning-emphasis rounded-pill px-3">Đã hết hạn</span>
                                        @else
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Đang hoạt động</span>
                                        @endif
                                        <div class="text-muted mt-1">
                                            @if ($user->expires_at)
                                                Hết hạn: {{ $user->expires_at->format('d/m/Y H:i') }}
                                            @else
                                                Không giới hạn thời gian
                                            @endif
                                        </div>
                                        @if ($user->deactivation_reason)
                                            <div class="text-danger mt-1" title="{{ $user->deactivation_reason }}">
                                                {{ Str::limit($user->deactivation_reason, 45) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-muted small">
                                        <div>{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Chưa đăng nhập' }}</div>
                                        <div class="mt-1">Tạo: {{ $user->created_at->format('d/m/Y') }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-primary shadow-sm me-2 manage-lifecycle-btn"
                                            data-bs-toggle="modal" data-bs-target="#lifecycleModal"
                                            data-action="{{ route('users.lifecycle.update', $user) }}"
                                            data-name="{{ $user->name }}"
                                            data-active="{{ $user->is_active ? '1' : '0' }}"
                                            data-expires-at="{{ $user->expires_at?->format('Y-m-d\TH:i') }}"
                                            data-reason="{{ $user->deactivation_reason }}">
                                            <i class="fa-solid fa-user-shield"></i> Vòng đời
                                        </button>

                                        @if (auth()->id() !== $user->id)
                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                                class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-link text-danger p-0 text-decoration-none shadow-none me-2"
                                                    onclick="return confirm('Bạn có chắc chắn muốn xóa tài khoản này?')">
                                                    <i class="fa-solid fa-trash-can"></i> Xóa
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted small fst-italic me-3">Bạn</span>
                                        @endif

                                        @if (auth()->user()->role === 'admin')
                                            <form action="{{ route('users.resetPassword', $user->id) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Bạn có chắc chắn cập nhật mật khẩu về mặc định?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning shadow-sm"
                                                    title="Cấp lại mật khẩu">
                                                    <i class="fa-solid fa-key"></i> Reset
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-user-slash fa-3x mb-3 opacity-25"></i>
                                        <p>Không tìm thấy người dùng nào phù hợp.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="pagination-wrapper">
            {{ $users->links('pagination::bootstrap-5') }}
        </div>
    </div>

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
                        <input type="email" name="email" class="form-control bg-light border-0 py-2">
                        <div class="form-text">Bắt buộc với giáo viên/admin. Học viên có thể để trống, hệ thống sẽ tạo email nội bộ.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Mã học sinh</label>
                        <input type="text" name="student_code" class="form-control bg-light border-0 py-2"
                            placeholder="Chỉ cần nhập với học viên nếu có">
                        <div class="form-text">Nếu học viên trùng họ tên, mã này sẽ giúp tạo tên đăng nhập không trùng.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Mật khẩu</label>
                        <input type="password" name="password" class="form-control bg-light border-0 py-2" required
                            minlength="6">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small text-muted">Vai trò (Role)</label>
                        <select name="role" class="form-select bg-light border-0 py-2" required>
                            <option value="teacher">Giáo viên (Teacher)</option>
                            <option value="student">Học viên (Student)</option>
                            <option value="admin">Quản trị viên (Admin)</option>
                        </select>
                    </div>
                    <div class="mt-3 mb-0">
                        <label for="newUserExpiresAt" class="form-label fw-bold small text-muted">Ngày hết hạn</label>
                        <input id="newUserExpiresAt" type="datetime-local" name="expires_at"
                            class="form-control bg-light border-0 py-2" value="{{ old('expires_at') }}">
                        <div class="form-text">Để trống nếu tài khoản không giới hạn thời gian.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Tạo tài khoản</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="lifecycleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="lifecycleForm" method="POST" class="modal-content border-0 shadow">
                @csrf
                @method('PATCH')
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold text-dark">Quản lý vòng đời tài khoản</h5>
                        <div id="lifecycleAccountName" class="small text-muted"></div>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                        aria-label="Đóng"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label for="lifecycleStatus" class="form-label fw-bold small text-muted">Trạng thái</label>
                        <select id="lifecycleStatus" name="is_active" class="form-select bg-light border-0 py-2" required>
                            <option value="1">Đang hoạt động</option>
                            <option value="0">Vô hiệu hóa</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="lifecycleExpiresAt" class="form-label fw-bold small text-muted">Ngày hết hạn</label>
                        <input id="lifecycleExpiresAt" type="datetime-local" name="expires_at"
                            class="form-control bg-light border-0 py-2">
                        <div class="form-text">Để trống nếu tài khoản không giới hạn thời gian.</div>
                    </div>
                    <div class="mb-0" id="deactivationReasonGroup">
                        <label for="deactivationReason" class="form-label fw-bold small text-muted">Lý do vô hiệu hóa</label>
                        <textarea id="deactivationReason" name="deactivation_reason" class="form-control bg-light border-0"
                            rows="3" maxlength="1000" placeholder="VD: Đã nghỉ học, kết thúc hợp đồng..."></textarea>
                    </div>
                    <div class="alert alert-warning small border-0 mt-3 mb-0">
                        Khi vô hiệu hóa, tất cả phiên đăng nhập và token của tài khoản sẽ bị thu hồi ngay.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('lifecycleForm');
            const accountName = document.getElementById('lifecycleAccountName');
            const status = document.getElementById('lifecycleStatus');
            const expiresAt = document.getElementById('lifecycleExpiresAt');
            const reason = document.getElementById('deactivationReason');
            const reasonGroup = document.getElementById('deactivationReasonGroup');

            const syncReasonState = () => {
                const inactive = status.value === '0';
                reasonGroup.classList.toggle('d-none', !inactive);
                reason.required = inactive;
                if (!inactive) reason.value = '';
            };

            document.querySelectorAll('.manage-lifecycle-btn').forEach((button) => {
                button.addEventListener('click', () => {
                    form.action = button.dataset.action;
                    accountName.textContent = button.dataset.name;
                    status.value = button.dataset.active;
                    expiresAt.value = button.dataset.expiresAt || '';
                    reason.value = button.dataset.reason || '';
                    syncReasonState();
                });
            });

            status.addEventListener('change', syncReasonState);
        });
    </script>
@endsection
