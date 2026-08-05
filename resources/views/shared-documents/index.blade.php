@extends('layouts.app')

@section('title', 'Tài liệu dùng chung')

@push('styles')
    @vite('resources/css/pages/shared-documents.css')
@endpush

@section('content')
    @php
        $hasFilters = request()->filled('q')
            || request()->filled('folder')
            || request()->filled('extension')
            || $sort !== 'newest';
        $scope = request('scope');
    @endphp

    <div class="lms-page shared-documents-page">
        <x-ui.page-header title="Tài liệu dùng chung">
            <x-slot:meta>
                <span><i class="fa-solid fa-folder-open" aria-hidden="true"></i>Kho giáo án và biểu mẫu dành cho giáo viên</span>
                <span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>Lưu trữ riêng tư trên Cloudflare R2</span>
            </x-slot:meta>
            <x-slot:actions>
                <x-ui.button icon="fa-cloud-arrow-up" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                    Tải tài liệu lên
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if ($errors->any())
            <div class="document-alert" role="alert">
                <span aria-hidden="true"><i class="fa-solid fa-circle-exclamation"></i></span>
                <div><strong>Chưa thể tải tài liệu</strong><p>{{ $errors->first() }}</p></div>
            </div>
        @endif

        <nav class="document-summary" aria-label="Phạm vi tài liệu">
            <a href="{{ route('shared-documents.index', request()->except(['page', 'scope'])) }}"
                class="document-summary-item {{ ! $scope ? 'is-active' : '' }}" @if (! $scope) aria-current="page" @endif>
                <span class="document-summary-icon tone-blue"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></span>
                <span><strong>{{ $totalDocuments }}</strong><small>Tất cả có thể truy cập</small></span>
            </a>
            <a href="{{ route('shared-documents.index', array_merge(request()->except(['page', 'scope']), ['scope' => 'mine'])) }}"
                class="document-summary-item {{ $scope === 'mine' ? 'is-active' : '' }}" @if ($scope === 'mine') aria-current="page" @endif>
                <span class="document-summary-icon tone-violet"><i class="fa-solid fa-user-lock" aria-hidden="true"></i></span>
                <span><strong>{{ $myDocuments }}</strong><small>Tài liệu của tôi</small></span>
            </a>
            <a href="{{ route('shared-documents.index', array_merge(request()->except(['page', 'scope']), ['scope' => 'shared'])) }}"
                class="document-summary-item {{ $scope === 'shared' ? 'is-active' : '' }}" @if ($scope === 'shared') aria-current="page" @endif>
                <span class="document-summary-icon tone-green"><i class="fa-solid fa-user-group" aria-hidden="true"></i></span>
                <span><strong>{{ $sharedDocuments }}</strong><small>Được giáo viên chia sẻ</small></span>
            </a>
            <div class="document-summary-item is-static">
                <span class="document-summary-icon tone-amber"><i class="fa-solid fa-hard-drive" aria-hidden="true"></i></span>
                <span><strong>{{ $totalStorage }}</strong><small>Dung lượng có thể truy cập</small></span>
            </div>
        </nav>

        <section class="document-workspace" aria-labelledby="document-list-heading">
            <div class="document-mobile-toolbar">
                <button class="document-filter-toggle {{ $hasFilters ? 'has-filters' : '' }}" type="button"
                    aria-controls="documentFilters" aria-expanded="{{ $hasFilters ? 'true' : 'false' }}">
                    <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                    <span>Bộ lọc</span>
                    @if ($hasFilters)<em>Đang lọc</em>@endif
                    <i class="fa-solid fa-chevron-down document-filter-toggle-icon" aria-hidden="true"></i>
                </button>
            </div>

            <form method="GET" action="{{ route('shared-documents.index') }}"
                class="document-filter-panel {{ $hasFilters ? 'is-open' : '' }}" id="documentFilters"
                aria-label="Bộ lọc tài liệu">
                @if ($scope)
                    <input type="hidden" name="scope" value="{{ $scope }}">
                @endif

                <div class="document-search-field">
                    <label for="document-search">Tìm kiếm</label>
                    <div class="document-input-with-icon">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input id="document-search" type="search" name="q" class="form-control"
                            value="{{ request('q') }}" placeholder="Tên, tên file hoặc mô tả">
                    </div>
                </div>
                <div class="document-filter-field">
                    <label for="document-folder">Thư mục</label>
                    <select id="document-folder" name="folder" class="form-select">
                        <option value="">Mọi thư mục</option>
                        @foreach ($folders as $folder)
                            <option value="{{ $folder }}" @selected(request('folder') === $folder)>{{ $folder }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="document-filter-field">
                    <label for="document-extension">Định dạng</label>
                    <select id="document-extension" name="extension" class="form-select">
                        <option value="">Mọi định dạng</option>
                        @foreach ($extensions as $extension)
                            <option value="{{ $extension }}" @selected(request('extension') === $extension)>{{ strtoupper($extension) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="document-filter-field">
                    <label for="document-sort">Sắp xếp</label>
                    <select id="document-sort" name="sort" class="form-select">
                        <option value="newest" @selected($sort === 'newest')>Đăng gần đây</option>
                        <option value="oldest" @selected($sort === 'oldest')>Đăng lâu nhất</option>
                        <option value="name" @selected($sort === 'name')>Tên A–Z</option>
                        <option value="size" @selected($sort === 'size')>Dung lượng lớn nhất</option>
                    </select>
                </div>
                <div class="document-filter-actions">
                    <x-ui.button type="submit" icon="fa-filter">Áp dụng</x-ui.button>
                    @if ($hasFilters)
                        <x-ui.button :href="route('shared-documents.index', array_filter(['scope' => $scope]))"
                            tone="outline" icon="fa-rotate-left">Đặt lại</x-ui.button>
                    @endif
                </div>
            </form>

            @if ($folders->isNotEmpty())
                <nav class="document-folder-chips" aria-label="Truy cập nhanh theo thư mục">
                    <a href="{{ route('shared-documents.index', request()->except(['page', 'folder'])) }}"
                        class="{{ request('folder') ? '' : 'is-active' }}">
                        <i class="fa-solid fa-border-all" aria-hidden="true"></i>Tất cả thư mục
                    </a>
                    @foreach ($folders as $folder)
                        <a href="{{ route('shared-documents.index', array_merge(request()->except(['page', 'folder']), ['folder' => $folder])) }}"
                            class="{{ request('folder') === $folder ? 'is-active' : '' }}">
                            <i class="fa-solid fa-folder" aria-hidden="true"></i>{{ $folder }}
                        </a>
                    @endforeach
                </nav>
            @endif

            <header class="document-results-header">
                <div>
                    <h2 id="document-list-heading">Danh sách tài liệu</h2>
                    <p>{{ $hasFilters || $scope ? 'Kết quả theo phạm vi và bộ lọc hiện tại' : 'Các tài liệu bạn có quyền xem và tải xuống' }}</p>
                </div>
                <div class="document-results-actions">
                    <span class="document-result-count">{{ $documents->total() }} tài liệu</span>
                    <div class="document-view-toggle" role="group" aria-label="Chế độ hiển thị tài liệu">
                        <button type="button" data-document-view="grid" aria-pressed="true" title="Xem dạng lưới"
                            aria-label="Xem dạng lưới"><i class="fa-solid fa-grip" aria-hidden="true"></i></button>
                        <button type="button" data-document-view="list" aria-pressed="false" title="Xem dạng danh sách"
                            aria-label="Xem dạng danh sách"><i class="fa-solid fa-list" aria-hidden="true"></i></button>
                    </div>
                </div>
            </header>

            @if ($documents->isEmpty())
                <div class="document-empty-panel">
                    <x-ui.empty-state
                        :title="$hasFilters || $scope ? 'Không tìm thấy tài liệu phù hợp' : 'Chưa có tài liệu nào'"
                        :description="$hasFilters || $scope
                            ? 'Hãy thay đổi phạm vi, từ khóa hoặc bộ lọc để xem thêm kết quả.'
                            : 'Tải tài liệu đầu tiên lên để bắt đầu xây dựng kho dùng chung cho giáo viên.'"
                        icon="fa-folder-open">
                        @if ($hasFilters || $scope)
                            <x-ui.button :href="route('shared-documents.index')" tone="outline" size="sm" icon="fa-rotate-left">Xóa bộ lọc</x-ui.button>
                        @else
                            <x-ui.button size="sm" icon="fa-cloud-arrow-up" data-bs-toggle="modal"
                                data-bs-target="#uploadDocumentModal">Tải tài liệu lên</x-ui.button>
                        @endif
                    </x-ui.empty-state>
                </div>
            @else
                <div class="document-grid" id="documentCollection" data-view="grid">
                    @foreach ($documents as $document)
                        <article class="document-card">
                            <div class="document-card-leading">
                                <span class="document-file-icon type-{{ $document->extension ?: 'file' }}" aria-hidden="true">
                                    <i class="fa-solid {{ $document->iconClass() }}"></i>
                                </span>
                                <div class="document-card-badges">
                                    <span class="document-extension">{{ strtoupper($document->extension ?: 'FILE') }}</span>
                                    @if ($document->created_at->greaterThanOrEqualTo(now()->subDays(3)))
                                        <span class="document-new-badge"><i class="fa-solid fa-sparkles" aria-hidden="true"></i>Mới</span>
                                    @endif
                                </div>
                                @can('update', $document)
                                    <div class="dropdown document-card-menu">
                                        <button class="document-icon-button" type="button" data-bs-toggle="dropdown"
                                            aria-expanded="false" aria-label="Mở thao tác cho tài liệu {{ $document->title }}">
                                            <i class="fa-solid fa-ellipsis" aria-hidden="true"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end document-dropdown">
                                            <li>
                                                <button class="dropdown-item document-edit-button" type="button"
                                                    data-bs-toggle="modal" data-bs-target="#editDocumentModal"
                                                    data-update-url="{{ route('shared-documents.update', $document) }}"
                                                    data-document-title="{{ $document->title }}"
                                                    data-document-name="{{ $document->original_name }}"
                                                    data-document-description="{{ $document->description }}"
                                                    data-document-folder="{{ $document->folder }}"
                                                    data-document-visibility="{{ $document->visibility }}">
                                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>Chỉnh sửa
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST" action="{{ route('shared-documents.destroy', $document) }}"
                                                    onsubmit="return confirm('Xóa tài liệu này khỏi kho lưu trữ? Hành động này không thể hoàn tác.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item text-danger" type="submit">
                                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>Xóa tài liệu
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                @endcan
                            </div>

                            <div class="document-card-main">
                                <h3 title="{{ $document->title }}">{{ $document->title }}</h3>
                                <p>{{ $document->description ?: 'Tài liệu chưa có mô tả.' }}</p>
                                <dl class="document-card-details" aria-label="Thông tin file">
                                    @if ($document->folder)
                                        <div><dt><i class="fa-solid fa-folder" aria-hidden="true"></i>Thư mục</dt><dd>{{ $document->folder }}</dd></div>
                                    @endif
                                    <div><dt><i class="fa-solid fa-hard-drive" aria-hidden="true"></i>Dung lượng</dt><dd>{{ $document->humanSize() }}</dd></div>
                                </dl>
                            </div>

                            <div class="document-card-owner">
                                <span class="document-owner-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($document->owner?->name ?? 'A', 0, 1)) }}</span>
                                <span><small>Người đăng</small><strong>{{ $document->owner?->name ?? 'Tài khoản đã xóa' }}</strong></span>
                                <span class="document-visibility {{ $document->visibility }}">
                                    <i class="fa-solid {{ $document->visibility === 'private' ? 'fa-lock' : 'fa-user-group' }}" aria-hidden="true"></i>
                                    {{ $document->visibility === 'private' ? 'Riêng tư' : 'Giáo viên' }}
                                </span>
                            </div>

                            <div class="document-card-footer">
                                <time datetime="{{ $document->created_at->toIso8601String() }}">
                                    <i class="fa-regular fa-clock" aria-hidden="true"></i>{{ $document->created_at->diffForHumans() }}
                                </time>
                                <div class="document-card-actions">
                                    @if ($document->previewType())
                                        <button class="document-preview-button" type="button" data-bs-toggle="modal"
                                            data-bs-target="#previewDocumentModal"
                                            data-preview-url="{{ route('shared-documents.preview', $document) }}"
                                            data-preview-type="{{ $document->previewType() }}"
                                            data-preview-title="{{ $document->title }}"
                                            data-download-url="{{ route('shared-documents.download', $document) }}">
                                            <i class="fa-solid fa-eye" aria-hidden="true"></i>Xem trước
                                        </button>
                                    @endif
                                    <a class="document-download" href="{{ route('shared-documents.download', $document) }}"
                                        data-no-page-transition data-file-download>
                                        <i class="fa-solid fa-download" aria-hidden="true"></i>Tải xuống
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="document-pagination-panel">
                    <x-ui.pagination :paginator="$documents" item-label="tài liệu" />
                </div>
            @endif
        </section>
    </div>

    <datalist id="documentFolders">
        @foreach ($folders as $folder)<option value="{{ $folder }}">@endforeach
    </datalist>

    <div class="modal fade document-modal-shell" id="editDocumentModal" tabindex="-1"
        aria-labelledby="editDocumentModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content document-modal" id="editDocumentForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <div class="document-modal-heading">
                        <span aria-hidden="true"><i class="fa-solid fa-pen-to-square"></i></span>
                        <div><h2 id="editDocumentModalTitle">Chỉnh sửa tài liệu</h2><p id="editDocumentFileName">Tài liệu</p></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body document-form-stack">
                    <div class="document-form-field">
                        <label for="editDocumentTitle">Tên hiển thị</label>
                        <input id="editDocumentTitle" class="form-control" type="text" name="title" maxlength="255" required>
                    </div>
                    <div class="document-form-field">
                        <label for="editDocumentDescription">Mô tả</label>
                        <textarea id="editDocumentDescription" class="form-control" name="description" rows="3" maxlength="2000"></textarea>
                    </div>
                    <div class="document-form-grid">
                        <div class="document-form-field">
                            <label for="editDocumentFolder">Thư mục</label>
                            <input id="editDocumentFolder" class="form-control" type="text" name="folder" maxlength="100" list="documentFolders">
                        </div>
                        <div class="document-form-field">
                            <label for="editDocumentVisibility">Phạm vi chia sẻ</label>
                            <select id="editDocumentVisibility" class="form-select" name="visibility" required>
                                <option value="teachers">Tất cả giáo viên</option>
                                <option value="private">Chỉ mình tôi</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <x-ui.button tone="outline" data-bs-dismiss="modal">Hủy</x-ui.button>
                    <x-ui.button type="submit" icon="fa-floppy-disk">Lưu thay đổi</x-ui.button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade document-modal-shell" id="previewDocumentModal" tabindex="-1"
        aria-labelledby="previewDocumentTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered document-preview-dialog">
            <div class="modal-content document-modal document-preview-modal">
                <div class="modal-header">
                    <div class="document-modal-heading">
                        <span aria-hidden="true"><i class="fa-solid fa-eye"></i></span>
                        <div><h2 id="previewDocumentTitle">Xem trước tài liệu</h2><p>Chỉ PDF và hình ảnh được hỗ trợ xem trực tiếp</p></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body document-preview-body">
                    <div class="document-preview-loading" id="documentPreviewLoading" role="status" aria-live="polite">
                        <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i><span>Đang mở tài liệu...</span>
                    </div>
                    <img class="document-preview-media document-preview-image" id="documentPreviewImage" alt="" hidden>
                    <iframe class="document-preview-media document-preview-frame" id="documentPreviewFrame"
                        title="Xem trước tài liệu PDF" loading="lazy" hidden></iframe>
                </div>
                <div class="modal-footer">
                    <x-ui.button id="documentPreviewOpen" href="#" tone="outline" icon="fa-arrow-up-right-from-square"
                        target="_blank" rel="noopener" data-no-page-transition>Mở tab mới</x-ui.button>
                    <x-ui.button id="documentPreviewDownload" href="#" icon="fa-download"
                        data-no-page-transition data-file-download>Tải xuống</x-ui.button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade document-modal-shell" id="uploadDocumentModal" tabindex="-1"
        aria-labelledby="uploadDocumentModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <form class="modal-content document-modal document-upload-modal" method="POST"
                action="{{ route('shared-documents.store') }}" enctype="multipart/form-data" id="uploadDocumentForm">
                @csrf
                <div class="modal-header">
                    <div class="document-modal-heading">
                        <span aria-hidden="true"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                        <div><h2 id="uploadDocumentModalTitle">Tải tài liệu lên</h2><p>Tối đa 10 file, mỗi file không quá 20 MB</p></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body document-form-stack">
                    <div class="document-dropzone" id="documentDropzone" role="button" tabindex="0"
                        aria-controls="documentFilesInput" aria-label="Kéo thả hoặc chọn tài liệu để tải lên">
                        <span class="document-dropzone-icon" aria-hidden="true"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                        <strong>Kéo thả tài liệu vào đây</strong>
                        <p>PDF, Word, Excel, PowerPoint, HTML, TXT, CSV, ZIP hoặc hình ảnh</p>
                        <button type="button" class="document-file-picker" id="documentFilePicker">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>Chọn tài liệu
                        </button>
                        <input type="file" name="files[]" id="documentFilesInput" multiple required
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.html,.htm,.txt,.csv,.zip,.jpg,.jpeg,.png,.webp" hidden>
                        <div class="document-file-previews" id="documentFilePreviews"></div>
                        <span class="document-dropzone-status" id="documentDropzoneStatus" aria-live="polite">Chưa chọn tài liệu</span>
                    </div>
                    <div class="document-form-grid">
                        <div class="document-form-field">
                            <label for="uploadDocumentFolder">Thư mục</label>
                            <input id="uploadDocumentFolder" class="form-control" type="text" name="folder"
                                value="{{ old('folder') }}" maxlength="100" list="documentFolders" placeholder="Ví dụ: Giáo án">
                        </div>
                        <div class="document-form-field">
                            <label for="uploadDocumentVisibility">Phạm vi chia sẻ</label>
                            <select id="uploadDocumentVisibility" class="form-select" name="visibility" required>
                                <option value="teachers" @selected(old('visibility', 'teachers') === 'teachers')>Tất cả giáo viên</option>
                                <option value="private" @selected(old('visibility') === 'private')>Chỉ mình tôi</option>
                            </select>
                        </div>
                    </div>
                    <div class="document-form-field">
                        <label for="uploadDocumentDescription">Mô tả chung</label>
                        <textarea id="uploadDocumentDescription" class="form-control" name="description" rows="3"
                            maxlength="2000" placeholder="Nội dung hoặc mục đích sử dụng của tài liệu">{{ old('description') }}</textarea>
                    </div>
                    <p class="document-security-note"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                        File được lưu trong vùng riêng tư. Người dùng phải đăng nhập và có quyền mới có thể xem hoặc tải xuống.
                    </p>
                </div>
                <div class="modal-footer">
                    <x-ui.button tone="outline" data-bs-dismiss="modal">Hủy</x-ui.button>
                    <x-ui.button type="submit" icon="fa-cloud-arrow-up" id="uploadDocumentSubmit">Tải lên</x-ui.button>
                </div>
            </form>
        </div>
    </div>
@endsection

@if ($errors->any())
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('uploadDocumentModal')).show();
            });
        </script>
    @endpush
@endif

@push('scripts')
    @vite('resources/js/pages/shared-documents.js')
@endpush
