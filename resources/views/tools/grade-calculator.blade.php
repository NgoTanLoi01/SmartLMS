@extends('layouts.app')

@section('title', 'Tính điểm Trung cấp nghề')

@section('content')
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, var(--sl-primary) 0%, var(--sl-ai) 100%);
            --bg-light: var(--sl-bg);
        }

        body {
            background-color: var(--bg-light);
        }

        .card-main {
            border-radius: 16px;
            border: none;
            overflow: hidden;
        }

        .card-main .card-header {
            background: var(--primary-gradient);
            color: white;
        }

        /* Bảng nhập liệu */
        .grade-table thead th {
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e9ecef;
            padding: 12px 8px;
        }

        .grade-table tbody tr {
            transition: background 0.2s;
        }

        .grade-table tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.03);
        }

        .grade-table .form-control {
            border: 1px solid transparent;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
            transition: all 0.2s;
        }

        .grade-table .form-control:focus {
            background: #fff;
            border-color: var(--sl-primary);
            box-shadow: var(--sl-focus-ring);
        }

        .grade-table .form-control.input-subject {
            text-align: left;
        }

        /* Nút xóa mềm mại hơn */
        .btn-remove-row {
            opacity: 0.3;
            transition: all 0.2s;
            border: none;
            background: none;
        }

        .btn-remove-row:hover {
            opacity: 1;
            color: #dc3545;
            transform: scale(1.2);
        }

        /* Panel Kết quả */
        .result-panel {
            border-radius: 16px;
            position: sticky;
            top: 20px;
        }

        .result-number {
            font-family: 'Be Vietnam Pro', sans-serif;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .result-highlight {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Nút bấm */
        .btn-calculate {
            background: var(--primary-gradient);
            border: none;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .btn-calculate:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-calculate:active {
            transform: translateY(0);
        }

        /* Bảng quy đổi */
        .ref-table td,
        .ref-table th {
            padding: 8px 10px;
            font-size: 0.85rem;
        }

        .badge-grade {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        /* Hiệu ứng fade in cho kết quả */
        @keyframes flashResult {
            0% {
                opacity: 0.5;
                transform: scale(0.95);
            }

            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .flash-animation {
            animation: flashResult 0.3s ease-out;
        }
    </style>

    <div class="container-fluid py-4">
        <div class="card card-main shadow-sm">
            <div class="card-header py-4 px-4 border-0">
                <h4 class="fw-bold mb-1 d-flex align-items-center">
                    <i class="fa-solid fa-graduation-cap me-3"></i> Công cụ tính điểm Trung cấp nghề
                </h4>
                <p class="mb-0 opacity-75 small">Hỗ trợ tính điểm nhanh theo quy chế đào tạo nghề</p>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <!-- Cột Bảng Điểm -->
                    <div class="col-xl-8">
                        <div class="table-responsive">
                            <table class="table grade-table align-middle border-bottom mb-3" id="subjectTable">
                                <thead>
                                    <tr class="text-muted text-uppercase">
                                        <th style="width: 40%;">Tên môn học / Mô đun</th>
                                        <th style="width: 10%;" class="text-center">Tín chỉ</th>
                                        <th style="width: 15%;" class="text-center">Điểm HS1</th>
                                        <th style="width: 15%;" class="text-center">Điểm HS2</th>
                                        <th style="width: 15%;" class="text-center">Điểm Thi</th>
                                        <th style="width: 5%;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="subject-row">
                                        <td><input type="text" class="form-control input-subject"
                                                placeholder="Nhập tên môn học..."></td>
                                        <td><input type="number" class="form-control credit" value="2" min="1">
                                        </td>
                                        <td><input type="text" class="form-control hs1" placeholder="9, 8"></td>
                                        <td><input type="text" class="form-control hs2" placeholder="7, 8"></td>
                                        <td><input type="number" class="form-control thi" step="0.1" min="0"
                                                max="10" placeholder="8.5"></td>
                                        <td><button class="btn-remove-row text-danger" onclick="removeRow(this)"><i
                                                    class="fa-solid fa-circle-xmark"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button class="btn btn-outline-primary btn-sm rounded-pill px-4 py-2 shadow-sm" onclick="addRow()">
                            <i class="fa-solid fa-plus me-2"></i> Thêm môn học
                        </button>
                    </div>

                    <!-- Cột Kết Quả -->
                    <div class="col-xl-4">
                        <div class="result-panel bg-white p-4 h-100 border shadow-sm">
                            <h6 class="fw-bold mb-4 d-flex align-items-center text-dark">
                                <i class="fa-solid fa-chart-pie me-2 text-primary"></i>Kết quả học kỳ
                            </h6>

                            <div class="d-flex justify-content-between mb-2 align-items-center">
                                <span class="text-muted small">Tổng số tín chỉ:</span>
                                <span class="fw-bold text-dark" id="totalCredits">0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 align-items-center">
                                <span class="fw-bold text-muted">ĐTB Hệ 10:</span>
                                <span class="result-number h4 mb-0 text-dark" id="avg10">0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-4 align-items-center">
                                <span class="fw-bold text-muted">ĐTB Hệ 4:</span>
                                <span class="result-number h3 mb-0 result-highlight" id="avg4">0.00</span>
                            </div>

                            <div class="text-center mb-4">
                                <button class="btn btn-primary btn-calculate w-100 mb-2 py-3 fw-bold shadow-sm"
                                    onclick="calculateGrades()">
                                    <i class="fa-solid fa-bolt me-2"></i> TÍNH ĐIỂM NGAY
                                </button>
                                <button class="btn btn-outline-secondary w-100 py-2 rounded-pill small border-0"
                                    onclick="showClearConfirm()">
                                    <i class="fa-solid fa-eraser me-2"></i> XÓA TRẮNG
                                </button>
                            </div>

                            <div class="mt-4 pt-3 border-top">
                                <p class="fw-bold small mb-3 text-muted text-uppercase"
                                    style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                    <i class="fa-solid fa-circle-info me-1"></i> Bảng quy đổi & Xếp loại
                                </p>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover ref-table border-0 mb-0">
                                        <thead>
                                            <tr class="table-light">
                                                <th class="border-0 rounded-start">Hệ 10</th>
                                                <th class="border-0 text-center">Hệ 4</th>
                                                <th class="border-0 text-end rounded-end">Xếp loại</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>9.0 - 10</td>
                                                <td class="text-center fw-bold">4.0</td>
                                                <td class="text-end"><span
                                                        class="badge-grade bg-success-subtle text-success">Xuất sắc</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>8.0 - 8.9</td>
                                                <td class="text-center fw-bold">3.5</td>
                                                <td class="text-end"><span
                                                        class="badge-grade bg-primary-subtle text-primary">Giỏi</span></td>
                                            </tr>
                                            <tr>
                                                <td>7.0 - 7.9</td>
                                                <td class="text-center fw-bold">3.0</td>
                                                <td class="text-end"><span
                                                        class="badge-grade bg-info-subtle text-info">Khá</span></td>
                                            </tr>
                                            <tr>
                                                <td>6.0 - 6.9</td>
                                                <td class="text-center fw-bold">2.5</td>
                                                <td class="text-end"><span
                                                        class="badge-grade bg-warning-subtle text-warning">TB Khá</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>5.0 - 5.9</td>
                                                <td class="text-center fw-bold">2.0</td>
                                                <td class="text-end"><span
                                                        class="badge-grade bg-secondary-subtle text-secondary">Trung
                                                        bình</span></td>
                                            </tr>
                                            <tr>
                                                <td>4.0 - 4.9</td>
                                                <td class="text-center fw-bold">1.5</td>
                                                <td class="text-end"><span
                                                        class="badge-grade bg-danger-subtle text-danger">Yếu (Đạt)</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    < 4.0</td>
                                                <td class="text-center fw-bold">0.0</td>
                                                <td class="text-end"><span class="badge-grade bg-dark-subtle text-dark">F
                                                        (Hỏng)</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="alert alert-warning p-2 mt-3 border-0 rounded-3" style="font-size: 0.75rem;">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Áp dụng theo <strong>TT
                                        04/2022/TT-BLĐTBXH</strong> cho hệ Trung cấp nghề.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Xác nhận xóa (Thay thế cho confirm rập khuôn của trình duyệt) -->
    <div class="modal fade" id="clearConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 16px;">
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <i class="fa-solid fa-circle-exclamation text-warning fa-3x"></i>
                    </div>
                    <h5 class="fw-bold">Xóa toàn bộ dữ liệu?</h5>
                    <p class="text-muted small mb-4">Hành động này không thể hoàn tác. Toàn bộ môn học sẽ bị làm mới.</p>
                    <div class="d-grid gap-2">
                        <button class="btn btn-danger py-2 fw-bold rounded-pill" onclick="clearData()">Đồng ý xóa</button>
                        <button class="btn btn-light py-2 rounded-pill" data-bs-dismiss="modal">Hủy bỏ</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Thêm dòng mới
        function addRow() {
            const row = `
            <tr class="subject-row">
                <td><input type="text" class="form-control input-subject" placeholder="Nhập tên môn học..."></td>
                <td><input type="number" class="form-control credit" value="2" min="1"></td>
                <td><input type="text" class="form-control hs1" placeholder="9, 8"></td>
                <td><input type="text" class="form-control hs2" placeholder="7, 8"></td>
                <td><input type="number" class="form-control thi" step="0.1" min="0" max="10" placeholder="8.5"></td>
                <td><button class="btn-remove-row text-danger" onclick="removeRow(this)"><i class="fa-solid fa-circle-xmark"></i></button></td>
            </tr>`;
            document.querySelector('#subjectTable tbody').insertAdjacentHTML('beforeend', row);
        }

        // Xóa dòng
        function removeRow(btn) {
            const row = btn.closest('tr');
            row.style.opacity = '0';
            row.style.transition = 'all 0.2s';
            setTimeout(() => row.remove(), 200);
        }

        // Hiện Modal xác nhận thay vì dùng confirm()
        function showClearConfirm() {
            const myModal = new bootstrap.Modal(document.getElementById('clearConfirmModal'));
            myModal.show();
        }

        // Xóa trắng dữ liệu
        function clearData() {
            location.reload();
        }

        // Logic tính toán
        function calculateGrades() {
            let totalWeightedScore = 0;
            let totalCredits = 0;
            let totalGPA4 = 0;

            document.querySelectorAll('.subject-row').forEach(function(row) {
                const creditInput = row.querySelector('.credit');
                const thiInput = row.querySelector('.thi');

                const credit = parseFloat(creditInput.value) || 0;
                const thi = parseFloat(thiInput.value) || 0;

                const parseAvg = (selector) => {
                    const val = row.querySelector(selector).value;
                    if (!val) return 0;
                    // Hỗ trợ nhập điểm cách nhau bằng dấu phẩy hoặc dấu cách
                    const scores = val.replace(/,/g, ' ').split(/\s+/).map(s => parseFloat(s)).filter(s => !
                        isNaN(s));
                    return scores.length ? scores.reduce((a, b) => a + b) / scores.length : 0;
                };

                const avgHS1 = parseAvg('.hs1');
                const avgHS2 = parseAvg('.hs2');

                // Tính điểm quá trình: (HS1 + HS2*2) / 3
                let processScore = 0;
                if (avgHS1 > 0 || avgHS2 > 0) {
                    processScore = (avgHS1 + (avgHS2 * 2)) / 3;
                }

                // Điểm môn học: 40% quá trình + 60% thi
                const subjectScore = (processScore * 0.4) + (thi * 0.6);

                // Quy đổi hệ 4 theo thang điểm nghề
                let gpa4 = 0;
                if (subjectScore >= 9.0) gpa4 = 4.0;
                else if (subjectScore >= 8.0) gpa4 = 3.5;
                else if (subjectScore >= 7.0) gpa4 = 3.0;
                else if (subjectScore >= 6.0) gpa4 = 2.5;
                else if (subjectScore >= 5.0) gpa4 = 2.0;
                else if (subjectScore >= 4.0) gpa4 = 1.5;
                else gpa4 = 0;

                totalWeightedScore += subjectScore * credit;
                totalGPA4 += gpa4 * credit;
                totalCredits += credit;
            });

            // Hiển thị kết quả và thêm hiệu ứng animation
            const elTotalCredits = document.getElementById('totalCredits');
            const elAvg10 = document.getElementById('avg10');
            const elAvg4 = document.getElementById('avg4');

            elTotalCredits.innerText = totalCredits;
            elAvg10.innerText = totalCredits > 0 ? (totalWeightedScore / totalCredits).toFixed(2) : "0.00";
            elAvg4.innerText = totalCredits > 0 ? (totalGPA4 / totalCredits).toFixed(2) : "0.00";

            // Thêm class animation để làm nổi bật kết quả vừa thay đổi
            [elTotalCredits, elAvg10, elAvg4].forEach(el => {
                el.classList.remove('flash-animation');
                void el.offsetWidth; // trick để restart animation
                el.classList.add('flash-animation');
            });
        }
    </script>
@endsection
