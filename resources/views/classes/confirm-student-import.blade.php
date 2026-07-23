@extends('layouts.app')

@section('title', 'Xác nhận thay thế sĩ số - '.$classroom->name)

@section('content')
    <div class="container py-4" style="max-width:760px;">
        <div class="card border-danger shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Xác nhận thay thế toàn bộ sĩ số</h1>
                <p class="mb-3">Lớp <strong>{{ $classroom->name }}</strong> sẽ được đồng bộ chính xác theo file vừa tải lên.</p>

                @if (!empty($rosterChanged))
                    <div class="alert alert-warning">Sĩ số đã thay đổi sau lần xem trước. Danh sách dưới đây đã được tính lại; vui lòng xác nhận lại.</div>
                @endif

                <div class="alert {{ $preview->detachedCount > 0 ? 'alert-danger' : 'alert-success' }}">
                    <strong>{{ $preview->detachedCount }} học viên sẽ bị gỡ khỏi lớp.</strong>
                    Tài khoản và dữ liệu học tập của họ không bị xóa.
                </div>

                @if ($preview->studentsToDetach !== [])
                    <div class="border rounded mb-4" style="max-height:300px; overflow:auto;">
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

                    <label class="d-flex gap-2 align-items-start mb-4">
                        <input type="checkbox" name="replace_confirmed" value="1" required class="mt-1">
                        <span>Tôi đã kiểm tra danh sách và xác nhận thay thế toàn bộ sĩ số lớp.</span>
                    </label>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('classes.students.index', $classroom->id) }}" class="btn btn-outline-secondary">Hủy</a>
                        <button type="submit" class="btn btn-danger">Xác nhận thay thế</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
