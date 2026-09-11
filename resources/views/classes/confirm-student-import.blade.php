@extends('layouts.app')

@section('title', 'Xác nhận thay thế sĩ số - '.$classroom->name)

@push('styles')
    @vite('resources/css/pages/class-students.css')
@endpush

@section('content')
    <div class="lms-page student-import-confirm-page">
        <x-ui.page-header title="Xác nhận thay thế sĩ số" :breadcrumbs="[
            ['label' => 'Lớp học', 'url' => route('classes.index')],
            ['label' => $classroom->name, 'url' => route('classes.students.index', $classroom)],
            ['label' => 'Xác nhận import'],
        ]">
            <x-slot:meta>
                <span><i class="fa-solid fa-file-excel" aria-hidden="true"></i>Kiểm tra dữ liệu trước khi cập nhật lớp</span>
            </x-slot:meta>
        </x-ui.page-header>

        <section class="student-import-confirm-card">
            <div class="student-import-confirm-card__body">
                <h2 class="h4 mb-2">Thay thế toàn bộ danh sách học viên?</h2>
                <p class="text-muted mb-0">Lớp <strong>{{ $classroom->name }}</strong> sẽ được đồng bộ chính xác theo file vừa tải lên.</p>

                @if (!empty($rosterChanged))
                    <div class="alert alert-warning mt-3 mb-0"><i class="fa-solid fa-triangle-exclamation me-2"></i>Sĩ số đã thay đổi sau lần xem trước. Danh sách dưới đây đã được tính lại; vui lòng xác nhận lại.</div>
                @endif

                <div class="student-import-confirm-summary">
                    <i class="fa-solid fa-user-minus mt-1" aria-hidden="true"></i>
                    <div>
                        <strong>{{ $preview->detachedCount }} học viên sẽ bị gỡ khỏi lớp.</strong>
                        <div>Tài khoản và dữ liệu học tập của họ vẫn được giữ nguyên.</div>
                    </div>
                </div>

                @if ($preview->studentsToDetach !== [])
                    <div class="student-import-confirm-list">
                        <ul class="list-group list-group-flush">
                            @foreach ($preview->studentsToDetach as $student)
                                <li class="list-group-item d-flex justify-content-between gap-3">
                                    <span>{{ $student['name'] }}</span>
                                    <span class="text-muted">{{ $student['student_code'] ?: 'Không có mã HS' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('classes.students.import', $classroom->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="mode" value="replace">
                    <input type="hidden" name="preview_token" value="{{ $previewToken }}">

                    <label class="student-import-confirm-check">
                        <input type="checkbox" name="replace_confirmed" value="1" required>
                        <span>Tôi đã kiểm tra danh sách và xác nhận thay thế toàn bộ sĩ số lớp.</span>
                    </label>

                    <div class="student-import-confirm-actions">
                        <a href="{{ route('classes.students.index', $classroom->id) }}" class="lms-btn lms-btn-outline">Hủy bỏ</a>
                        <button type="submit" class="lms-btn lms-btn-danger"><i class="fa-solid fa-rotate"></i>Xác nhận thay thế</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection
