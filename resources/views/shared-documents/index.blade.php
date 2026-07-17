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
                        <span class="document-kicker"><i class="fas fa-folder-open"></i> Tài liệu chung</span>
                        <h1>Kho tài liệu dành cho giáo viên</h1>
                        <p>Lưu trữ, sắp xếp và chia sẻ giáo án, biểu mẫu, Word, Excel, PowerPoint, PDF và HTML trên Cloudflare R2.</p>
                    </div>
                    <button class="document-upload-button" type="button" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                        <i class="fas fa-cloud-arrow-up"></i> Tải tài liệu lên
                    </button>
                </div>

                <div class="document-stats" aria-label="Thống kê tài liệu">
                    <a href="{{ route('shared-documents.index') }}" class="document-stat {{ !request('scope') ? 'active' : '' }}">
                        <span class="document-stat-icon blue"><i class="fas fa-layer-group"></i></span>
                        <span><strong>{{ $totalDocuments }}</strong><small>Tất cả có thể truy cập</small></span>
                    </a>
                    <a href="{{ route('shared-documents.index', ['scope' => 'mine']) }}" class="document-stat {{ request('scope') === 'mine' ? 'active' : '' }}">
                        <span class="document-stat-icon violet"><i class="fas fa-user-lock"></i></span>
                        <span><strong>{{ $myDocuments }}</strong><small>Tài liệu của tôi</small></span>
                    </a>
                    <a href="{{ route('shared-documents.index', ['scope' => 'shared']) }}" class="document-stat {{ request('scope') === 'shared' ? 'active' : '' }}">
                        <span class="document-stat-icon green"><i class="fas fa-user-group"></i></span>
                        <span><strong>{{ $sharedDocuments }}</strong><small>Được giáo viên chia sẻ</small></span>
                    </a>
                </div>
            </section>

            @if (session('success'))
                <div class="alert alert-success document-alert"><i class="fas fa-circle-check"></i>{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger document-alert">
                    <i class="fas fa-circle-exclamation"></i>
                    <div><strong>Chưa thể tải tài liệu.</strong><br>{{ $errors->first() }}</div>
                </div>
            @endif

            <section class="document-workspace">
                <div class="document-toolbar">
                    <form method="GET" action="{{ route('shared-documents.index') }}" class="document-filters">
                        @if (request('scope'))
                            <input type="hidden" name="scope" value="{{ request('scope') }}">
                        @endif
                        <label class="document-search">
                            <i class="fas fa-magnifying-glass"></i>
                            <input type="search" name="q" value="{{ request('q') }}" placeholder="Tìm theo tên hoặc mô tả…">
                        </label>
                        <select name="folder" aria-label="Lọc theo thư mục">
                            <option value="">Mọi thư mục</option>
                            @foreach ($folders as $folder)
                                <option value="{{ $folder }}" @selected(request('folder') === $folder)>{{ $folder }}</option>
                            @endforeach
                        </select>
                        <select name="extension" aria-label="Lọc theo định dạng">
                            <option value="">Mọi định dạng</option>
                            @foreach ($extensions as $extension)
                                <option value="{{ $extension }}" @selected(request('extension') === $extension)>{{ strtoupper($extension) }}</option>
                            @endforeach
                        </select>
                        <button type="submit"><i class="fas fa-filter"></i> Lọc</button>
                        @if (request()->hasAny(['q', 'folder', 'extension']))
                            <a href="{{ route('shared-documents.index', array_filter(['scope' => request('scope')])) }}" class="document-filter-clear">Xóa lọc</a>
                        @endif
                    </form>
                </div>

                @if ($folders->isNotEmpty())
                    <div class="document-folder-row">
                        <span><i class="fas fa-folder-tree"></i> Thư mục</span>
                        @foreach ($folders->take(8) as $folder)
                            <a href="{{ route('shared-documents.index', ['folder' => $folder]) }}" class="{{ request('folder') === $folder ? 'active' : '' }}">
                                <i class="fas fa-folder"></i>{{ $folder }}
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($documents->isEmpty())
                    <div class="document-empty">
                        <span><i class="fas fa-folder-open"></i></span>
                        <h2>Chưa có tài liệu phù hợp</h2>
                        <p>Tải tài liệu đầu tiên lên hoặc thay đổi bộ lọc hiện tại.</p>
                        <button type="button" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">Tải tài liệu lên</button>
                    </div>
                @else
                    <div class="document-grid">
                        @foreach ($documents as $document)
                            <article class="document-card">
                                <div class="document-card-top">
                                    <span class="document-file-icon type-{{ $document->extension ?: 'file' }}">
                                        <i class="fas {{ $document->iconClass() }}"></i>
                                    </span>
                                    <span class="document-extension">{{ strtoupper($document->extension ?: 'FILE') }}</span>
                                    @can('update', $document)
                                        <div class="dropdown ms-auto">
                                            <button class="document-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Thao tác tài liệu">
                                                <i class="fas fa-ellipsis"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#editDocumentModal{{ $document->id }}"><i class="fas fa-pen"></i> Chỉnh sửa</button></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('shared-documents.destroy', $document) }}" onsubmit="return confirm('Xóa tài liệu này khỏi R2?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="dropdown-item text-danger" type="submit"><i class="fas fa-trash"></i> Xóa tài liệu</button>
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
                                            <span><i class="fas fa-folder"></i>{{ $document->folder }}</span>
                                        @endif
                                        <span><i class="fas fa-hard-drive"></i>{{ $document->humanSize() }}</span>
                                    </div>
                                </div>

                                <div class="document-card-footer">
                                    <div class="document-owner">
                                        <span>{{ mb_strtoupper(mb_substr($document->owner?->name ?? 'A', 0, 1)) }}</span>
                                        <div><strong>{{ $document->owner?->name ?? 'Tài khoản đã xóa' }}</strong><small>{{ $document->created_at->format('d/m/Y H:i') }}</small></div>
                                    </div>
                                    <span class="document-visibility {{ $document->visibility }}">
                                        <i class="fas {{ $document->visibility === 'private' ? 'fa-lock' : 'fa-user-group' }}"></i>
                                        {{ $document->visibility === 'private' ? 'Riêng tư' : 'Giáo viên' }}
                                    </span>
                                </div>

                                <a class="document-download" href="{{ route('shared-documents.download', $document) }}">
                                    <i class="fas fa-download"></i> Tải xuống
                                </a>
                            </article>

                            @can('update', $document)
                                <div class="modal fade" id="editDocumentModal{{ $document->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <form class="modal-content document-modal" method="POST" action="{{ route('shared-documents.update', $document) }}">
                                            @csrf
                                            @method('PATCH')
                                            <div class="modal-header">
                                                <div><span>Chỉnh sửa tài liệu</span><h2>{{ $document->original_name }}</h2></div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                                            </div>
                                            <div class="modal-body">
                                                <label>Tên hiển thị<input type="text" name="title" value="{{ $document->title }}" maxlength="255" required></label>
                                                <label>Mô tả<textarea name="description" rows="3" maxlength="2000">{{ $document->description }}</textarea></label>
                                                <label>Thư mục<input type="text" name="folder" value="{{ $document->folder }}" maxlength="100" list="documentFolders"></label>
                                                <label>Phạm vi chia sẻ
                                                    <select name="visibility" required>
                                                        <option value="teachers" @selected($document->visibility === 'teachers')>Tất cả giáo viên</option>
                                                        <option value="private" @selected($document->visibility === 'private')>Chỉ mình tôi</option>
                                                    </select>
                                                </label>
                                            </div>
                                            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary">Lưu thay đổi</button></div>
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
        @foreach ($folders as $folder)<option value="{{ $folder }}">@endforeach
    </datalist>

    <div class="modal fade" id="uploadDocumentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered document-upload-dialog">
            <form class="modal-content document-modal document-upload-modal" method="POST" action="{{ route('shared-documents.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <div><span>Cloudflare R2</span><h2>Tải tài liệu lên kho chung</h2></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <label class="document-dropzone">
                        <i class="fas fa-cloud-arrow-up"></i>
                        <strong>Chọn tối đa 10 tài liệu</strong>
                        <span>Word, Excel, PowerPoint, PDF, HTML, TXT, CSV, ZIP hoặc hình ảnh · tối đa 20 MB/file</span>
                        <input type="file" name="files[]" multiple required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.html,.htm,.txt,.csv,.zip,.jpg,.jpeg,.png,.webp">
                    </label>
                    <div class="document-form-grid">
                        <label>Thư mục<input type="text" name="folder" value="{{ old('folder') }}" maxlength="100" list="documentFolders" placeholder="Ví dụ: Giáo án"></label>
                        <label>Phạm vi chia sẻ
                            <select name="visibility" required>
                                <option value="teachers" @selected(old('visibility', 'teachers') === 'teachers')>Tất cả giáo viên</option>
                                <option value="private" @selected(old('visibility') === 'private')>Chỉ mình tôi</option>
                            </select>
                        </label>
                    </div>
                    <label>Mô tả chung<textarea name="description" rows="3" maxlength="2000" placeholder="Nội dung hoặc mục đích sử dụng của tài liệu">{{ old('description') }}</textarea></label>
                    <p class="document-security-note"><i class="fas fa-shield-halved"></i> File được lưu trong bucket riêng tư. Người dùng phải đăng nhập và có quyền mới tải xuống được.</p>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary"><i class="fas fa-cloud-arrow-up me-1"></i>Tải lên R2</button></div>
            </form>
        </div>
    </div>
@endsection

@if ($errors->any())
    @push('scripts')
        <script>document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('uploadDocumentModal')).show());</script>
    @endpush
@endif
