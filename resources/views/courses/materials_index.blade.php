@extends('layouts.app')

@section('title', 'Kho học liệu')

@section('content')
    <style>
        .materials-index {
            --ml-ink: var(--sl-text);
            --ml-muted: var(--sl-text-muted);
            --ml-bg: transparent;
            --ml-surface: var(--sl-surface);
            --ml-border: var(--sl-border);
            --ml-primary: var(--sl-primary);
            --ml-primary-dark: var(--sl-primary-active);
            --ml-primary-soft: var(--sl-primary-soft);
            --ml-accent: var(--sl-success);
            --ml-accent-soft: var(--sl-success-soft);
            --ml-amber: var(--sl-warning);
            --ml-amber-soft: var(--sl-warning-soft);
            --ml-red: var(--sl-danger);
            --ml-red-soft: var(--sl-danger-soft);
            --ml-radius-lg: var(--sl-radius-md);
            --ml-radius-md: var(--sl-radius-sm);
            font-family: var(--sl-font-sans);
            background: var(--ml-bg);
            min-height: 0;
        }

        .materials-index * {
            box-sizing: border-box;
        }

        .materials-index :focus-visible {
            outline: 2px solid var(--ml-primary);
            outline-offset: 2px;
        }

        .materials-index-shell {
            max-width: 1500px;
        }

        .materials-index-hero,
        .materials-course-card,
        .materials-library-panel,
        .materials-sync-panel {
            background: var(--ml-surface);
            border: 1px solid var(--ml-border);
            border-radius: var(--ml-radius-lg);
            box-shadow: var(--sl-shadow-xs);
        }

        /* ---------- Hero ---------- */
        .materials-index-hero {
            margin-bottom: 18px;
            overflow: hidden;
            padding: 0;
        }

        .materials-index-hero-top {
            padding: 0;
        }

        .materials-index-hero .lms-page-header {
            align-items: center;
            margin: 0;
            padding: 18px 20px;
        }

        .materials-stat-shelf {
            border-top: 1px solid var(--ml-border);
            display: grid;
            grid-template-columns: repeat(3, 1fr);
        }

        .materials-stat {
            align-items: center;
            border-left: 1px solid var(--ml-border);
            display: flex;
            gap: 12px;
            min-height: 72px;
            padding: 12px 20px;
        }

        .materials-stat:first-child {
            border-left: 0;
        }

        .materials-stat-num {
            color: var(--ml-ink);
            font-family: var(--sl-font-sans);
            font-size: 21px;
            font-weight: 700;
            line-height: 1;
        }

        .materials-stat-label {
            color: var(--ml-muted);
            font-size: 12px;
            font-weight: 600;
            margin-top: 5px;
        }

        .materials-stat-icon {
            align-items: center;
            background: var(--ml-primary-soft);
            border-radius: 11px;
            color: var(--ml-primary);
            display: flex;
            flex: 0 0 40px;
            font-size: 15px;
            height: 40px;
            justify-content: center;
        }

        .materials-stat--success .materials-stat-icon {
            background: var(--ml-accent-soft);
            color: var(--ml-accent);
        }

        .materials-stat--warn .materials-stat-icon {
            background: var(--ml-amber-soft);
            color: var(--ml-amber);
        }

        .materials-stat--warn .materials-stat-num {
            color: var(--ml-amber);
        }

        /* ---------- Panels ---------- */
        .materials-library-panel,
        .materials-sync-panel {
            margin-bottom: 18px;
            padding: 22px 24px;
        }

        .materials-course-panel {
            margin-bottom: 0;
        }

        .materials-section-head {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .materials-section-title {
            align-items: center;
            color: var(--ml-ink);
            display: flex;
            font-family: var(--sl-font-sans);
            font-size: 18px;
            font-weight: 700;
            gap: 8px;
            margin: 0 0 4px;
        }

        .materials-section-note {
            color: var(--ml-muted);
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            max-width: 520px;
        }

        .materials-sync-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .materials-action {
            align-items: center;
            border: 1px solid transparent;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            font-size: 13px;
            font-weight: 700;
            gap: 7px;
            justify-content: center;
            min-height: 40px;
            padding: 9px 15px;
            text-decoration: none;
            transition: transform .12s ease, box-shadow .12s ease, background .12s ease;
        }

        .materials-action:hover {
            text-decoration: none;
        }

        .materials-action--ghost {
            background: var(--ml-primary-soft);
            color: var(--ml-primary-dark);
        }

        .materials-action--ghost:hover {
            background: #dde2ff;
            color: var(--ml-primary-dark);
        }

        .materials-action--primary {
            background: var(--ml-primary);
            color: #fff;
        }

        .materials-action--primary:hover {
            background: var(--ml-primary-dark);
            box-shadow: 0 6px 16px rgba(58, 79, 240, .28);
            color: #fff;
            transform: translateY(-1px);
        }

        .materials-action--download {
            background: var(--ml-primary-soft);
            color: var(--ml-primary-dark);
            font-size: 12.5px;
            min-height: 34px;
            padding: 7px 12px;
        }

        .materials-action--download:hover {
            background: #dde2ff;
            color: var(--ml-primary-dark);
        }

        /* ---------- Sync operations ---------- */
        .materials-operations {
            display: grid;
            gap: 8px;
            margin-top: 14px;
        }

        .materials-operation {
            align-items: center;
            background: #f9fafc;
            border: 1px solid var(--ml-border);
            border-radius: 12px;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            padding: 11px 14px;
        }

        .materials-operation-main {
            color: #29324a;
            font-size: 12.5px;
            font-weight: 700;
        }

        .materials-operation-result {
            color: var(--ml-muted);
            font-size: 11.5px;
            margin-top: 3px;
        }

        .materials-operation-status {
            align-items: center;
            border-radius: 999px;
            display: inline-flex;
            flex-shrink: 0;
            font-size: 11px;
            font-weight: 700;
            gap: 5px;
            padding: 5px 10px;
            white-space: nowrap;
        }

        .materials-operation-status::before {
            border-radius: 50%;
            content: '';
            height: 6px;
            width: 6px;
        }

        .status-queued,
        .status-processing {
            background: var(--ml-amber-soft);
            color: var(--ml-amber);
        }

        .status-queued::before,
        .status-processing::before {
            background: var(--ml-amber);
            animation: ml-pulse 1.1s ease-in-out infinite;
        }

        .status-completed {
            background: var(--ml-accent-soft);
            color: var(--ml-accent);
        }

        .status-completed::before {
            background: var(--ml-accent);
        }

        .status-failed {
            background: var(--ml-red-soft);
            color: var(--ml-red);
        }

        .status-failed::before {
            background: var(--ml-red);
        }

        @keyframes ml-pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .35;
            }
        }

        .materials-sync-empty {
            border: 1px dashed var(--ml-border);
            border-radius: 12px;
            color: var(--ml-muted);
            font-size: 12.5px;
            margin-top: 14px;
            padding: 14px;
            text-align: center;
        }

        /* ---------- Filter ---------- */
        .materials-filter {
            align-items: end;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, 1fr) 190px auto auto;
            margin-bottom: 18px;
        }

        .materials-filter-field {
            min-width: 0;
        }

        .materials-filter-field label {
            color: var(--ml-muted);
            display: block;
            font-size: 10.5px;
            font-weight: 750;
            letter-spacing: .04em;
            margin: 0 0 6px 2px;
            text-transform: uppercase;
        }

        .materials-filter .form-control,
        .materials-filter .form-select {
            border: 1px solid var(--ml-border);
            border-radius: 10px;
            font-size: 13.5px;
            min-height: 42px;
        }

        .materials-filter .form-control:focus,
        .materials-filter .form-select:focus {
            border-color: var(--ml-primary);
            box-shadow: 0 0 0 3px rgba(58, 79, 240, .12);
        }

        .materials-filter-clear {
            align-items: center;
            color: var(--ml-muted);
            display: inline-flex;
            font-size: 12.5px;
            font-weight: 600;
            gap: 4px;
            justify-content: center;
            text-decoration: none;
            white-space: nowrap;
        }

        .materials-filter-clear:hover {
            color: var(--ml-red);
            text-decoration: none;
        }

        /* ---------- Asset grid ---------- */
        .materials-asset-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .materials-asset {
            border: 1px solid var(--ml-border);
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-width: 0;
            padding: 16px;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .materials-asset:hover {
            border-color: #c9d2ec;
            box-shadow: 0 8px 20px rgba(16, 22, 43, .06);
        }

        .materials-asset-top {
            align-items: flex-start;
            display: flex;
            gap: 11px;
        }

        .materials-asset-icon {
            align-items: center;
            background: var(--ml-primary-soft);
            border-radius: 12px;
            color: var(--ml-primary);
            display: flex;
            flex: 0 0 40px;
            font-size: 15px;
            height: 40px;
            justify-content: center;
        }

        .materials-asset-title {
            color: #172033;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.4;
            margin: 0;
            overflow-wrap: anywhere;
        }

        .materials-asset-meta {
            color: var(--ml-muted);
            font-size: 11px;
            font-weight: 600;
            line-height: 1.55;
            margin-top: 3px;
        }

        .materials-asset-footer {
            align-items: center;
            display: flex;
            gap: 8px;
            justify-content: space-between;
            margin-top: auto;
        }

        .materials-source {
            color: var(--ml-muted);
            font-size: 11px;
            line-height: 1.4;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .materials-badge {
            background: #f1f4f9;
            border-radius: 999px;
            color: #475569;
            display: inline-flex;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 9px;
        }

        /* type accents (data-type on .materials-asset) */
        .materials-asset[data-type="pdf"] .materials-asset-icon {
            background: var(--ml-red-soft);
            color: var(--ml-red);
        }

        .materials-asset[data-type="slide"] .materials-asset-icon {
            background: #ffefdd;
            color: #b45f18;
        }

        .materials-asset[data-type="image"] .materials-asset-icon {
            background: #f2ecfe;
            color: #7c3fd8;
        }

        .materials-asset[data-type="video"] .materials-asset-icon {
            background: #fdecf5;
            color: #c22a80;
        }

        .materials-asset[data-type="document"] .materials-asset-icon {
            background: var(--ml-primary-soft);
            color: var(--ml-primary-dark);
        }

        .materials-asset[data-type="code"] .materials-asset-icon {
            background: var(--ml-accent-soft);
            color: var(--ml-accent);
        }

        .materials-asset[data-type="website"] .materials-asset-icon {
            background: #e2f4f8;
            color: #0f7a91;
        }

        .materials-asset[data-type="other"] .materials-asset-icon {
            background: #eef1f6;
            color: #64748b;
        }

        /* ---------- Course grid ---------- */
        .materials-course-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .materials-course-card {
            color: inherit;
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-height: 170px;
            padding: 20px;
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .materials-course-card:hover {
            border-color: #bcc9fb;
            box-shadow: 0 16px 34px rgba(58, 79, 240, .15);
            color: inherit;
            transform: translateY(-2px);
        }

        .materials-course-icon {
            align-items: center;
            background: var(--ml-primary-soft);
            border-radius: 14px;
            color: var(--ml-primary-dark);
            display: inline-flex;
            font-size: 19px;
            height: 46px;
            justify-content: center;
            width: 46px;
        }

        .materials-course-title {
            color: var(--ml-ink);
            font-family: var(--sl-font-sans);
            font-size: 17px;
            font-weight: 700;
            line-height: 1.35;
            margin: 0;
        }

        .materials-course-meta {
            color: var(--ml-muted);
            font-size: 12.5px;
            font-weight: 600;
            line-height: 1.5;
            margin-top: 6px;
        }

        .materials-course-footer {
            align-items: center;
            display: flex;
            justify-content: space-between;
            margin-top: auto;
        }

        .materials-count {
            background: #f8fafc;
            border: 1px solid var(--ml-border);
            border-radius: 999px;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 11px;
        }

        .materials-open {
            align-items: center;
            color: var(--ml-primary);
            display: inline-flex;
            font-size: 13px;
            font-weight: 700;
            gap: 4px;
            transition: gap .15s ease;
        }

        .materials-course-card:hover .materials-open {
            gap: 8px;
        }

        /* ---------- Empty states ---------- */
        .materials-empty {
            align-items: center;
            background: var(--ml-surface);
            border: 1.5px dashed #cbd5e1;
            border-radius: var(--ml-radius-lg);
            color: var(--ml-muted);
            display: flex;
            flex-direction: column;
            font-weight: 600;
            gap: 6px;
            padding: 40px 24px;
            text-align: center;
        }

        .materials-empty-icon {
            align-items: center;
            background: var(--ml-primary-soft);
            border-radius: 50%;
            color: var(--ml-primary);
            display: flex;
            font-size: 20px;
            height: 48px;
            justify-content: center;
            margin-bottom: 4px;
            width: 48px;
        }

        .materials-empty-title {
            color: var(--ml-ink);
            font-size: 14.5px;
            font-weight: 700;
        }

        .materials-empty-sub {
            font-size: 12.5px;
            font-weight: 500;
            max-width: 380px;
        }

        .materials-pagination {
            margin: 18px -24px -22px;
        }

        @media (max-width: 991.98px) {

            .materials-course-grid,
            .materials-asset-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .materials-stat-shelf {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 575.98px) {

            .materials-index-shell {
                padding-inline: 14px;
            }

            .materials-index-hero .lms-page-header {
                align-items: stretch;
                padding: 16px;
            }

            .materials-course-grid,
            .materials-asset-grid,
            .materials-filter {
                grid-template-columns: 1fr;
            }

            .materials-filter-clear {
                justify-content: flex-start;
            }

            .materials-section-head {
                flex-direction: column;
            }

            .materials-stat-shelf {
                grid-template-columns: 1fr;
            }

            .materials-stat {
                border-left: 0;
                border-top: 1px solid var(--ml-border);
            }

            .materials-stat:first-child {
                border-top: 0;
            }
        }
    </style>

    <div class="materials-index">
        <div class="lms-page materials-index-shell">
            <div class="materials-index-hero">
                <div class="materials-index-hero-top">
                    <x-ui.page-header title="Kho học liệu">
                        <x-slot:meta>
                            <span><i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                                Quản lý và tái sử dụng tài liệu học tập giữa các khóa học
                            </span>
                        </x-slot:meta>
                    </x-ui.page-header>
                </div>
                @if ($materials)
                    @php
                        $pendingSyncCount = $syncOperations->whereIn('status', ['queued', 'processing'])->count();
                    @endphp
                    <div class="materials-stat-shelf">
                        <div class="materials-stat">
                            <span class="materials-stat-icon"><i class="fa-solid fa-file-lines"
                                    aria-hidden="true"></i></span>
                            <div>
                                <div class="materials-stat-num">{{ $materials->total() }}</div>
                                <div class="materials-stat-label">Học liệu trong kho</div>
                            </div>
                        </div>
                        <div class="materials-stat materials-stat--success">
                            <span class="materials-stat-icon"><i class="fa-solid fa-book-open"
                                    aria-hidden="true"></i></span>
                            <div>
                                <div class="materials-stat-num">{{ $courses->count() }}</div>
                                <div class="materials-stat-label">Khóa học khả dụng</div>
                            </div>
                        </div>
                        <div class="materials-stat @if ($pendingSyncCount) materials-stat--warn @endif">
                            <span class="materials-stat-icon"><i class="fa-solid fa-rotate"
                                    aria-hidden="true"></i></span>
                            <div>
                                <div class="materials-stat-num">{{ $pendingSyncCount }}</div>
                                <div class="materials-stat-label">Tác vụ đồng bộ đang chạy</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if ($materials)
                <section class="materials-sync-panel">
                    <div class="materials-section-head mb-0">
                        <div>
                            <h2 class="materials-section-title"><i class="fa-solid fa-cloud-arrow-down text-primary"></i>Đồng bộ
                                file cũ</h2>
                            <p class="materials-section-note">Quét file bài học và ảnh R2 trong nội dung. Bài nộp học viên,
                                backup và file hệ thống không được đưa vào kho.</p>
                        </div>
                        <div class="materials-sync-actions">
                            <form method="POST" action="{{ route('materials.legacy.scan') }}">
                                @csrf
                                <button class="materials-action materials-action--ghost" type="submit">
                                    <i class="fa-solid fa-magnifying-glass"></i> Quét và xem trước
                                </button>
                            </form>
                            <form method="POST" action="{{ route('materials.legacy.sync') }}"
                                onsubmit="return confirm('Đồng bộ các file hợp lệ vào kho học liệu? File trên R2 sẽ không bị sao chép.');">
                                @csrf
                                <button class="materials-action materials-action--primary" type="submit">
                                    <i class="fa-solid fa-rotate"></i> Đồng bộ vào kho
                                </button>
                            </form>
                        </div>
                    </div>

                    @if ($syncOperations->isNotEmpty())
                        <div class="materials-operations" id="materialSyncOperations">
                            @foreach ($syncOperations as $operation)
                                @php($result = $operation->result ?? [])
                                <div class="materials-operation"
                                    data-status-url="{{ route('ai-operations.show', $operation->uuid) }}"
                                    data-operation-status="{{ $operation->status }}">
                                    <div>
                                        <div class="materials-operation-main">
                                            {{ $operation->feature === 'material_library_scan' ? 'Quét xem trước' : 'Đồng bộ học liệu' }}
                                            · {{ $operation->created_at->format('d/m/Y H:i') }}
                                        </div>
                                        <div class="materials-operation-result">
                                            @if ($operation->status === 'completed')
                                                {{ $result['unique_files'] ?? 0 }} file ·
                                                {{ $result['available_files'] ?? 0 }} tồn tại ·
                                                {{ $result['missing_files'] ?? 0 }} bị thiếu ·
                                                {{ $result['imported'] ?? 0 }} đã thêm mới
                                            @elseif ($operation->status === 'failed')
                                                {{ $operation->error_message ?: 'Tác vụ thất bại.' }}
                                            @else
                                                Tác vụ đang được xử lý trên queue documents.
                                            @endif
                                        </div>
                                    </div>
                                    <span class="materials-operation-status status-{{ $operation->status }}">
                                        {{ match ($operation->status) {'queued' => 'Đang chờ','processing' => 'Đang quét','completed' => 'Hoàn tất','failed' => 'Thất bại',default => $operation->status} }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="materials-sync-empty">Chưa có lần đồng bộ nào. Nhấn “Quét và xem trước” để bắt đầu.
                        </div>
                    @endif
                </section>

                <section class="materials-library-panel">
                    <div class="materials-section-head">
                        <div>
                            <h2 class="materials-section-title">Tất cả học liệu có thể tái sử dụng</h2>
                            <p class="materials-section-note">File trong danh mục chưa tự động hiển thị cho học viên. Hãy mở
                                một khóa học và chọn “Gắn học liệu đã có”.</p>
                        </div>
                        <span class="materials-count">{{ $materials->total() }} học liệu</span>
                    </div>

                    <form class="materials-filter" method="GET" action="{{ route('materials.index') }}">
                        <div class="materials-filter-field">
                            <label for="materials-search">Tìm học liệu</label>
                            <input id="materials-search" class="form-control" type="search" name="q"
                                value="{{ request('q') }}" placeholder="Tên học liệu hoặc tên file">
                        </div>
                        <div class="materials-filter-field">
                            <label for="materials-type">Định dạng</label>
                            <select id="materials-type" class="form-select" name="type">
                                <option value="">Tất cả định dạng</option>
                                @foreach (['pdf' => 'PDF', 'slide' => 'Bài trình chiếu', 'image' => 'Hình ảnh', 'video' => 'Video', 'document' => 'Tài liệu', 'code' => 'Mã nguồn', 'website' => 'Trang web', 'other' => 'Khác'] as $value => $label)
                                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button class="materials-action materials-action--primary" type="submit"><i
                            class="fa-solid fa-filter"></i> Lọc</button>
                        @if (request('q') || request('type'))
                            <a class="materials-filter-clear" href="{{ route('materials.index') }}"><i
                                    class="fa-solid fa-xmark"></i> Xóa lọc</a>
                        @endif
                    </form>

                    @if ($materials->isEmpty())
                        <div class="materials-empty">
                            <span class="materials-empty-icon"><i class="fa-solid fa-inbox"></i></span>
                            <div class="materials-empty-title">Chưa có học liệu phù hợp</div>
                            <div class="materials-empty-sub">Thử đổi từ khóa hoặc định dạng tìm kiếm, hoặc chạy “Quét và xem
                                trước” ở trên để tìm file cũ đưa vào kho.</div>
                        </div>
                    @else
                        <div class="materials-asset-grid">
                            @foreach ($materials as $material)
                                @php($sourceCourse = $material->sources->pluck('course')->filter()->first())
                                <article class="materials-asset" data-type="{{ $material->type ?? 'other' }}">
                                    <div class="materials-asset-top">
                                        <span class="materials-asset-icon"><i
                                                class="fa-solid {{ $material->iconClass() }}"></i></span>
                                        <div class="min-w-0">
                                            <h3 class="materials-asset-title">{{ $material->title }}</h3>
                                            <div class="materials-asset-meta">
                                                {{ $material->typeLabel() }} · {{ $material->humanSize() }}
                                                @if ($material->imported_at)
                                                    · Đồng bộ {{ $material->imported_at->format('d/m/Y') }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="materials-source"
                                        title="{{ $sourceCourse?->title ?? $material->original_name }}">
                                        <i class="fa-solid fa-book me-1"></i>
                                        {{ $sourceCourse?->title ?? ($material->original_name ?: 'Học liệu dùng chung') }}
                                    </div>
                                    <div class="materials-asset-footer">
                                        <span class="materials-badge">{{ $material->sources_count }} nguồn sử dụng</span>
                                        @if ($material->isLink())
                                            <a class="materials-action materials-action--download"
                                                href="{{ $material->url }}" target="_blank" rel="noopener">Mở link</a>
                                        @else
                                            <a class="materials-action materials-action--download"
                                                href="{{ route('materials.library.download', $material) }}"><i
                                                    class="fa-solid fa-download"></i> Tải</a>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <x-ui.pagination :paginator="$materials" item-label="học liệu" class="materials-pagination" />
                    @endif
                </section>
            @endif

            <section class="materials-library-panel materials-course-panel">
                <div class="materials-section-head">
                    <div>
                        <h2 class="materials-section-title">Kho theo khóa học</h2>
                        <p class="materials-section-note">Mở khóa học để gắn học liệu, đặt điều kiện hiển thị và quản lý lớp
                            áp dụng.</p>
                    </div>
                </div>

                @if ($courses->isEmpty())
                    <div class="materials-empty">
                        <span class="materials-empty-icon"><i class="fa-solid fa-folder-open"></i></span>
                        <div class="materials-empty-title">Chưa có khóa học nào khả dụng</div>
                    </div>
                @else
                    <div class="materials-course-grid">
                        @foreach ($courses as $course)
                            <a class="materials-course-card" href="{{ route('courses.materials.index', $course->id) }}">
                                <span class="materials-course-icon"><i class="fa-solid fa-book-open"></i></span>
                                <div>
                                    <h2 class="materials-course-title">{{ $course->title }}</h2>
                                    <div class="materials-course-meta">
                                        {{ $course->teacher?->name ?? 'Chưa có giáo viên' }}
                                        @if ($course->classes->isNotEmpty())
                                            · {{ $course->classes->pluck('name')->take(2)->join(', ') }}
                                        @endif
                                    </div>
                                </div>
                                <div class="materials-course-footer">
                                    <span class="materials-count">{{ $course->materials_count }} học liệu</span>
                                    <span class="materials-open">Mở kho <i class="fa-solid fa-arrow-right"></i></span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const activeOperations = [...document.querySelectorAll(
                '[data-operation-status="queued"], [data-operation-status="processing"]')];
            if (!activeOperations.length) return;

            const timer = setInterval(async function() {
                let stillActive = false;
                for (const operation of activeOperations) {
                    if (!['queued', 'processing'].includes(operation.dataset.operationStatus)) continue;
                    stillActive = true;
                    try {
                        const response = await fetch(operation.dataset.statusUrl, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!response.ok) continue;
                        const data = await response.json();
                        operation.dataset.operationStatus = data.status;
                        if (['completed', 'failed'].includes(data.status)) {
                            window.location.reload();
                            return;
                        }
                    } catch (_) {}
                }
                if (!stillActive) clearInterval(timer);
            }, 3000);
        });
    </script>
@endpush
