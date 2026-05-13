@extends('layouts.app')

@section('title', 'Tính điểm Trung cấp nghề')

@section('content')
    <div class="container-fluid py-4">
        <div class="card border-0 shadow-sm rounded-16">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h4 class="fw-bold text-navy mb-0"><i class="fas fa-calculator me-2 text-primary"></i>Công cụ tính điểm Trung
                    cấp nghề</h4>
                <p class="text-muted small">Tính điểm nhanh theo quy chế đào tạo nghề</p>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-xl-8">
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle" id="subjectTable">
                                <thead>
                                    <tr class="text-muted small">
                                        <th style="width: 500px;">Tên môn học / Mô đun</th>
                                        <th style="width: 100px; text-align: center;">Tín chỉ</th>
                                        <th style="width: 150px; text-align: center;">Điểm HS1</th>
                                        <th style="width: 150px; text-align: center;">Điểm HS2</th>
                                        <th style="width: 100px; text-align: center;">Điểm Thi</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="subject-row">
                                        <td><input type="text" class="form-control" placeholder="Tên môn học..."></td>
                                        <td><input type="text" class="form-control credit" value="2" min="1"
                                                style="text-align: center;">
                                        </td>
                                        <td><input type="text" class="form-control hs1" placeholder="9, 8"
                                                style="text-align: center;"></td>
                                        <td><input type="text" class="form-control hs2" placeholder="7, 8"
                                                style="text-align: center;"></td>
                                        <td><input type="text" class="form-control thi" step="0.1" min="0"
                                                max="10" style="text-align: center;"></td>
                                        <td><button class="btn btn-link text-danger p-0" onclick="removeRow(this)"><i
                                                    class="fas fa-times"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="addRow()">
                            <i class="fas fa-plus me-1"></i> Thêm môn học
                        </button>
                    </div>

                    <div class="col-xl-4">
                        <div class="bg-white rounded-16 p-4 h-100 border shadow-sm">
                            <h6 class="fw-bold mb-4 text-navy"><i class="fas fa-poll-h me-2"></i>Kết quả học kỳ</h6>

                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted small">Tổng số tín chỉ:</span>
                                <span class="fw-bold" id="totalCredits">0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 text-primary">
                                <span class="fw-bold">ĐTB Hệ 10:</span>
                                <span class="fw-bold h5 mb-0" id="avg10">0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-4 text-success">
                                <span class="fw-bold">ĐTB Hệ 4:</span>
                                <span class="fw-bold h5 mb-0" id="avg4">0.00</span>
                            </div>

                            <div class="text-center mb-4">
                                <button class="btn btn-primary w-100 mb-2 py-2 rounded-pill fw-bold shadow-sm"
                                    onclick="calculateGrades()">
                                    <i class="fas fa-sync-alt me-2"></i> TÍNH ĐIỂM
                                </button>
                                <button class="btn btn-outline-secondary w-100 py-2 rounded-pill small"
                                    onclick="clearData()">
                                    <i class="fas fa-eraser me-2"></i> XÓA TRẮNG
                                </button>
                            </div>

                            <div class="mt-4 pt-3 border-top">
                                <p class="fw-bold small mb-2 text-muted"><i class="fas fa-info-circle me-1"></i> Bảng quy
                                    đổi & Xếp loại</p>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover border-0" style="font-size: 1rem;">
                                        <thead>
                                            <tr class="table-light">
                                                <th class="border-0">Hệ 10</th>
                                                <th class="border-0 text-center">Hệ 4</th>
                                                <th class="border-0 text-end">Xếp loại</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>9.0 - 10</td>
                                                <td class="text-center fw-bold text-success">4.0</td>
                                                <td class="text-end">Xuất sắc</td>
                                            </tr>
                                            <tr>
                                                <td>8.0 - 8.9</td>
                                                <td class="text-center fw-bold text-primary">3.5</td>
                                                <td class="text-end">Giỏi</td>
                                            </tr>
                                            <tr>
                                                <td>7.0 - 7.9</td>
                                                <td class="text-center fw-bold text-info">3.0</td>
                                                <td class="text-end">Khá</td>
                                            </tr>
                                            <tr>
                                                <td>6.0 - 6.9</td>
                                                <td class="text-center fw-bold text-warning">2.5</td>
                                                <td class="text-end">TB Khá</td>
                                            </tr>
                                            <tr>
                                                <td>5.0 - 5.9</td>
                                                <td class="text-center fw-bold text-secondary">2.0</td>
                                                <td class="text-end">Trung bình</td>
                                            </tr>
                                            <tr>
                                                <td>4.0 - 4.9</td>
                                                <td class="text-center fw-bold text-danger">1.5</td>
                                                <td class="text-end text-danger">Yếu (Đạt)</td>
                                            </tr>
                                            <tr class="table-danger opacity-75">
                                                <td>Dưới 4.0</td>
                                                <td class="text-center fw-bold">0.0</td>
                                                <td class="text-end">F (Hỏng)</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="alert alert-warning p-2 mt-2 border-0" style="font-size: 0.7rem;">
                                    <i class="fas fa-exclamation-triangle me-1"></i> Lưu ý: Thang điểm áp dụng theo
                                    <strong>thông tư 04/2022/TT-BLĐTBXH</strong>
                                    hiện hành cho hệ Trung cấp/Cao đẳng nghề.
                                </div>
                            </div>
                        </div>
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
            <td><input type="text" class="form-control" placeholder="Tên môn học..."></td>
            <td><input type="number" class="form-control credit" value="2" min="1"></td>
            <td><input type="text" class="form-control hs1" placeholder="9, 8"></td>
            <td><input type="text" class="form-control hs2" placeholder="7, 8"></td>
            <td><input type="number" class="form-control thi" step="0.1" min="0" max="10"></td>
            <td><button class="btn btn-link text-danger p-0" onclick="removeRow(this)"><i class="fas fa-times"></i></button></td>
        </tr>`;
            document.querySelector('#subjectTable tbody').insertAdjacentHTML('beforeend', row);
        }

        // Xóa dòng
        function removeRow(btn) {
            btn.closest('tr').remove();
        }

        // Xóa trắng dữ liệu
        function clearData() {
            if (confirm("Bạn có chắc chắn muốn xóa toàn bộ dữ liệu?")) {
                location.reload();
            }
        }

        // Logic tính toán (Đã sửa lỗi .each thành .forEach)
        function calculateGrades() {
            let totalWeightedScore = 0;
            let totalCredits = 0;
            let totalGPA4 = 0;

            // SỬA LỖI TẠI ĐÂY: Dùng forEach thay vì each
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
                // Nếu không có điểm HS1 hoặc HS2, công thức vẫn chạy dựa trên số điểm thực tế
                let processScore = 0;
                if (avgHS1 > 0 || avgHS2 > 0) {
                    processScore = (avgHS1 + (avgHS2 * 2)) / 3;
                }

                // Điểm môn học: 40% quá trình + 60% thi
                const subjectScore = (processScore * 0.4) + (thi * 0.6);

                // Quy đổi hệ 4 theo thang điểm nghề thầy cần
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

            // Hiển thị kết quả lên giao diện
            document.getElementById('totalCredits').innerText = totalCredits;
            document.getElementById('avg10').innerText = totalCredits > 0 ? (totalWeightedScore / totalCredits).toFixed(2) :
                "0.00";
            document.getElementById('avg4').innerText = totalCredits > 0 ? (totalGPA4 / totalCredits).toFixed(2) : "0.00";

            // Thêm thông báo nhỏ để người dùng biết đã tính xong
            console.log("Đã tính xong điểm cho " + totalCredits + " tín chỉ.");
        }
    </script>
@endsection
