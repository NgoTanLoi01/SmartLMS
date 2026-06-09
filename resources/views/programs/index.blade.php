@extends('layouts.app')

@section('title', 'Chương trình học')

@section('content')
    <style>
        .program-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .program-title {
            margin: 0 0 4px;
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
        }

        .program-subtitle {
            margin: 0;
            color: #64748b;
            font-size: 13.5px;
        }

        .program-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .program-code {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
        }

        .program-desc {
            max-width: 460px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
        }

        .program-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        @media (max-width: 767.98px) {
            .program-header .btn {
                width: 100%;
            }

            .program-actions {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="program-header">
        <div>
            <h1 class="program-title">Chương trình học</h1>
            <p class="program-subtitle">Gom nhóm các khóa học theo chương trình/môn học chuẩn trước khi tách nội dung mẫu.</p>
        </div>
        <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal"
            data-bs-target="#createProgramModal">
            <i class="fas fa-plus me-2"></i>Thêm chương trình
        </button>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 rounded-3">
            <i class="fas fa-exclamation-circle me-2"></i>{{ $errors->first() }}
        </div>
    @endif

    <div class="program-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="px-4 py-3 border-0">Chương trình</th>
                        <th class="px-4 py-3 border-0">Mã</th>
                        <th class="px-4 py-3 border-0">Khóa học</th>
                        <th class="px-4 py-3 border-0">Trạng thái</th>
                        @if (auth()->user()->role === 'admin')
                            <th class="px-4 py-3 border-0">Người quản lý</th>
                        @endif
                        <th class="px-4 py-3 border-0 text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($programs as $program)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="fw-bold text-dark">{{ $program->name }}</div>
                                <div class="program-desc">{{ $program->description ?: 'Chưa có mô tả.' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="program-code"><i class="fas fa-hashtag"></i>{{ $program->code }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="fw-semibold">{{ $program->courses_count }}</span>
                                <span class="text-muted small">khóa</span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($program->status === 'published')
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Published</span>
                                @elseif ($program->status === 'hidden')
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3">Hidden</span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3">Draft</span>
                                @endif
                            </td>
                            @if (auth()->user()->role === 'admin')
                                <td class="px-4 py-3 text-muted">{{ $program->teacher?->name ?? 'N/A' }}</td>
                            @endif
                            <td class="px-4 py-3">
                                <div class="program-actions">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                        data-bs-toggle="modal" data-bs-target="#editProgramModal{{ $program->id }}">
                                        <i class="fas fa-edit me-1"></i>Sửa
                                    </button>
                                    <form action="{{ route('programs.destroy', $program->id) }}" method="POST"
                                        onsubmit="return confirm('Xóa chương trình này? Các khóa học đã gắn sẽ được giữ lại.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">
                                            <i class="fas fa-trash-alt me-1"></i>Xóa
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->role === 'admin' ? 6 : 5 }}" class="text-center py-5 text-muted">
                                <i class="fas fa-sitemap fa-3x mb-3 opacity-25"></i>
                                <p class="mb-0">Chưa có chương trình học nào.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach ($programs as $program)
        <div class="modal fade" id="editProgramModal{{ $program->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('programs.update', $program->id) }}" method="POST"
                    class="modal-content border-0 shadow">
                    @csrf
                    @method('PUT')
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Sửa chương trình học</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body py-4">
                        @include('programs.partials.form', ['program' => $program])
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                            data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    <div class="modal fade" id="createProgramModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('programs.store') }}" method="POST" class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Thêm chương trình học</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    @include('programs.partials.form', ['program' => null])
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Tạo chương trình</button>
                </div>
            </form>
        </div>
    </div>
@endsection
