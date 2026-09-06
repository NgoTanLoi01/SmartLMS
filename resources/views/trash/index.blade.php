@extends('layouts.app')

@section('title', 'Thùng rác')

@section('content')
    <style>
        .trash-page { color: #263A37; }
        .trash-head { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:18px; }
        .trash-head h1 { margin:0 0 5px; font-size:22px; font-weight:700; }
        .trash-head p { margin:0; color:#61736F; font-size:13px; }
        .trash-warning { display:flex; gap:10px; align-items:flex-start; padding:12px 14px; margin-bottom:16px; border:1px solid #FBCE5A; border-radius:12px; color:#705817; background:#FFF8DF; font-size:12.5px; }
        .trash-tabs { display:flex; gap:8px; overflow-x:auto; padding-bottom:5px; margin-bottom:15px; }
        .trash-tab { display:flex; align-items:center; gap:7px; min-width:max-content; padding:9px 12px; border:1px solid #D9DDD3; border-radius:10px; color:#61736F; background:#fff; font-size:12.5px; font-weight:600; text-decoration:none; }
        .trash-tab:hover { color:#385652; border-color:#B0DAD2; }
        .trash-tab.active { color:#385652; border-color:#B0DAD2; background:#EEF5F2; }
        .trash-tab__count { min-width:21px; padding:2px 6px; border-radius:999px; color:#61736F; background:#EFEDDE; font-size:10.5px; text-align:center; }
        .trash-tab.active .trash-tab__count { color:#385652; background:#DCE9E5; }
        .trash-card { overflow:hidden; border:1px solid #D9DDD3; border-radius:14px; background:#fff; box-shadow:0 6px 24px rgba(15,23,42,.04); }
        .trash-toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:14px 16px; border-bottom:1px solid #eef2f7; }
        .trash-search { display:flex; gap:8px; width:min(420px, 100%); }
        .trash-search input { width:100%; height:38px; padding:0 12px; border:1px solid #dbe3ee; border-radius:9px; font-size:13px; }
        .trash-actions { display:flex; gap:8px; }
        .trash-btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; min-height:36px; padding:0 12px; border:0; border-radius:9px; font-size:12.5px; font-weight:600; text-decoration:none; cursor:pointer; }
        .trash-btn:disabled { opacity:.5; cursor:not-allowed; }
        .trash-btn--primary { color:#fff; background:#54726E; }
        .trash-btn--muted { color:#61736F; background:#EFEDDE; }
        .trash-btn--danger { color:#b91c1c; border:1px solid #fecaca; background:#fff1f2; }
        .trash-table-wrap { overflow-x:auto; }
        .trash-table { width:100%; border-collapse:collapse; }
        .trash-table th { padding:10px 14px; color:#61736F; background:#F7F7F2; font-size:11px; font-weight:700; text-align:left; text-transform:uppercase; letter-spacing:.04em; }
        .trash-table td { padding:13px 14px; border-top:1px solid #EFEDDE; vertical-align:middle; font-size:12.5px; }
        .trash-item-title { margin-bottom:3px; color:#344743; font-weight:650; }
        .trash-item-context { color:#61736F; font-size:11.5px; }
        .trash-row-actions { display:flex; justify-content:flex-end; gap:7px; }
        .trash-empty { padding:52px 20px; color:#61736F; text-align:center; }
        .trash-empty i { display:block; margin-bottom:10px; color:#BCC8BF; font-size:32px; }
        .trash-footer { padding:12px 16px; border-top:1px solid #eef2f7; }
        .trash-confirm-copy { color:#61736F; font-size:13px; line-height:1.6; }
        .trash-confirm-code { padding:2px 6px; border-radius:5px; color:#b91c1c; background:#fee2e2; font-weight:700; }
        @media (max-width: 767.98px) {
            .trash-head, .trash-toolbar { flex-direction:column; align-items:stretch; }
            .trash-search { width:100%; }
            .trash-actions { display:grid; grid-template-columns:1fr 1fr; }
            .trash-table th:nth-child(3), .trash-table td:nth-child(3) { display:none; }
            .trash-row-actions { flex-direction:column; }
        }
    </style>

    <div class="trash-page">
        <div class="trash-head">
            <div>
                <h1><i class="fa-solid fa-trash-can-arrow-up text-primary me-2"></i>Thùng rác</h1>
                <p>Khôi phục nội dung đã lưu trữ hoặc xóa vĩnh viễn khi không còn cần thiết.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="trash-warning">
            <i class="fa-solid fa-circle-info mt-1"></i>
            <div>
                Nội dung được khôi phục về trạng thái an toàn: khóa học, bài học và bài tập về bản nháp; lượt gắn học liệu về trạng thái ẩn.
                Lịch học chỉ được khôi phục khi không trùng lớp, giáo viên hoặc phòng.
                @if ($isAdmin)
                    Xóa vĩnh viễn sẽ dọn cả dữ liệu phụ thuộc và không thể hoàn tác.
                @endif
            </div>
        </div>

        <nav class="trash-tabs" aria-label="Loại dữ liệu trong thùng rác">
            @foreach ($types as $type => $definition)
                <a href="{{ route('trash.index', ['type' => $type]) }}"
                    class="trash-tab {{ $activeType === $type ? 'active' : '' }}"
                    @if ($activeType === $type) aria-current="page" @endif>
                    <i class="fa-solid {{ $definition['icon'] }}"></i>
                    {{ $definition['label'] }}
                    <span class="trash-tab__count">{{ $counts[$type] ?? 0 }}</span>
                </a>
            @endforeach
        </nav>

        <div class="trash-card">
            <div class="trash-toolbar">
                <form class="trash-search" method="GET" action="{{ route('trash.index') }}">
                    <input type="hidden" name="type" value="{{ $activeType }}">
                    <input type="search" name="search" value="{{ $search }}" placeholder="Tìm trong {{ mb_strtolower($types[$activeType]['label']) }}...">
                    <button class="trash-btn trash-btn--muted" type="submit"><i class="fa-solid fa-search"></i>Tìm</button>
                </form>
                <div class="trash-actions">
                    <button class="trash-btn trash-btn--primary" id="bulkRestoreButton" type="button" disabled>
                        <i class="fa-solid fa-rotate-left"></i>Khôi phục (<span data-selected-count>0</span>)
                    </button>
                    @if ($isAdmin)
                        <button class="trash-btn trash-btn--danger" id="bulkDeleteButton" type="button" disabled>
                            <i class="fa-solid fa-trash"></i>Xóa vĩnh viễn
                        </button>
                    @endif
                </div>
            </div>

            @if ($items->isEmpty())
                <div class="trash-empty">
                    <i class="fa-regular fa-trash-can"></i>
                    Không có {{ mb_strtolower($types[$activeType]['label']) }} nào trong thùng rác.
                </div>
            @else
                <div class="trash-table-wrap">
                    <table class="trash-table">
                        <thead>
                            <tr>
                                <th style="width:42px"><input class="form-check-input" type="checkbox" id="selectAllTrash" aria-label="Chọn tất cả"></th>
                                <th>Nội dung</th>
                                <th style="width:160px">Lưu trữ lúc</th>
                                <th style="width:245px; text-align:right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td><input class="form-check-input trash-select" type="checkbox" value="{{ $item['key'] }}" aria-label="Chọn {{ $item['title'] }}"></td>
                                    <td>
                                        <div class="trash-item-title">{{ $item['title'] }}</div>
                                        <div class="trash-item-context">{{ $item['context'] }}</div>
                                    </td>
                                    <td>{{ $item['archived_at'] ?: 'Không rõ' }}</td>
                                    <td>
                                        <div class="trash-row-actions">
                                            <form method="POST" action="{{ route('trash.restore') }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="items[]" value="{{ $item['key'] }}">
                                                <button class="trash-btn trash-btn--muted" type="submit"><i class="fa-solid fa-rotate-left"></i>Khôi phục</button>
                                            </form>
                                            @if ($isAdmin)
                                                <button class="trash-btn trash-btn--danger js-permanent-delete" type="button" data-key="{{ $item['key'] }}" data-title="{{ $item['title'] }}">
                                                    <i class="fa-solid fa-trash"></i>Xóa
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="trash-footer">
                    <x-ui.pagination :paginator="$items" item-label="mục" />
                </div>
            @endif
        </div>
    </div>

    <form class="d-none" id="bulkRestoreForm" method="POST" action="{{ route('trash.restore') }}">
        @csrf
        @method('PATCH')
        <div data-items-container></div>
    </form>

    @if ($isAdmin)
        <div class="modal fade" id="permanentDeleteModal" tabindex="-1" aria-labelledby="permanentDeleteTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <form method="POST" action="{{ route('trash.permanent-delete') }}" id="permanentDeleteForm">
                        @csrf
                        @method('DELETE')
                        <div class="modal-header">
                            <h5 class="modal-title" id="permanentDeleteTitle"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>Xóa vĩnh viễn</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body">
                            <p class="trash-confirm-copy" id="permanentDeleteDescription"></p>
                            <label class="form-label fw-semibold" for="permanentDeleteConfirmation">
                                Nhập <span class="trash-confirm-code">XOA VINH VIEN</span> để xác nhận
                            </label>
                            <input class="form-control" id="permanentDeleteConfirmation" name="confirmation" autocomplete="off" required>
                            <div data-items-container></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="trash-btn trash-btn--muted" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="trash-btn trash-btn--danger">Xóa vĩnh viễn</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const checkboxes = [...document.querySelectorAll('.trash-select')];
            const selectAll = document.getElementById('selectAllTrash');
            const restoreButton = document.getElementById('bulkRestoreButton');
            const deleteButton = document.getElementById('bulkDeleteButton');
            const selectedCount = document.querySelector('[data-selected-count]');
            const selectedKeys = () => checkboxes.filter(item => item.checked).map(item => item.value);
            const fillItems = (form, keys) => {
                const container = form.querySelector('[data-items-container]');
                container.replaceChildren(...keys.map(key => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'items[]';
                    input.value = key;
                    return input;
                }));
            };
            const syncSelection = () => {
                const count = selectedKeys().length;
                if (selectedCount) selectedCount.textContent = count;
                if (restoreButton) restoreButton.disabled = count === 0;
                if (deleteButton) deleteButton.disabled = count === 0;
                if (selectAll) {
                    selectAll.checked = count > 0 && count === checkboxes.length;
                    selectAll.indeterminate = count > 0 && count < checkboxes.length;
                }
            };

            checkboxes.forEach(item => item.addEventListener('change', syncSelection));
            selectAll?.addEventListener('change', () => {
                checkboxes.forEach(item => item.checked = selectAll.checked);
                syncSelection();
            });
            restoreButton?.addEventListener('click', () => {
                const form = document.getElementById('bulkRestoreForm');
                fillItems(form, selectedKeys());
                form.submit();
            });

            const modalElement = document.getElementById('permanentDeleteModal');
            if (!modalElement || typeof bootstrap === 'undefined') return;
            const deleteModal = new bootstrap.Modal(modalElement);
            const deleteForm = document.getElementById('permanentDeleteForm');
            const description = document.getElementById('permanentDeleteDescription');
            const confirmation = document.getElementById('permanentDeleteConfirmation');
            const openDeleteModal = (keys, label) => {
                fillItems(deleteForm, keys);
                description.textContent = `${label} Dữ liệu phụ thuộc và file không còn được tham chiếu cũng sẽ bị xóa. Thao tác này không thể hoàn tác.`;
                confirmation.value = '';
                deleteModal.show();
            };

            deleteButton?.addEventListener('click', () => openDeleteModal(selectedKeys(), `Bạn sắp xóa vĩnh viễn ${selectedKeys().length} mục.`));
            document.querySelectorAll('.js-permanent-delete').forEach(button => {
                button.addEventListener('click', () => openDeleteModal([button.dataset.key], `Bạn sắp xóa vĩnh viễn “${button.dataset.title}”.`));
            });
        });
    </script>
@endpush
