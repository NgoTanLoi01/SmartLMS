@extends('layouts.app')

@section('title', 'Thiết lập Sổ điểm · '.$course->title)

@section('content')
    @php
        $categoryRows = old('categories', [
            ['code' => 'process', 'name' => 'Điểm quá trình', 'weight_percent' => 40, 'allow_over_max' => false],
            ['code' => 'exam', 'name' => 'Điểm thi', 'weight_percent' => 60, 'allow_over_max' => false],
        ]);
        $sourceRows = $sources;
        $preview = session('gradebook_setup_preview');
    @endphp
    <div class="lms-page gradebook-setup-page" data-gradebook-setup>
        <x-ui.page-header title="Thiết lập Sổ điểm">
            <x-slot:meta><span><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>{{ $course->title }}</span></x-slot:meta>
        </x-ui.page-header>

        <div class="alert alert-light border" role="note">
            <strong>Quy trình an toàn:</strong> cấu hình → kiểm tra trước → áp dụng. Hệ thống chỉ sao chép dữ liệu hiện có sang Sổ điểm, không xóa hoặc sửa bảng Điểm danh.
        </div>

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Chưa thể áp dụng cấu hình.</strong>
                <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @if($preview)
            <div class="alert alert-success" role="status">
                <strong>Kiểm tra trước đạt.</strong>
                Dự kiến đồng bộ {{ $preview['planned_grades'] }} điểm:
                {{ $preview['graded'] }} có điểm, {{ $preview['ungraded'] }} chưa chấm,
                {{ $preview['missing'] }} thiếu và {{ $preview['excused'] }} được miễn.
                Chưa có dữ liệu nào được ghi.
            </div>
        @endif

        <form method="POST" action="{{ route('gradebook.setup.store', $course) }}" class="d-grid gap-4">
            @csrf

            <section class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5 mb-3">1. Kỳ điểm</h2>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label" for="period-name">Tên kỳ điểm</label><input id="period-name" class="form-control" name="period[name]" value="{{ old('period.name', 'Học kỳ 1') }}" required maxlength="255"></div>
                        <div class="col-md-3"><label class="form-label" for="period-code">Mã kỳ điểm</label><input id="period-code" class="form-control" name="period[code]" value="{{ old('period.code', 'hk1-'.now()->year) }}" required maxlength="80" pattern="[A-Za-z0-9_-]+"><div class="form-text">Không dấu, không khoảng trắng.</div></div>
                        <div class="col-md-2"><label class="form-label" for="period-start">Bắt đầu</label><input id="period-start" class="form-control" type="date" name="period[starts_at]" value="{{ old('period.starts_at') }}"></div>
                        <div class="col-md-2"><label class="form-label" for="period-end">Kết thúc</label><input id="period-end" class="form-control" type="date" name="period[ends_at]" value="{{ old('period.ends_at') }}"></div>
                        <div class="col-md-4"><label class="form-label" for="missing-policy">Khi chưa có điểm</label><select id="missing-policy" class="form-select" name="period[missing_policy]" required><option value="block" @selected(old('period.missing_policy', 'block') === 'block')>Không cho chốt điểm</option><option value="exclude" @selected(old('period.missing_policy') === 'exclude')>Không tính thành phần thiếu</option><option value="zero" @selected(old('period.missing_policy') === 'zero')>Tính thành 0</option></select></div>
                        <div class="col-md-3"><label class="form-label" for="rounding-precision">Làm tròn</label><select id="rounding-precision" class="form-select" name="period[rounding_precision]" required>@foreach(range(0, 4) as $precision)<option value="{{ $precision }}" @selected((int) old('period.rounding_precision', 1) === $precision)>{{ $precision }} chữ số thập phân</option>@endforeach</select></div>
                    </div>
                </div>
            </section>

            <section class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div><h2 class="h5 mb-1">2. Nhóm điểm và trọng số</h2><div class="text-muted small">Tổng trọng số phải bằng đúng 100%.</div></div>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-add-category><i class="fa-solid fa-plus" aria-hidden="true"></i> Thêm nhóm</button>
                    </div>
                    <div class="d-grid gap-2" data-category-list>
                        @foreach($categoryRows as $index => $category)
                            <div class="row g-2 align-items-end gradebook-category-row" data-category-row>
                                <div class="col-md-3"><label class="form-label">Mã nhóm</label><input class="form-control" data-category-code name="categories[{{ $index }}][code]" value="{{ $category['code'] }}" required pattern="[A-Za-z0-9_-]+"></div>
                                <div class="col-md-4"><label class="form-label">Tên nhóm</label><input class="form-control" data-category-name name="categories[{{ $index }}][name]" value="{{ $category['name'] }}" required maxlength="255"></div>
                                <div class="col-md-2"><label class="form-label">Trọng số (%)</label><input class="form-control" type="number" min="0.0001" max="100" step="0.0001" name="categories[{{ $index }}][weight_percent]" value="{{ $category['weight_percent'] }}" required></div>
                                <div class="col-md-2"><input type="hidden" name="categories[{{ $index }}][allow_over_max]" value="0"><label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="categories[{{ $index }}][allow_over_max]" value="1" @checked((bool)($category['allow_over_max'] ?? false))><span class="form-check-label">Cho điểm vượt tối đa</span></label></div>
                                <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-category aria-label="Xóa nhóm điểm"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></div>
                            </div>
                        @endforeach
                    </div>
                    <template data-category-template>
                        <div class="row g-2 align-items-end gradebook-category-row" data-category-row>
                            <div class="col-md-3"><label class="form-label">Mã nhóm</label><input class="form-control" data-category-code data-name="categories[__INDEX__][code]" required pattern="[A-Za-z0-9_-]+"></div>
                            <div class="col-md-4"><label class="form-label">Tên nhóm</label><input class="form-control" data-category-name data-name="categories[__INDEX__][name]" required maxlength="255"></div>
                            <div class="col-md-2"><label class="form-label">Trọng số (%)</label><input class="form-control" type="number" min="0.0001" max="100" step="0.0001" data-name="categories[__INDEX__][weight_percent]" required></div>
                            <div class="col-md-2"><input type="hidden" data-name="categories[__INDEX__][allow_over_max]" value="0"><label class="form-check mb-2"><input class="form-check-input" type="checkbox" data-name="categories[__INDEX__][allow_over_max]" value="1"><span class="form-check-label">Cho điểm vượt tối đa</span></label></div>
                            <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="button" data-remove-category aria-label="Xóa nhóm điểm"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></div>
                        </div>
                    </template>
                </div>
            </section>

            <section class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5 mb-1">3. Chọn và mapping thành phần điểm</h2>
                    <p class="text-muted">HS1/HS2/Thi được phát hiện từ bảng Điểm danh nhưng vẫn cần giáo viên xác nhận. Assignment và Quiz không tự ảnh hưởng điểm chính thức nếu chưa được chọn.</p>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr><th scope="col">Dùng</th><th scope="col">Nguồn</th><th scope="col">Tên thành phần</th><th scope="col">Loại</th><th scope="col">Nhóm</th><th scope="col">Hệ số</th><th scope="col">Chính sách nguồn</th></tr></thead>
                            <tbody>
                            @forelse($sourceRows as $index => $source)
                                <tr data-source-row>
                                    <td><input type="hidden" name="items[{{ $index }}][enabled]" value="0"><input class="form-check-input" type="checkbox" name="items[{{ $index }}][enabled]" value="1" @checked((bool)($source['enabled'] ?? false)) aria-label="Sử dụng {{ $source['name'] }}"></td>
                                    <td><span class="badge text-bg-light border">{{ $source['source_label'] }}</span><div class="small text-muted">#{{ $source['source_id'] }}</div><input type="hidden" name="items[{{ $index }}][source_type]" value="{{ $source['source_type'] }}"><input type="hidden" name="items[{{ $index }}][source_id]" value="{{ $source['source_id'] }}"></td>
                                    <td><input class="form-control form-control-sm" name="items[{{ $index }}][name]" value="{{ $source['name'] }}" required><input type="hidden" name="items[{{ $index }}][code]" value="{{ $source['code'] }}"></td>
                                    <td>
                                        <select class="form-select form-select-sm" name="items[{{ $index }}][item_type]" required>
                                            @if($source['source_type'] === 'legacy_attendance')
                                                <option value="hs1" @selected($source['item_type'] === 'hs1')>HS1</option><option value="hs2" @selected($source['item_type'] === 'hs2')>HS2</option><option value="exam" @selected($source['item_type'] === 'exam')>Thi</option><option value="manual" @selected($source['item_type'] === 'manual')>Điểm khác</option>
                                            @elseif($source['source_type'] === 'assignment')
                                                <option value="assignment">Bài tập</option>
                                            @else
                                                <option value="quiz" @selected($source['item_type'] === 'quiz')>Quiz</option><option value="exam" @selected($source['item_type'] === 'exam')>Thi</option>
                                            @endif
                                        </select>
                                    </td>
                                    <td><select class="form-select form-select-sm" data-category-select name="items[{{ $index }}][category_code]" data-selected="{{ $source['category_code'] }}" required>@foreach($categoryRows as $category)<option value="{{ $category['code'] }}" @selected($source['category_code'] === $category['code'])>{{ $category['name'] }}</option>@endforeach</select></td>
                                    <td><input class="form-control form-control-sm" type="number" min="0.0001" step="0.0001" name="items[{{ $index }}][item_weight]" value="{{ $source['item_weight'] }}" required></td>
                                    <td>
                                        @if($source['source_type'] === 'legacy_attendance')
                                            <select class="form-select form-select-sm" name="items[{{ $index }}][absence_policy]"><option value="missing" @selected($source['absence_policy'] === 'missing')>“Vắng” = thiếu điểm</option><option value="excused" @selected($source['absence_policy'] === 'excused')>“Vắng” = miễn</option><option value="zero" @selected($source['absence_policy'] === 'zero')>“Vắng” = 0 điểm</option></select>
                                        @elseif($source['source_type'] === 'quiz')
                                            <select class="form-select form-select-sm" name="items[{{ $index }}][attempt_policy]"><option value="highest_released" @selected($source['attempt_policy'] === 'highest_released')>Điểm cao nhất đã công bố</option><option value="latest_released" @selected($source['attempt_policy'] === 'latest_released')>Lần gần nhất đã công bố</option><option value="first_released" @selected($source['attempt_policy'] === 'first_released')>Lần đầu đã công bố</option></select>
                                        @else<span class="text-muted small">Theo thang điểm bài tập</span>@endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7"><x-ui.empty-state icon="fa-list-check" title="Chưa có nguồn điểm" description="Hãy tạo cột điểm, bài tập hoặc quiz trước khi thiết lập Sổ điểm." /></td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <div class="d-flex flex-wrap justify-content-between gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('gradebook.index', $course) }}">Quay lại Sổ điểm</a>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" type="submit" name="mode" value="preview"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Kiểm tra trước</button>
                    <button class="btn btn-primary" type="submit" name="mode" value="apply" @disabled(!$preview)><i class="fa-solid fa-check" aria-hidden="true"></i> Áp dụng cấu hình</button>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        @vite('resources/js/pages/gradebook-setup.js')
    @endpush
@endsection
