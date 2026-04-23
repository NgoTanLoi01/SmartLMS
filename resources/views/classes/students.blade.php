@extends('layouts.app')

@section('title', 'Quản lý học sinh - ' . $classroom->name)

@section('content')
    <style>
        /* Tuỳ chỉnh avatar chữ cái đầu */
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

        /* Hiệu ứng hover cho bảng */
        .table-custom tbody tr {
            transition: background-color 0.2s ease;
        }

        .table-custom tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Ẩn hiện nút thao tác */
        .action-btn {
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .table-custom tbody tr:hover .action-btn {
            opacity: 1;
        }
    </style>

    <div class="container-fluid py-4">
        <!-- Hiển thị thông báo thành công/lỗi -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <ul class="mb-0 px-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 px-2">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Quản lý lớp học</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $classroom->code }}</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-0 text-dark">{{ $classroom->name }}</h3>
                <p class="text-muted mb-0 mt-1 small">
                    <i class="fas fa-chalkboard-teacher me-1"></i> Giáo viên: <span
                        class="fw-medium">{{ $classroom->teacher->name }}</span> |
                    <i class="fas fa-users ms-2 me-1"></i> Sĩ số: <span
                        class="fw-medium">{{ $classroom->students->count() }}</span>
                </p>
            </div>

            @if (auth()->user()->role === 'admin' || auth()->id() === $classroom->teacher_id)
                <div class="d-flex gap-2">
                    <button class="btn btn-success rounded-pill px-4 shadow-sm" data-bs-toggle="modal"
                        data-bs-target="#importExcelModal">
                        <i class="fas fa-file-excel me-1"></i> Nhập từ Excel
                    </button>
                    <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal"
                        data-bs-target="#addStudentModal">
                        <i class="fas fa-user-plus me-1"></i> Thêm thủ công
                    </button>
                </div>
            @endif
        </div>

        <!-- Danh sách học sinh -->
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-list-ul me-2 text-primary"></i>Danh sách học viên</h6>
                <div class="input-group input-group-sm w-auto">
                    <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control bg-light border-0 shadow-none"
                        placeholder="Tìm kiếm học sinh...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="px-4 py-3 border-0">Học sinh</th>
                                <th class="px-4 py-3 border-0">Email</th>
                                <th class="px-4 py-3 border-0">Ngày tham gia</th>
                                <th class="px-4 py-3 border-0 text-end">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($classroom->students as $student)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-primary bg-opacity-10 text-primary me-3">
                                                {{ mb_strtoupper(mb_substr($student->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark">{{ $student->name }}</div>
                                                <div class="text-muted small">ID: #{{ $student->id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-muted">{{ $student->email }}</td>
                                    <td class="px-4 py-3 text-muted small">
                                        {{ $student->pivot->created_at ? $student->pivot->created_at->format('d/m/Y H:i') : 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        @if (auth()->user()->role === 'admin' || auth()->id() === $classroom->teacher_id)
                                            <form
                                                action="{{ route('classes.students.destroy', ['classId' => $classroom->id, 'studentId' => $student->id]) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-link text-danger p-0 action-btn text-decoration-none shadow-none"
                                                    onclick="return confirm('Bạn có chắc chắn muốn xóa học sinh này khỏi lớp?')">
                                                    <i class="fas fa-user-minus"></i> Xóa khỏi lớp
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <div class="opacity-50">
                                            <i class="fas fa-user-graduate fa-3x mb-3 text-muted"></i>
                                            <h6 class="text-muted fw-bold">Lớp học chưa có học sinh</h6>
                                            <p class="text-muted small mb-0">Hãy thêm học sinh mới để bắt đầu khóa học.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Thêm Học Sinh Thủ Công -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('classes.students.store', $classroom->id) }}" method="POST"
                class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark">Thêm học sinh mới</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="alert alert-info border-0 bg-info bg-opacity-10 small mb-4">
                        <i class="fas fa-info-circle me-1"></i> Học sinh sẽ được tạo tài khoản và tự động gán vào lớp
                        <strong>{{ $classroom->name }}</strong>.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Họ và tên học sinh</label>
                        <input type="text" name="name" class="form-control bg-light border-0 py-2"
                            placeholder="VD: Nguyễn Văn A" required value="{{ old('name') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Địa chỉ Email</label>
                        <input type="email" name="email" class="form-control bg-light border-0 py-2"
                            placeholder="VD: nguyenb@example.com" required value="{{ old('email') }}">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small text-muted">Mật khẩu khởi tạo</label>
                        <input type="password" name="password" class="form-control bg-light border-0 py-2"
                            placeholder="Nhập ít nhất 6 ký tự" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy
                        bỏ</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Thêm vào lớp</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Nhập từ Excel -->
    <div class="modal fade" id="importExcelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('classes.students.import', $classroom->id) }}" method="POST"
                enctype="multipart/form-data" class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark">Nhập danh sách từ Excel</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="alert alert-warning border-0 bg-warning bg-opacity-10 small mb-4">
                        <i class="fas fa-exclamation-triangle me-1"></i> Hệ thống sẽ đọc dữ liệu từ <strong>dòng số
                            5</strong>. Vui lòng đảm bảo cột <strong>D</strong> là Mã HS, cột <strong>E</strong> là Họ, cột
                        <strong>F</strong> là Tên theo đúng biểu mẫu.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Chọn file Excel (.xlsx, .xls, .csv)</label>
                        <input type="file" name="file" class="form-control bg-light border-0 py-2"
                            accept=".xlsx, .xls, .csv" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm">Bắt đầu nhập dữ
                        liệu</button>
                </div>
            </form>
        </div>
    </div>
@endsection
