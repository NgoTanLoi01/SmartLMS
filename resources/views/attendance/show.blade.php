@extends('layouts.app')

@section('content')
    <style>
        /* ===== RESET & BASE ===== */
        .table input:focus {
            background-color: #fff9db !important;
            outline: none;
            box-shadow: inset 0 0 0 2px #ffec99;
        }

        /* ===== FIX BOOTSTRAP border-collapse ===== */
        .table {
            border-collapse: separate !important;
            border-spacing: 0 !important;
            width: max-content;
            min-width: 100%;
            table-layout: auto;
        }

        .table th,
        .table td {
            border-right: 1px solid #dee2e6 !important;
            border-bottom: 1px solid #dee2e6 !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .table thead th {
            border-top: 1px solid #dee2e6 !important;
        }

        .table tr td:first-child,
        .table tr th:first-child {
            border-left: 1px solid #dee2e6 !important;
        }

        /* ===== STICKY HEADER ROW ===== */
        .table thead tr th {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 3;
        }

        /* ===== STICKY COL STT ===== */
        .col-stt {
            position: sticky !important;
            left: 0 !important;
            width: 48px !important;
            min-width: 48px !important;
            max-width: 48px !important;
            z-index: 4 !important;
            background: white !important;
            box-shadow: 2px 0 0 #dee2e6;
            text-align: center;
        }

        thead .col-stt {
            z-index: 8 !important;
            background: #f8f9fa !important;
        }

        /* ===== STICKY COL NAME ===== */
        .col-name {
            position: sticky !important;
            left: 48px !important;
            width: 200px !important;
            min-width: 200px !important;
            max-width: 200px !important;
            z-index: 4 !important;
            background: white !important;
            box-shadow: 3px 0 8px rgba(0, 0, 0, 0.10);
            border-right: 2px solid #adb5bd !important;
            white-space: nowrap !important;
            overflow: visible !important;
            /* Cho hiện đủ họ tên */
            text-overflow: clip !important;
        }

        thead .col-name {
            z-index: 8 !important;
            background: #f8f9fa !important;
        }

        /* ===== CỘT ĐIỂM DANH ===== */
        .col-attendance {
            width: 54px !important;
            min-width: 54px !important;
            max-width: 54px !important;
            background-color: #f0f4ff;
            text-align: center;
        }

        /* ===== CỘT ĐIỂM SỐ ===== */
        .col-grade {
            width: 58px !important;
            min-width: 58px !important;
            max-width: 58px !important;
            background-color: #fff4e6;
            text-align: center;
        }

        /* ===== CỘT GHI CHÚ ===== */
        .col-note {
            width: 200px !important;
            min-width: 200px !important;
            max-width: 200px !important;
        }

        /* ===== HOVER ===== */
        .student-row:hover td {
            background-color: #f1f3f5 !important;
        }

        .student-row:hover .col-stt,
        .student-row:hover .col-name {
            background-color: #f1f3f5 !important;
        }

        /* ===== COLUMN HEADER ===== */
        .column-header {
            position: relative;
        }

        .btn-delete-col {
            position: absolute;
            top: 2px;
            right: 2px;
            display: none;
            color: #dc3545;
            cursor: pointer;
            font-size: 10px;
        }

        .column-header:hover .btn-delete-col {
            display: block;
        }

        .editable-name {
            cursor: text;
            border-bottom: 1px dashed #ccc;
            padding: 2px;
            display: block;
        }

        .editable-name:focus {
            background: #fff;
            outline: 2px solid #0d6efd;
            border-radius: 3px;
        }

        /* ===== INPUT TRONG TABLE ===== */
        table input[type="text"] {
            width: 100%;
            border: none;
            background: transparent;
            padding: 4px 2px;
            font-size: 12px;
        }

        .col-attendance input,
        .col-grade input {
            text-align: center;
        }

        /* ===== CARD HEADER: không wrap, không bị cắt ===== */
        .attendance-card-header {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 16px;
            background: white;
            border-bottom: 1px solid #dee2e6;
            min-width: 0;
        }

        .attendance-card-header .header-title {
            flex-shrink: 0;
            min-width: 0;
        }

        .attendance-card-header .header-title h5 {
            margin: 0;
            white-space: nowrap;
        }

        .attendance-card-header .header-title small {
            white-space: nowrap;
            display: block;
        }

        .attendance-card-header .header-actions {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .attendance-card-header .header-actions form {
            display: flex;
            flex-wrap: nowrap;
            gap: 4px;
            align-items: center;
            border-left: 1px solid #dee2e6;
            padding-left: 8px;
        }
    </style>

    <div class="container-fluid py-3 px-3">
        <div class="card border-0 shadow-sm">

            {{-- Thay thế phần Card Header cũ bằng đoạn này --}}
            <div class="card-header bg-white py-3">
                <div class="row g-3 align-items-center">
                    {{-- Tiêu đề --}}
                    <div class="col-12 col-lg-4">
                        <h5 class="fw-bold text-primary mb-0">
                            <i class="fas fa-clipboard-check me-2"></i>Điểm danh & Điểm số
                        </h5>
                        <small class="text-muted text-truncate d-block">{{ $course->title }}</small>
                    </div>

                    {{-- Các hành động --}}
                    <div class="col-12 col-lg-8">
                        <div class="d-flex flex-wrap gap-2 justify-content-lg-end align-items-center">
                            {{-- Nút xuất Excel --}}
                            <a href="{{ route('attendance.export', $course->id) }}" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-file-excel me-1"></i> Xuất Excel
                            </a>

                            {{-- Tìm kiếm --}}
                            <div class="input-group input-group-sm" style="width: 190px;">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="filterName" class="form-control border-start-0"
                                    placeholder="Tìm tên học sinh...">
                            </div>

                            {{-- Form thêm cột --}}
                            <form action="{{ route('attendance.addColumn', $course->id) }}" method="POST"
                                class="d-flex gap-1">
                                @csrf
                                <input type="text" name="name" class="form-control form-control-sm"
                                    placeholder="Tên cột" required style="width: 115px;">
                                <select name="type" class="form-select form-select-sm" style="width: 120px;">
                                    <option value="attendance">Điểm danh</option>
                                    <option value="grade">Điểm số</option>
                                    <option value="note">Ghi chú</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary text-nowrap">+ Thêm</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>


            <div class="card-body p-0">
                <form action="{{ route('attendance.save', $course->id) }}" method="POST">
                    @csrf
                    <div class="table-responsive" style="max-height: 75vh; overflow: auto;">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="text-center small">
                                <tr>
                                    <th class="col-stt">STT</th>
                                    <th class="col-name">Họ và Tên</th>
                                    @foreach ($columns as $col)
                                        <th
                                            class="column-header {{ $col->type == 'attendance' ? 'col-attendance' : ($col->type == 'grade' ? 'col-grade' : 'col-note') }}">
                                            <span class="editable-name" contenteditable="true"
                                                data-col-id="{{ $col->id }}"
                                                onblur="updateColumnName(this)">{{ $col->name }}</span>
                                            <i class="fas fa-times-circle btn-delete-col"
                                                onclick="deleteColumn({{ $col->id }}, '{{ $col->name }}')"></i>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody id="studentList">
                                @foreach ($students as $index => $student)
                                    <tr class="student-row">
                                        <td class="text-center small col-stt">{{ $index + 1 }}</td>
                                        <td class="fw-semibold col-name small">{{ $student->name }}</td>
                                        @foreach ($columns as $col)
                                            <td class="p-0">
                                                <input type="text"
                                                    name="data[{{ $col->id }}][{{ $student->id }}]"
                                                    class="form-control form-control-sm border-0 bg-transparent {{ $col->type != 'note' ? 'text-center' : '' }}"
                                                    value="{{ $attendanceData[$student->id][$col->id] ?? '' }}"
                                                    placeholder="{{ $col->type == 'attendance' ? '-' : ($col->type == 'grade' ? '0' : '...') }}">
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3 bg-light d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Click vào tên cột để đổi ngày. Hover vào cột để xóa.
                        </small>
                        <button type="submit" class="btn btn-success px-5 rounded-pill fw-bold shadow-sm">
                            LƯU BẢNG ĐIỂM
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form id="delete-column-form" method="POST" style="display:none;">
        @csrf
        @method('DELETE')
    </form>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        function updateColumnName(el) {
            const colId = el.getAttribute('data-col-id');
            const newName = el.innerText.trim();
            if (!newName) return;
            axios.post(`/attendance/column/${colId}/update`, {
                    name: newName
                })
                .then(() => {
                    el.style.color = '#198754';
                    setTimeout(() => el.style.color = '', 1000);
                })
                .catch(() => {
                    alert('Không thể cập nhật tên cột');
                    location.reload();
                });
        }

        function deleteColumn(id, name) {
            if (confirm(`Bạn có chắc muốn xóa cột "${name}" và toàn bộ dữ liệu bên dưới không?`)) {
                const form = document.getElementById('delete-column-form');
                form.action = `/attendance/column/${id}`;
                form.submit();
            }
        }

        document.getElementById('filterName').addEventListener('keyup', function() {
            let val = this.value.toLowerCase();
            document.querySelectorAll('.student-row').forEach(row => {
                let name = row.querySelector('.col-name').innerText.toLowerCase();
                row.style.display = name.includes(val) ? '' : 'none';
            });
        });

        document.querySelectorAll('input[type="text"]').forEach(input => {
            input.addEventListener('focus', function() {
                this.select();
            });
        });
    </script>
@endsection
