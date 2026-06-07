@extends('layouts.app')

@push('styles')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

        :root {
            --primary: #4F46E5;
            --primary-light: #EEF2FF;
            --primary-mid: #818CF8;
            --accent: #06B6D4;
            --accent-light: #ECFEFF;
            --surface: #FFFFFF;
            --surface-2: #F8FAFC;
            --surface-3: #F1F5F9;
            --border: #E2E8F0;
            --border-strong: #CBD5E1;
            --text-primary: #0F172A;
            --text-secondary: #475569;
            --text-muted: #94A3B8;
            --danger: #EF4444;
            --danger-light: #FEF2F2;
            --warning: #F59E0B;
            --warning-light: #FFFBEB;
            --success: #10B981;
            --success-light: #ECFDF5;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 10px 25px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
        }

        body {
            font-family: 'Be Vietnam Pro', sans-serif;
            background: #F8FAFC;
            color: var(--text-primary);
        }

        .page-wrapper {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        /* === PAGE HEADER === */
        .page-header {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .header-icon-wrap {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary) 0%, #7C3AED 100%);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
        }

        .header-icon-wrap i {
            font-size: 1.5rem;
            color: #fff;
        }

        .page-header h3 {
            font-size: 1.45rem;
            font-weight: 700;
            margin: 0;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }

        .page-header p {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin: 0.2rem 0 0;
        }

        /* === NOTICE CARD === */
        .notice-card {
            background: var(--warning-light);
            border: 1px solid #FDE68A;
            border-radius: var(--radius);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
        }

        .notice-icon {
            flex-shrink: 0;
            color: var(--warning);
            font-size: 1.1rem;
            padding-top: 1px;
        }

        .notice-card h6 {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #92400E;
            margin: 0 0 0.5rem;
        }

        .notice-card p {
            font-size: 0.85rem;
            color: #78350F;
            margin: 0 0 0.6rem;
            line-height: 1.6;
        }

        .notice-card ul {
            font-size: 0.82rem;
            color: #92400E;
            margin: 0;
            padding-left: 1.2rem;
            line-height: 1.8;
        }

        .notice-card ul li span.highlight-danger {
            color: #B91C1C;
            font-weight: 600;
        }

        .notice-card ul li strong {
            color: #78350F;
        }

        /* === CARD BASE === */
        .card-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-panel-body {
            padding: 1.75rem;
        }

        /* === SECTION LABEL === */
        .section-label {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
        }

        .section-label i {
            color: var(--primary);
            font-size: 1rem;
        }

        /* === FORM ELEMENTS === */
        .field-group {
            margin-bottom: 0;
        }

        .field-label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        select.field-ctrl,
        .field-ctrl {
            width: 100%;
            background: var(--surface-2);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 0.7rem 1rem;
            font-family: 'Be Vietnam Pro', sans-serif;
            font-size: 0.88rem;
            color: var(--text-primary);
            transition: border-color 0.18s, box-shadow 0.18s;
            appearance: none;
            outline: none;
        }

        select.field-ctrl {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394A3B8' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.85rem center;
            padding-right: 2.5rem;
            cursor: pointer;
        }

        .field-ctrl:focus,
        select.field-ctrl:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
            background: #fff;
        }

        /* File input */
        .file-input-wrap {
            position: relative;
        }

        .file-input-wrap input[type="file"] {
            width: 100%;
            background: var(--surface-2);
            border: 1.5px dashed var(--border-strong);
            border-radius: var(--radius-sm);
            padding: 0.65rem 1rem;
            font-family: 'Be Vietnam Pro', sans-serif;
            font-size: 0.85rem;
            color: var(--text-secondary);
            transition: border-color 0.18s, background 0.18s;
            cursor: pointer;
        }

        .file-input-wrap input[type="file"]:hover {
            border-color: var(--primary-mid);
            background: var(--primary-light);
        }

        /* Submit button */
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, #6D28D9 100%);
            border: none;
            border-radius: var(--radius-sm);
            padding: 0.9rem 1.5rem;
            font-family: 'Be Vietnam Pro', sans-serif;
            font-size: 0.9rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.04em;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            transition: opacity 0.18s, transform 0.18s;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
        }

        .btn-submit:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit:disabled {
            opacity: 0.65;
            cursor: not-allowed;
            transform: none;
        }

        .btn-submit i {
            font-size: 1rem;
        }

        /* === ALERTS === */
        .alert-success-custom {
            background: var(--success-light);
            border: 1px solid #A7F3D0;
            border-radius: var(--radius-sm);
            padding: 0.9rem 1.1rem;
            font-size: 0.875rem;
            color: #065F46;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .alert-danger-custom {
            background: var(--danger-light);
            border: 1px solid #FECACA;
            border-radius: var(--radius-sm);
            padding: 0.9rem 1.1rem;
            font-size: 0.875rem;
            color: #991B1B;
            margin-bottom: 1.25rem;
        }

        .alert-danger-custom ul {
            margin: 0;
            padding-left: 1.2rem;
        }

        /* === PROGRESS === */
        .progress-wrap {
            margin-top: 1.5rem;
            display: none;
        }

        .progress-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .progress-status {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--primary);
        }

        .progress-pct {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--primary);
            font-family: 'JetBrains Mono', monospace;
        }

        .progress-track {
            height: 6px;
            background: var(--surface-3);
            border-radius: 99px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 99px;
            width: 0%;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            transition: width 0.3s ease;
            position: relative;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 80px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4));
            animation: shimmer 1.2s infinite;
        }

        @keyframes shimmer {
            0% {
                opacity: 0;
            }

            50% {
                opacity: 1;
            }

            100% {
                opacity: 0;
            }
        }

        .progress-note {
            margin-top: 0.75rem;
            background: var(--accent-light);
            border: 1px solid #A5F3FC;
            border-radius: var(--radius-sm);
            padding: 0.7rem 0.9rem;
            font-size: 0.8rem;
            color: #164E63;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            line-height: 1.5;
        }

        /* === TABLE SECTION === */
        .table-header {
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
        }

        .table-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table-title i {
            color: var(--primary);
        }

        .doc-count-badge {
            font-size: 0.72rem;
            font-weight: 600;
            background: var(--surface-3);
            color: var(--text-secondary);
            border: 1px solid var(--border);
            border-radius: 99px;
            padding: 0.2rem 0.65rem;
        }

        table.docs-table {
            width: 100%;
            border-collapse: collapse;
        }

        .docs-table thead th {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-muted);
            padding: 0.75rem 1rem;
            background: var(--surface-2);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        .docs-table thead th:first-child {
            padding-left: 1.5rem;
        }

        .docs-table thead th:last-child {
            padding-right: 1.5rem;
            text-align: right;
        }

        .docs-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.12s;
        }

        .docs-table tbody tr:last-child {
            border-bottom: none;
        }

        .docs-table tbody tr:hover {
            background: var(--surface-2);
        }

        .docs-table tbody td {
            padding: 0.95rem 1rem;
            vertical-align: middle;
        }

        .docs-table tbody td:first-child {
            padding-left: 1.5rem;
        }

        .docs-table tbody td:last-child {
            padding-right: 1.5rem;
            text-align: right;
        }

        .docs-table-wrap {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .doc-name-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .doc-icon {
            width: 36px;
            height: 36px;
            background: var(--danger-light);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .doc-icon i {
            color: var(--danger);
            font-size: 1rem;
        }

        .doc-name {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* Badges */
        .badge-system {
            background: #F1F5F9;
            color: #475569;
            border: 1px solid #CBD5E1;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.3rem 0.65rem;
            border-radius: 99px;
            display: inline-block;
        }

        .badge-course {
            background: #EFF6FF;
            color: #1D4ED8;
            border: 1px solid #BFDBFE;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.3rem 0.65rem;
            border-radius: 99px;
            display: inline-block;
        }

        .badge-danger {
            background: var(--danger-light);
            color: #991B1B;
            border: 1px solid #FECACA;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.3rem 0.65rem;
            border-radius: 99px;
            display: inline-block;
        }

        .badge-vectors {
            background: var(--primary-light);
            color: var(--primary);
            border: 1px solid #C7D2FE;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.7rem;
            border-radius: 99px;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-family: 'JetBrains Mono', monospace;
        }

        .badge-vectors i {
            font-size: 0.7rem;
        }

        .date-text {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-family: 'JetBrains Mono', monospace;
        }

        .btn-delete {
            background: none;
            border: none;
            padding: 0.35rem 0.7rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            border-radius: var(--radius-sm);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: color 0.15s, background 0.15s;
        }

        .btn-delete:hover {
            color: var(--danger);
            background: var(--danger-light);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3.5rem 1rem;
        }

        .empty-state .empty-icon {
            font-size: 2.5rem;
            color: var(--border-strong);
            margin-bottom: 0.75rem;
        }

        .empty-state p {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin: 0;
        }

        /* Field error */
        .field-error {
            font-size: 0.78rem;
            color: var(--danger);
            margin-top: 0.35rem;
        }

        @media (max-width: 767.98px) {
            .page-wrapper {
                padding: 1.25rem 0.75rem;
            }

            .page-header,
            .notice-card {
                align-items: flex-start;
            }

            .page-header {
                gap: 0.9rem;
                margin-bottom: 1.25rem;
            }

            .header-icon-wrap {
                width: 46px;
                height: 46px;
            }

            .page-header h3 {
                font-size: 1.15rem;
                line-height: 1.3;
            }

            .card-panel-body {
                padding: 1.1rem;
            }

            .notice-card {
                padding: 1rem;
            }

            .table-header {
                align-items: flex-start;
                flex-direction: column;
                gap: 0.6rem;
                padding: 1rem;
            }

            table.docs-table {
                min-width: 720px;
            }

            .docs-table thead th:first-child,
            .docs-table tbody td:first-child {
                padding-left: 1rem;
            }

            .docs-table thead th:last-child,
            .docs-table tbody td:last-child {
                padding-right: 1rem;
            }

            .doc-name {
                max-width: 220px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrapper">

        {{-- Header --}}
        <div class="page-header">
            <div class="header-icon-wrap">
                <i class="fas fa-brain"></i>
            </div>
            <div>
                <h3>Huấn luyện Trí tuệ Nhân tạo</h3>
                <p>Cung cấp tài liệu để AI học kiến thức và hỗ trợ học sinh hiệu quả hơn</p>
            </div>
        </div>

        {{-- Notice --}}
        <div class="notice-card">
            <div class="notice-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div>
                <h6>Lưu ý trước khi Train</h6>
                <p>Nếu file quá lớn (trên 50 trang): Thầy/Cô nên <strong>chia nhỏ file PDF thành từng chương</strong> trước
                    khi upload để AI tìm kiếm chính xác hơn và tránh nghẽn API Google khi tạo Vector.</p>
                <ul>
                    <li>Chỉ dùng file PDF dạng văn bản <span class="highlight-danger">(không dùng ảnh quét/scan)</span></li>
                    <li>Hệ thống sử dụng Gemini Flash để tạo Vector 3072 chiều</li>
                    <li><strong>Tài liệu được Train sẽ dùng cho chatbot và ngân hàng câu hỏi</strong></li>
                </ul>
            </div>
        </div>

        {{-- Upload Form --}}
        <div class="card-panel">
            <div class="card-panel-body">
                <div class="section-label">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Tải lên tài liệu mới
                </div>

                @if (session('success'))
                    <div class="alert-success-custom">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert-danger-custom">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                    @csrf
                    <div class="form-row">
                        <div class="field-group">
                            <label class="field-label">Khóa học áp dụng</label>
                            <select name="course_id" class="field-ctrl" required>
                                <option value="">-- Chọn khóa học --</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                                <option value="0">-- Dùng chung toàn hệ thống --</option>
                            </select>
                            @error('course_id')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="field-group">
                            <label class="field-label">Tài liệu (Định dạng PDF)</label>
                            <div class="file-input-wrap">
                                <input type="file" name="file" accept="application/pdf" required>
                            </div>
                            @error('file')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="btnUpload">
                        <i class="fas fa-rocket"></i>
                        BẮT ĐẦU TRÍCH XUẤT KIẾN THỨC
                    </button>
                </form>

                {{-- Progress --}}
                <div class="progress-wrap" id="progressContainer">
                    <div class="progress-meta">
                        <span class="progress-status" id="progressStatus">Đang khởi tạo cấu trúc AI...</span>
                        <span class="progress-pct" id="progressPercent">0%</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-bar" id="progressBar"></div>
                    </div>
                    <div class="progress-note">
                        <i class="fas fa-circle-notch fa-spin" style="margin-top:2px; flex-shrink:0;"></i>
                        Hệ thống đang phân tích ngữ cảnh và tạo tọa độ Vector. Vui lòng giữ kết nối và không đóng trình
                        duyệt.
                    </div>
                </div>
            </div>
        </div>

        {{-- Document Table --}}
        <div class="card-panel">
            <div class="table-header">
                <div class="table-title">
                    <i class="fas fa-database"></i>
                    Kho tri thức đã huấn luyện
                </div>
                <span class="doc-count-badge">{{ $documents->count() }} tài liệu</span>
            </div>

            <div class="docs-table-wrap">
                <table class="docs-table">
                    <thead>
                        <tr>
                            <th>Tên tài liệu</th>
                            <th>Khóa học</th>
                            <th>Vectors</th>
                            <th>Ngày nạp</th>
                            <th>Quản lý</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $doc)
                            <tr>
                                <td>
                                    <div class="doc-name-cell">
                                        <div class="doc-icon"><i class="fas fa-file-pdf"></i></div>
                                        <span class="doc-name">{{ $doc->document_name }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if ($doc->course_id == 0)
                                        <span class="badge-system">Toàn hệ thống</span>
                                    @elseif ($doc->course)
                                        <span class="badge-course">{{ $doc->course->title }}</span>
                                    @else
                                        <span class="badge-danger">Không khả dụng (ID: {{ $doc->course_id }})</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge-vectors">
                                        <i class="fas fa-vector-square"></i>
                                        {{ $doc->total_chunks }}
                                    </span>
                                </td>
                                <td>
                                    <span class="date-text">{{ $doc->created_at->format('d/m/Y') }}</span>
                                </td>
                                <td>
                                    <form action="{{ route('documents.destroy', $doc->document_name) }}" method="POST"
                                        class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-delete"
                                            onclick="return confirm('Xóa tài liệu này? AI sẽ không còn trả lời được nội dung liên quan.')">
                                            <i class="fas fa-trash-alt"></i> Xóa
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
                                        <p>Chưa có tài liệu tri thức nào được nạp</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        document.getElementById('uploadForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('btnUpload');
            const container = document.getElementById('progressContainer');
            const bar = document.getElementById('progressBar');
            const percent = document.getElementById('progressPercent');
            const status = document.getElementById('progressStatus');

            btn.disabled = true;
            btn.innerHTML =
                '<span class="fas fa-circle-notch fa-spin" style="margin-right:.5rem"></span> Hệ thống đang xử lý...';
            container.style.display = 'block';

            const messages = [
                "Đang đọc nội dung file PDF...",
                "Đang trích xuất văn bản thô...",
                "Đang chia nhỏ dữ liệu thành các đoạn ngữ cảnh...",
                "Đang kết nối tới Google Gemini API...",
                "Đang khởi tạo Vector 3072 chiều...",
                "Đang mã hóa kiến thức vào không gian đa chiều...",
                "Đang lưu trữ vào PostgreSQL Vector DB...",
                "Đang hoàn tất quá trình huấn luyện..."
            ];

            let width = 0;

            const interval = setInterval(function() {
                if (width >= 94) {
                    clearInterval(interval);
                    status.innerText = "Đang kiểm tra và phản hồi...";
                } else {
                    let inc = width < 40 ? 1.5 : width < 70 ? 0.4 : width < 90 ? 0.1 : 0.05;
                    width += inc;
                    bar.style.width = width + '%';
                    percent.innerText = Math.round(width) + '%';
                    const msgIdx = Math.min(Math.floor(width / (100 / messages.length)), messages.length -
                        1);
                    if (status.innerText !== messages[msgIdx]) status.innerText = messages[msgIdx];
                }
            }, 100);
        });
    </script>
@endsection
