@extends('layouts.app')

@section('title', 'Quản lý người dùng hệ thống')

@section('content')
    @push('styles')
        @vite('resources/css/pages/users-index.css')
    @endpush

    <div class="lms-page user-management-page">
        <x-ui.page-header title="Tài khoản người dùng">
            <x-slot:meta>
                <span><i class="fa-solid fa-shield-halved"></i> Quản lý quyền truy cập và vòng đời tài khoản</span>
            </x-slot:meta>

            <x-slot:actions>
                <x-ui.button class="user-create-btn" icon="fa-user-plus" data-bs-toggle="modal"
                    data-bs-target="#addUserModal">
                    Cấp tài khoản mới
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <section class="user-stats" aria-label="Tổng quan tài khoản">
            <article class="user-stat">
                <span class="user-stat__icon" aria-hidden="true"><i class="fa-solid fa-users"></i></span>
                <div>
                    <div class="user-stat__value">{{ (int) $userStats->total }}</div>
                    <div class="user-stat__label">Tổng tài khoản</div>
                </div>
            </article>
            <article class="user-stat user-stat--success">
                <span class="user-stat__icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                <div>
                    <div class="user-stat__value">{{ (int) $userStats->active }}</div>
                    <div class="user-stat__label">Đang hoạt động</div>
                </div>
            </article>
            <article class="user-stat user-stat--student">
                <span class="user-stat__icon" aria-hidden="true"><i class="fa-solid fa-user-graduate"></i></span>
                <div>
                    <div class="user-stat__value">{{ (int) $userStats->students }}</div>
                    <div class="user-stat__label">Học viên</div>
                </div>
            </article>
            <article class="user-stat user-stat--attention">
                <span class="user-stat__icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <div>
                    <div class="user-stat__value">{{ (int) $userStats->attention }}</div>
                    <div class="user-stat__label">Cần xử lý</div>
                </div>
            </article>
        </section>

        <form action="{{ route('users.index') }}" method="GET" class="user-filter-panel" role="search">
            <div class="user-filter-field user-filter-field--search">
                <label class="user-filter-label" for="userSearch">Tìm tài khoản</label>
                <div class="user-filter-control">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input id="userSearch" type="search" name="search" class="form-control"
                        placeholder="Tên, tên đăng nhập, mã học viên hoặc email" value="{{ request('search') }}">
                </div>
            </div>

            <div class="user-filter-field">
                <label class="user-filter-label" for="userRoleFilter">Vai trò</label>
                <div class="user-filter-control">
                    <i class="fa-solid fa-user-tag" aria-hidden="true"></i>
                    <select id="userRoleFilter" name="role" class="form-select">
                        <option value="">Tất cả vai trò</option>
                        <option value="admin" @selected(request('role') === 'admin')>Quản trị viên</option>
                        <option value="teacher" @selected(request('role') === 'teacher')>Giáo viên</option>
                        <option value="student" @selected(request('role') === 'student')>Học viên</option>
                    </select>
                </div>
            </div>

            <div class="user-filter-field">
                <label class="user-filter-label" for="userStatusFilter">Trạng thái</label>
                <div class="user-filter-control">
                    <i class="fa-solid fa-toggle-on" aria-hidden="true"></i>
                    <select id="userStatusFilter" name="status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" @selected(request('status') === 'active')>Đang hoạt động</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Đã vô hiệu hóa</option>
                        <option value="expired" @selected(request('status') === 'expired')>Đã hết hạn</option>
                    </select>
                </div>
            </div>

            <div class="user-filter-actions">
                <x-ui.button type="submit" icon="fa-filter">Áp dụng</x-ui.button>
                @if (request()->filled('search') || request()->filled('role') || request()->filled('status'))
                    <x-ui.button :href="route('users.index')" tone="outline" icon="fa-rotate-left">Đặt lại</x-ui.button>
                @endif
            </div>

            <div class="user-filter-summary" aria-live="polite">
                @if ($users->total() > 0)
                    Hiển thị {{ $users->firstItem() }}–{{ $users->lastItem() }} trong {{ $users->total() }} tài khoản phù hợp
                @else
                    Không có tài khoản phù hợp với bộ lọc hiện tại
                @endif
            </div>
        </form>

        <section class="user-list-card" aria-label="Danh sách tài khoản">
            <table class="user-table">
                <thead>
                    <tr>
                        <th class="user-table__account">Tài khoản</th>
                        <th class="user-table__login">Thông tin đăng nhập</th>
                        <th class="user-table__role">Vai trò</th>
                        <th class="user-table__status">Trạng thái và hoạt động</th>
                        <th class="user-table__actions text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    @forelse ($users as $user)
                        @php
                            $status = ! $user->is_active ? 'inactive' : ($user->isExpired() ? 'expired' : 'active');
                        @endphp
                        <tr class="user-row">
                            <td data-label="Tài khoản">
                                <div class="user-identity">
                                    <div class="user-avatar" aria-hidden="true">
                                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                    </div>
                                    <div class="user-identity__copy">
                                        <div class="user-identity__name user-name">{{ $user->name }}</div>
                                        <div class="user-identity__email user-email" title="{{ $user->email }}">
                                            {{ $user->email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Thông tin đăng nhập">
                                <div class="user-login-copy">
                                    <div class="user-login-name" title="{{ $user->username ?: $user->email }}">
                                        {{ $user->username ?: 'Đăng nhập bằng email' }}
                                    </div>
                                    <div class="user-login-meta">
                                        {{ $user->student_code ? 'Mã HV: '.$user->student_code : 'Không có mã học viên' }}
                                    </div>
                                </div>
                            </td>
                            <td data-label="Vai trò">
                                <x-ui.role-badge :role="$user->role" />
                            </td>
                            <td data-label="Trạng thái và hoạt động">
                                <div class="user-status-copy">
                                    <div class="user-status-line">
                                        <x-ui.status-badge :status="$status" />
                                        @if ($user->expires_at)
                                            <span class="user-status-meta">đến {{ $user->expires_at->format('d/m/Y') }}</span>
                                        @endif
                                    </div>
                                    <div class="user-status-meta">
                                        <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                        {{ $user->last_login_at ? 'Đăng nhập '.$user->last_login_at->format('d/m/Y H:i') : 'Chưa đăng nhập' }}
                                    </div>
                                    <div class="user-status-meta">
                                        <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                        Tạo ngày {{ $user->created_at->format('d/m/Y') }}
                                    </div>
                                    @if ($user->deactivation_reason)
                                        <div class="user-status-reason" title="{{ $user->deactivation_reason }}">
                                            {{ Str::limit($user->deactivation_reason, 55) }}
                                        </div>
                                    @elseif (! $user->expires_at)
                                        <div class="user-status-meta">Không giới hạn thời gian</div>
                                    @endif
                                </div>
                            </td>
                            <td data-label="Thao tác">
                                <div class="user-actions">
                                    <button type="button" class="lms-btn lms-btn-outline lms-btn-sm user-manage-btn manage-lifecycle-btn"
                                        data-bs-toggle="modal" data-bs-target="#lifecycleModal"
                                        data-action="{{ route('users.lifecycle.update', $user) }}"
                                        data-name="{{ $user->name }}" data-active="{{ $user->is_active ? '1' : '0' }}"
                                        data-expires-at="{{ $user->expires_at?->format('Y-m-d\TH:i') }}"
                                        data-reason="{{ $user->deactivation_reason }}">
                                        <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
                                        <span>Quản lý</span>
                                    </button>

                                    <div class="dropdown">
                                        <button type="button" class="user-more-btn" data-bs-toggle="dropdown"
                                            data-bs-boundary="viewport" aria-expanded="false"
                                            aria-label="Thêm thao tác với {{ $user->name }}">
                                            <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end user-action-menu">
                                            <button type="button" class="dropdown-item edit-user-btn"
                                                data-bs-toggle="modal" data-bs-target="#editUserModal"
                                                data-action="{{ route('users.update', $user) }}"
                                                data-user-id="{{ $user->id }}"
                                                data-name="{{ $user->name }}"
                                                data-email="{{ $user->email }}"
                                                data-role-label="{{ $user->isAdmin() ? 'Quản trị viên' : ($user->isTeacher() ? 'Giáo viên' : 'Học viên') }}"
                                                data-is-student="{{ $user->isStudent() ? '1' : '0' }}"
                                                data-username="{{ $user->username }}"
                                                data-student-code="{{ $user->student_code }}">
                                                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Sửa thông tin
                                            </button>

                                            <div class="dropdown-divider"></div>
                                            <form action="{{ route('users.resetPassword', $user->id) }}" method="POST"
                                                onsubmit="return confirm('Cấp lại mật khẩu mặc định cho tài khoản này?');">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-warning">
                                                    <i class="fa-solid fa-key" aria-hidden="true"></i> Cấp lại mật khẩu
                                                </button>
                                            </form>

                                            @if (auth()->id() !== $user->id)
                                                <div class="dropdown-divider"></div>
                                                <form action="{{ route('users.destroy', $user->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"
                                                        onclick="return confirm('Bạn có chắc chắn muốn xóa tài khoản này?');">
                                                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Xóa tài khoản
                                                    </button>
                                                </form>
                                            @else
                                                <div class="dropdown-item-text small text-muted">
                                                    <i class="fa-solid fa-user-check me-2" aria-hidden="true"></i> Tài khoản của bạn
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="user-table__empty">
                                <x-ui.empty-state title="Không tìm thấy tài khoản"
                                    description="Hãy thay đổi từ khóa hoặc đặt lại bộ lọc để xem danh sách tài khoản."
                                    icon="fa-user-slash">
                                    @if (request()->filled('search') || request()->filled('role') || request()->filled('status'))
                                        <x-ui.button :href="route('users.index')" tone="outline" size="sm"
                                            icon="fa-rotate-left">Đặt lại bộ lọc</x-ui.button>
                                    @endif
                                </x-ui.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <x-ui.pagination :paginator="$users" item-label="tài khoản" />
        </section>
    </div>

    <div class="modal fade user-modal" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('users.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="addUserModalTitle">Cấp tài khoản mới</h5>
                        <div class="small text-muted">Tạo thông tin đăng nhập và phân quyền ban đầu</div>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted" for="newUserName">Họ và tên</label>
                        <input id="newUserName" type="text" name="name" class="form-control" value="{{ old('name') }}"
                            autocomplete="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted" for="newUserEmail">Email</label>
                        <input id="newUserEmail" type="email" name="email" class="form-control"
                            value="{{ old('email') }}" autocomplete="email">
                        <div class="form-text">Bắt buộc với giáo viên và quản trị viên. Học viên có thể dùng email nội bộ.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted" for="newStudentCode">Mã học viên</label>
                        <input id="newStudentCode" type="text" name="student_code" class="form-control"
                            value="{{ old('student_code') }}" placeholder="Chỉ nhập khi tạo tài khoản học viên">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted" for="newUserPassword">Mật khẩu</label>
                        <input id="newUserPassword" type="password" name="password" class="form-control"
                            autocomplete="new-password" minlength="6" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted" for="newUserRole">Vai trò</label>
                            <select id="newUserRole" name="role" class="form-select" required>
                                <option value="teacher" @selected(old('role') === 'teacher')>Giáo viên</option>
                                <option value="student" @selected(old('role') === 'student')>Học viên</option>
                                <option value="admin" @selected(old('role') === 'admin')>Quản trị viên</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted" for="newUserExpiresAt">Ngày hết hạn</label>
                            <input id="newUserExpiresAt" type="datetime-local" name="expires_at" class="form-control"
                                value="{{ old('expires_at') }}">
                        </div>
                    </div>
                    <div class="form-text mt-2">Để trống ngày hết hạn nếu tài khoản được sử dụng lâu dài.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lms-btn lms-btn-outline" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="lms-btn lms-btn-primary">
                        <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Tạo tài khoản
                    </button>
                </div>
            </form>
        </div>
    </div>

    @php
        $editUserHasErrors = $errors->hasBag('editUser');
        $editingIsStudent = $editingUser?->isStudent() ?? false;
        $editingRoleLabel = $editingUser?->isAdmin()
            ? 'Quản trị viên'
            : ($editingUser?->isTeacher() ? 'Giáo viên' : 'Học viên');
    @endphp
    <div class="modal fade user-modal" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalTitle"
        aria-hidden="true" data-reopen="{{ $editUserHasErrors && $editingUser ? '1' : '0' }}">
        <div class="modal-dialog modal-dialog-centered">
            <form id="editUserForm" action="{{ $editingUser ? route('users.update', $editingUser) : '' }}" method="POST"
                class="modal-content">
                @csrf
                @method('PATCH')
                <input id="editingUserId" type="hidden" name="editing_user_id"
                    value="{{ old('editing_user_id', $editingUser?->id) }}">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="editUserModalTitle">Sửa thông tin người dùng</h5>
                        <div class="small text-muted">Cập nhật hồ sơ và thông tin đăng nhập</div>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    @if ($editUserHasErrors)
                        <div class="alert alert-danger small" role="alert">
                            Vui lòng kiểm tra lại các thông tin được đánh dấu bên dưới.
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted" for="editUserName">Họ và tên</label>
                        <input id="editUserName" type="text" name="name"
                            class="form-control @error('name', 'editUser') is-invalid @enderror"
                            value="{{ old('name', $editingUser?->name) }}" autocomplete="name" required>
                        @error('name', 'editUser')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted" for="editUserEmail">Email</label>
                        <input id="editUserEmail" type="email" name="email"
                            class="form-control @error('email', 'editUser') is-invalid @enderror"
                            value="{{ old('email', $editingUser?->email) }}" autocomplete="email">
                        <div class="form-text">
                            Email bắt buộc với giáo viên và quản trị viên; học viên có thể để trống để dùng email nội bộ.
                        </div>
                        @error('email', 'editUser')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Vai trò</label>
                        <div id="editUserRole" class="form-control bg-light" aria-readonly="true">
                            {{ $editingUser ? $editingRoleLabel : '' }}
                        </div>
                        <div class="form-text">Vai trò và trạng thái tài khoản được quản lý bằng luồng riêng.</div>
                    </div>

                    <div id="editStudentFields" class="{{ $editingIsStudent ? '' : 'd-none' }}">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted" for="editUsername">Tên đăng nhập</label>
                            <input id="editUsername" type="text" name="username"
                                class="form-control @error('username', 'editUser') is-invalid @enderror"
                                value="{{ old('username', $editingUser?->username) }}"
                                {{ $editingIsStudent ? 'required' : 'disabled' }} autocomplete="username">
                            @error('username', 'editUser')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-bold small text-muted" for="editStudentCode">Mã học viên</label>
                            <input id="editStudentCode" type="text" name="student_code"
                                class="form-control @error('student_code', 'editUser') is-invalid @enderror"
                                value="{{ old('student_code', $editingUser?->student_code) }}"
                                {{ $editingIsStudent ? '' : 'disabled' }}>
                            @error('student_code', 'editUser')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lms-btn lms-btn-outline" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="lms-btn lms-btn-primary">
                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade user-modal" id="lifecycleModal" tabindex="-1" aria-labelledby="lifecycleModalTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="lifecycleForm" method="POST" class="modal-content">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="lifecycleModalTitle">Quản lý vòng đời tài khoản</h5>
                        <div id="lifecycleAccountName" class="small text-muted"></div>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="lifecycleStatus" class="form-label fw-bold small text-muted">Trạng thái</label>
                        <select id="lifecycleStatus" name="is_active" class="form-select" required>
                            <option value="1">Đang hoạt động</option>
                            <option value="0">Vô hiệu hóa</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="lifecycleExpiresAt" class="form-label fw-bold small text-muted">Ngày hết hạn</label>
                        <input id="lifecycleExpiresAt" type="datetime-local" name="expires_at" class="form-control">
                        <div class="form-text">Để trống nếu tài khoản không giới hạn thời gian.</div>
                    </div>
                    <div class="mb-0" id="deactivationReasonGroup">
                        <label for="deactivationReason" class="form-label fw-bold small text-muted">Lý do vô hiệu hóa</label>
                        <textarea id="deactivationReason" name="deactivation_reason" class="form-control" rows="3" maxlength="1000"
                            placeholder="Ví dụ: đã nghỉ học, kết thúc hợp đồng..."></textarea>
                    </div>
                    <div class="alert alert-warning small border-0 mt-3 mb-0">
                        <i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true"></i>
                        Khi vô hiệu hóa, tất cả phiên đăng nhập của tài khoản sẽ bị thu hồi ngay.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lms-btn lms-btn-outline" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="lms-btn lms-btn-primary">Lưu thay đổi</button>
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
                if (!status || !reasonGroup || !reason) return;

                const inactive = status.value === '0';
                reasonGroup.classList.toggle('d-none', !inactive);
                reason.required = inactive;
                if (!inactive) reason.value = '';
            };

            document.querySelectorAll('.manage-lifecycle-btn').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!form || !accountName || !status || !expiresAt || !reason) return;

                    form.action = button.dataset.action;
                    accountName.textContent = button.dataset.name;
                    status.value = button.dataset.active;
                    expiresAt.value = button.dataset.expiresAt || '';
                    reason.value = button.dataset.reason || '';
                    syncReasonState();
                });
            });

            status?.addEventListener('change', syncReasonState);

            const editModal = document.getElementById('editUserModal');
            const editForm = document.getElementById('editUserForm');
            const editingUserId = document.getElementById('editingUserId');
            const editName = document.getElementById('editUserName');
            const editEmail = document.getElementById('editUserEmail');
            const editRole = document.getElementById('editUserRole');
            const studentFields = document.getElementById('editStudentFields');
            const editUsername = document.getElementById('editUsername');
            const editStudentCode = document.getElementById('editStudentCode');

            const fillEditForm = (button) => {
                if (!editForm || !editingUserId || !editName || !editEmail || !editRole ||
                    !studentFields || !editUsername || !editStudentCode) return;

                const isStudent = button.dataset.isStudent === '1';
                editForm.action = button.dataset.action;
                editingUserId.value = button.dataset.userId;
                editName.value = button.dataset.name || '';
                editEmail.value = button.dataset.email || '';
                editRole.textContent = button.dataset.roleLabel || '';
                editUsername.value = button.dataset.username || '';
                editStudentCode.value = button.dataset.studentCode || '';
                studentFields.classList.toggle('d-none', !isStudent);
                editUsername.disabled = !isStudent;
                editUsername.required = isStudent;
                editStudentCode.disabled = !isStudent;
            };

            document.querySelectorAll('.edit-user-btn').forEach((button) => {
                button.addEventListener('click', () => fillEditForm(button));
            });

            if (editModal?.dataset.reopen === '1' && window.bootstrap?.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(editModal).show();
            }
        });
    </script>
@endsection
