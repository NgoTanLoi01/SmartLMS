@extends('layouts.app')

@section('title', 'Tài liệu chung')

@push('styles')
    @vite('resources/css/pages/shared-documents.css')
@endpush

@section('content')
    <div class="shared-documents-page">
        <div class="shared-documents-shell">
            <section class="document-hero">
                <div class="document-hero-top">
                    <div>
                        <span class="document-kicker"><i class="fa-solid fa-folder-open"></i> Tài liệu chung</span>
                        <h1>Kho tài liệu dành cho giáo viên</h1>
                        <p>Lưu trữ, sắp xếp và chia sẻ giáo án, biểu mẫu, Word, Excel, PowerPoint, PDF và HTML trên
                            Cloudflare R2.</p>
                    </div>
                    <button class="document-upload-button" type="button" data-bs-toggle="modal"
                        data-bs-target="#uploadDocumentModal">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Tải tài liệu lên
                    </button>
                </div>

                <div class="document-stats" aria-label="Thống kê tài liệu">
                    <a href="{{ route('shared-documents.index') }}"
                        class="document-stat {{ !request('scope') ? 'active' : '' }}">
                        <span class="document-stat-icon blue"><i class="fa-solid fa-layer-group"></i></span>
                        <span><strong data-document-count="{{ $totalDocuments }}">{{ $totalDocuments }}</strong><small>TẤT CẢ CÓ THỂ TRUY CẬP</small></span>
                    </a>
                    <a href="{{ route('shared-documents.index', ['scope' => 'mine']) }}"
                        class="document-stat {{ request('scope') === 'mine' ? 'active' : '' }}">
                        <span class="document-stat-icon violet"><i class="fa-solid fa-user-lock"></i></span>
                        <span><strong data-document-count="{{ $myDocuments }}">{{ $myDocuments }}</strong><small>TÀI LIỆU CỦA TÔI</small></span>
                    </a>
                    <a href="{{ route('shared-documents.index', ['scope' => 'shared']) }}"
                        class="document-stat {{ request('scope') === 'shared' ? 'active' : '' }}">
                        <span class="document-stat-icon green"><i class="fa-solid fa-user-group"></i></span>
                        <span><strong data-document-count="{{ $sharedDocuments }}">{{ $sharedDocuments }}</strong><small>ĐƯỢC GIÁO VIÊN CHIA SẺ</small></span>
                    </a>
                </div>
            </section>

            @if ($errors->any())
                <div class="alert alert-danger document-alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div><strong>Chưa thể tải tài liệu.</strong><br>{{ $errors->first() }}</div>
                </div>
            @endif

            <section class="document-workspace">
                <div class="document-toolbar">
                    <div class="document-toolbar-main">
                        <form method="GET" action="{{ route('shared-documents.index') }}" class="document-filters">
                            @if (request('scope'))
                                <input type="hidden" name="scope" value="{{ request('scope') }}">
                            @endif
                            <label class="document-search">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="search" name="q" value="{{ request('q') }}"
                                    placeholder="Tìm theo tên hoặc mô tả…" aria-label="Tìm kiếm tài liệu">
                            </label>
                            <select name="folder" aria-label="Lọc theo thư mục">
                                <option value="">Mọi thư mục</option>
                                @foreach ($folders as $folder)
                                    <option value="{{ $folder }}" @selected(request('folder') === $folder)>{{ $folder }}
                                    </option>
                                @endforeach
                            </select>
                            <select name="extension" aria-label="Lọc theo định dạng">
                                <option value="">Mọi định dạng</option>
                                @foreach ($extensions as $extension)
                                    <option value="{{ $extension }}" @selected(request('extension') === $extension)>
                                        {{ strtoupper($extension) }}</option>
                                @endforeach
                            </select>
                            <button type="submit"><i class="fa-solid fa-filter"></i> Lọc</button>
                            @if (request()->hasAny(['q', 'folder', 'extension']))
                                <a href="{{ route('shared-documents.index', array_filter(['scope' => request('scope')])) }}"
                                    class="document-filter-clear">Xóa lọc</a>
                            @endif
                        </form>

                        <div class="document-view-toggle" role="group" aria-label="Chế độ hiển thị tài liệu">
                            <button type="button" class="active" data-document-view="grid" aria-pressed="true"
                                title="Xem dạng lưới"><i class="fa-solid fa-grip"></i><span>Lưới</span></button>
                            <button type="button" data-document-view="list" aria-pressed="false"
                                title="Xem dạng danh sách"><i class="fa-solid fa-list"></i><span>Danh sách</span></button>
                        </div>
                    </div>

                    @if ($folders->isNotEmpty())
                        <div class="document-folder-row">
                            <span><i class="fa-solid fa-folder-tree"></i> Thư mục</span>
                            @foreach ($folders->take(8) as $folder)
                                <a href="{{ route('shared-documents.index', ['folder' => $folder]) }}"
                                    class="{{ request('folder') === $folder ? 'active' : '' }}">
                                    <i class="fa-solid fa-folder"></i>{{ $folder }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($documents->isEmpty())
                    <div class="document-empty">
                        <span><i class="fa-solid fa-folder-open"></i></span>
                        <h2>Chưa có tài liệu phù hợp</h2>
                        <p>
                            @if (request()->hasAny(['q', 'folder', 'extension']))
                                Không có tài liệu nào khớp với bộ lọc hiện tại. Thử xóa lọc hoặc tải tài liệu mới lên.
                            @else
                                Đây là nơi lưu trữ chung của giáo viên. Hãy là người đầu tiên tải tài liệu lên.
                            @endif
                        </p>
                        <button type="button" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">Tải tài liệu
                            lên</button>
                    </div>
                @else
                    <div class="document-grid" id="documentCollection" data-view="grid">
                        @foreach ($documents as $document)
                            <article class="document-card">
                                <div class="document-card-top">
                                    <span class="document-file-icon type-{{ $document->extension ?: 'file' }}">
                                        <i class="fa-solid {{ $document->iconClass() }}"></i>
                                    </span>
                                    @if ($document->created_at->greaterThanOrEqualTo(now()->subDays(3)))
                                        <span class="document-new-badge" title="Đăng trong 3 ngày gần đây"><i
                                                class="fa-solid fa-sparkles"></i> Mới</span>
                                    @endif
                                    @can('update', $document)
                                        <div class="dropdown ms-auto">
                                            <button class="document-menu" type="button" data-bs-toggle="dropdown"
                                                aria-expanded="false" aria-label="Thao tác tài liệu">
                                                <i class="fa-solid fa-ellipsis"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><button class="dropdown-item" type="button" data-bs-toggle="modal"
                                                        data-bs-target="#editDocumentModal{{ $document->id }}"><i
                                                            class="fa-solid fa-pen"></i> Chỉnh sửa</button></li>
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    <form method="POST"
                                                        action="{{ route('shared-documents.destroy', $document) }}"
                                                        onsubmit="return confirm('Xóa tài liệu này khỏi R2? Hành động này không thể hoàn tác.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="dropdown-item text-danger" type="submit"><i
                                                                class="fa-solid fa-trash"></i> Xóa tài liệu</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    @endcan
                                </div>

                                <div class="document-card-body">
                                    <h2 title="{{ $document->title }}">{{ $document->title }}</h2>
                                    <p>{{ $document->description ?: 'Không có mô tả.' }}</p>
                                    <div class="document-meta">
                                        @if ($document->folder)
                                            <span><i class="fa-solid fa-folder"></i>{{ $document->folder }}</span>
                                        @endif
                                        <span><i class="fa-solid fa-hard-drive"></i>{{ $document->humanSize() }}</span>
                                    </div>
                                </div>

                                <div class="document-card-footer">
                                    <div class="document-owner">
                                        <span>{{ mb_strtoupper(mb_substr($document->owner?->name ?? 'A', 0, 1)) }}</span>
                                        <div>
                                            <strong>{{ $document->owner?->name ?? 'Tài khoản đã xóa' }}</strong><small>{{ $document->created_at->format('d/m/Y H:i') }}</small>
                                        </div>
                                    </div>
                                    <span class="document-visibility {{ $document->visibility }}">
                                        <i
                                            class="fa-solid {{ $document->visibility === 'private' ? 'fa-lock' : 'fa-user-group' }}"></i>
                                        {{ $document->visibility === 'private' ? 'Riêng tư' : 'Giáo viên' }}
                                    </span>
                                </div>

                                <a class="document-download" href="{{ route('shared-documents.download', $document) }}"
                                    data-no-page-transition data-file-download>
                                    <i class="fa-solid fa-download"></i> Tải xuống
                                </a>
                            </article>

                            @can('update', $document)
                                <div class="modal fade" id="editDocumentModal{{ $document->id }}" tabindex="-1"
                                    aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <form class="modal-content document-modal" method="POST"
                                            action="{{ route('shared-documents.update', $document) }}">
                                            @csrf
                                            @method('PATCH')
                                            <div class="modal-header">
                                                <div><span>Chỉnh sửa tài liệu</span>
                                                    <h2>{{ $document->original_name }}</h2>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Đóng"></button>
                                            </div>
                                            <div class="modal-body">
                                                <label>Tên hiển thị<input type="text" name="title"
                                                        value="{{ $document->title }}" maxlength="255" required></label>
                                                <label>Mô tả
                                                    <textarea name="description" rows="3" maxlength="2000">{{ $document->description }}</textarea>
                                                </label>
                                                <label>Thư mục<input type="text" name="folder"
                                                        value="{{ $document->folder }}" maxlength="100"
                                                        list="documentFolders"></label>
                                                <label>Phạm vi chia sẻ
                                                    <select name="visibility" required>
                                                        <option value="teachers" @selected($document->visibility === 'teachers')>Tất cả giáo viên
                                                        </option>
                                                        <option value="private" @selected($document->visibility === 'private')>Chỉ mình tôi
                                                        </option>
                                                    </select>
                                                </label>
                                            </div>
                                            <div class="modal-footer"><button type="button" class="btn btn-light"
                                                    data-bs-dismiss="modal">Hủy</button><button type="submit"
                                                    class="btn btn-primary">Lưu thay đổi</button></div>
                                        </form>
                                    </div>
                                </div>
                            @endcan
                        @endforeach
                    </div>

                    <div class="document-pagination">{{ $documents->links() }}</div>
                @endif
            </section>
        </div>
    </div>

    <datalist id="documentFolders">
        @foreach ($folders as $folder)
            <option value="{{ $folder }}">
        @endforeach
    </datalist>

    <div class="modal fade" id="uploadDocumentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered document-upload-dialog">
            <form class="modal-content document-modal document-upload-modal" method="POST"
                action="{{ route('shared-documents.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <div><span>Cloudflare R2</span>
                        <h2>Tải tài liệu lên kho chung</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="document-dropzone" id="documentDropzone" role="button" tabindex="0"
                        aria-label="Kéo thả hoặc chọn tài liệu để tải lên">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <strong>Kéo thả tài liệu vào đây</strong>
                        <span>Word, Excel, PowerPoint, PDF, HTML, TXT, CSV, ZIP hoặc hình ảnh · tối đa 20 MB/file</span>
                        <button type="button" class="document-file-picker" id="documentFilePicker"><i
                                class="fa-solid fa-plus"></i> Chọn tài liệu</button>
                        <input type="file" name="files[]" id="documentFilesInput" multiple required
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.html,.htm,.txt,.csv,.zip,.jpg,.jpeg,.png,.webp"
                            hidden>
                        <div class="document-file-previews" id="documentFilePreviews" aria-live="polite"></div>
                        <span class="document-dropzone-status" id="documentDropzoneStatus">Tối đa 10 file</span>
                    </div>
                    <div class="document-form-grid">
                        <label>Thư mục<input type="text" name="folder" value="{{ old('folder') }}" maxlength="100"
                                list="documentFolders" placeholder="Ví dụ: Giáo án"></label>
                        <label>Phạm vi chia sẻ
                            <select name="visibility" required>
                                <option value="teachers" @selected(old('visibility', 'teachers') === 'teachers')>Tất cả giáo viên</option>
                                <option value="private" @selected(old('visibility') === 'private')>Chỉ mình tôi</option>
                            </select>
                        </label>
                    </div>
                    <label>Mô tả chung
                        <textarea name="description" rows="3" maxlength="2000"
                            placeholder="Nội dung hoặc mục đích sử dụng của tài liệu">{{ old('description') }}</textarea>
                    </label>
                    <p class="document-security-note"><i class="fa-solid fa-shield-halved"></i> File được lưu trong bucket
                        riêng tư. Người dùng phải đăng nhập và có quyền mới tải xuống được.</p>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light"
                        data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary"><i
                            class="fa-solid fa-cloud-arrow-up me-1"></i>Tải lên R2</button></div>
            </form>
        </div>
    </div>
@endsection

@if ($errors->any())
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById(
                'uploadDocumentModal')).show());
        </script>
    @endpush
@endif

@push('scripts')
    @vite('resources/js/pages/shared-documents.js')
@endpush
