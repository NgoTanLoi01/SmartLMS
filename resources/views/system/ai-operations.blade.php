@extends('layouts.app')

@section('title', 'Theo dõi AI và hàng đợi')

@push('styles')
    @vite('resources/css/pages/system-operations.css')
@endpush

@section('content')
    @php
        $featureLabels = [
            'assignment_analysis' => 'Phân tích bài tập',
            'class_learning_analysis' => 'Phân tích học tập',
            'course_plan' => 'Lập kế hoạch khóa học',
            'quiz_generation' => 'Tạo câu hỏi kiểm tra',
        ];
        $statusLabels = [
            'queued' => 'Đang chờ',
            'processing' => 'Đang xử lý',
            'completed' => 'Hoàn thành',
            'failed' => 'Thất bại',
        ];
        $hasActiveFilters = filled($filters['status']) || filled($filters['feature']);
        $finishedCount = (int) $summary->completed + (int) $summary->failed;
        $successRate = $finishedCount > 0 ? round(((int) $summary->completed / $finishedCount) * 100, 1) : 0;
    @endphp

    <div class="lms-page system-operations-page ai-operations-page">
        <section class="system-hero ai-hero" aria-label="Tổng quan AI và hàng đợi">
            <div class="system-hero__accent" aria-hidden="true"></div>
            <x-ui.page-header title="Theo dõi AI và hàng đợi">
                <x-slot:meta>
                    <span><i class="fa-solid fa-calendar-days"></i> Số liệu vận hành trong 30 ngày</span>
                    <span><i class="fa-solid fa-rotate"></i> Cập nhật khi tải lại trang</span>
                </x-slot:meta>
                <x-slot:actions>
                    <x-ui.button :href="route('system.ai-operations.index', request()->query())" tone="outline" icon="fa-rotate-right">
                        Làm mới dữ liệu
                    </x-ui.button>
                </x-slot:actions>
            </x-ui.page-header>

            <div class="system-stats system-stats--five">
                <article class="system-stat stat-blue"><span><i class="fa-solid fa-layer-group"></i></span><div><strong>{{ number_format((int) $summary->total) }}</strong><small>Tổng tác vụ</small></div></article>
                <article class="system-stat stat-amber"><span><i class="fa-solid fa-spinner"></i></span><div><strong>{{ number_format((int) $summary->active) }}</strong><small>Đang chờ hoặc chạy</small></div></article>
                <article class="system-stat stat-red"><span><i class="fa-solid fa-triangle-exclamation"></i></span><div><strong>{{ number_format((int) $summary->failed) }}</strong><small>Tác vụ thất bại</small></div></article>
                <article class="system-stat stat-violet"><span><i class="fa-solid fa-coins"></i></span><div><strong>{{ number_format((int) $summary->total_tokens) }}</strong><small>Token đã sử dụng</small></div></article>
                <article class="system-stat stat-green"><span><i class="fa-solid fa-dollar-sign"></i></span><div><strong>${{ number_format((float) $summary->estimated_cost_usd, 6) }}</strong><small>Chi phí ước tính</small></div></article>
            </div>
        </section>

        <section class="queue-health-card" aria-label="Sức khỏe hàng đợi AI">
            <div class="queue-health-main {{ (int) $summary->failed > 0 ? 'has-warning' : 'is-healthy' }}">
                <span><i class="fa-solid {{ (int) $summary->failed > 0 ? 'fa-triangle-exclamation' : 'fa-circle-check' }}"></i></span>
                <div>
                    <strong>{{ (int) $summary->failed > 0 ? 'Có tác vụ cần kiểm tra' : 'Hàng đợi đang ổn định' }}</strong>
                    <small>{{ (int) $summary->active }} tác vụ đang hoạt động · {{ (int) $summary->failed }} lỗi trong 30 ngày</small>
                </div>
            </div>
            <div class="queue-health-metric"><span>Tỷ lệ hoàn thành</span><strong>{{ number_format($successRate, 1) }}%</strong></div>
            <div class="queue-health-metric"><span>Thời gian trung bình</span><strong>{{ (int) $summary->average_duration_ms > 0 ? number_format((float) $summary->average_duration_ms / 1000, 1) . ' giây' : '—' }}</strong></div>
        </section>

        <section class="system-filter-card" aria-labelledby="ai-filter-title">
            <header class="system-section-header">
                <div><span class="system-section-icon"><i class="fa-solid fa-sliders"></i></span><div><h2 id="ai-filter-title">Lọc tác vụ</h2><p>Tìm nhanh theo trạng thái xử lý hoặc chức năng AI.</p></div></div>
                @if ($hasActiveFilters)<a href="{{ route('system.ai-operations.index') }}" class="system-reset-link"><i class="fa-solid fa-xmark"></i> Xóa bộ lọc</a>@endif
            </header>
            <form method="GET" class="ai-filter-grid">
                <div class="system-field">
                    <label for="ai-status-filter">Trạng thái</label>
                    <select name="status" id="ai-status-filter" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        @foreach ($statusLabels as $value => $label)<option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="system-field">
                    <label for="ai-feature-filter">Chức năng AI</label>
                    <select name="feature" id="ai-feature-filter" class="form-select">
                        <option value="">Tất cả chức năng</option>
                        @foreach ($features as $feature)<option value="{{ $feature }}" @selected($filters['feature'] === $feature)>{{ $featureLabels[$feature] ?? $feature }}</option>@endforeach
                    </select>
                </div>
                <div class="system-filter-actions">
                    <x-ui.button type="submit" icon="fa-filter">Áp dụng</x-ui.button>
                    <x-ui.button :href="route('system.ai-operations.index')" tone="outline" icon="fa-rotate-left">Đặt lại</x-ui.button>
                </div>
            </form>
        </section>

        <section class="system-list-card" aria-labelledby="ai-operation-list-title">
            <header class="system-section-header system-list-header">
                <div><span class="system-section-icon icon-violet"><i class="fa-solid fa-microchip"></i></span><div><h2 id="ai-operation-list-title">Hoạt động xử lý</h2><p>Mới nhất trước · trang {{ $operations->currentPage() }}/{{ $operations->lastPage() }}</p></div></div>
                <span class="system-result-count">{{ $operations->firstItem() ?? 0 }}–{{ $operations->lastItem() ?? 0 }} / {{ $operations->total() }}</span>
            </header>

            <div class="ai-operation-list">
                @forelse ($operations as $operation)
                    @php
                        $status = $operation->status;
                        $statusIcon = match ($status) {
                            'completed' => 'fa-circle-check',
                            'failed' => 'fa-circle-xmark',
                            'processing' => 'fa-spinner',
                            default => 'fa-clock',
                        };
                        $subjectTitle = $operation->subject?->title ?? $operation->subject?->name;
                        $progress = min(100, max(0, (int) data_get($operation->metadata, 'progress_percent', 0)));
                        $hasDetails = filled($operation->error_message) || !empty($operation->metadata);
                    @endphp
                    <article class="ai-operation ai-status-{{ $status }}">
                        <div class="ai-operation-status"><i class="fa-solid {{ $statusIcon }} {{ $status === 'processing' ? 'fa-spin' : '' }}"></i></div>
                        <div class="ai-operation-body">
                            <header class="ai-operation-header">
                                <div>
                                    <div class="ai-operation-title-line">
                                        <h3>{{ $featureLabels[$operation->feature] ?? $operation->feature }}</h3>
                                        <span class="system-status status-{{ $status }}">{{ $statusLabels[$status] ?? $status }}</span>
                                    </div>
                                    <div class="ai-operation-context">
                                        <span><i class="fa-solid fa-server"></i> {{ $operation->provider ?: 'Không rõ provider' }} · {{ $operation->model ?: 'Không rõ model' }}</span>
                                        @if ($subjectTitle)<span><i class="fa-solid fa-link"></i> {{ Str::limit($subjectTitle, 70) }}</span>@endif
                                    </div>
                                </div>
                                <time datetime="{{ $operation->created_at->toIso8601String() }}"><strong>{{ $operation->created_at->diffForHumans() }}</strong><span>{{ $operation->created_at->format('d/m/Y · H:i:s') }}</span></time>
                            </header>

                            @if ($status === 'processing' || ($status === 'queued' && $progress > 0))
                                <div class="ai-progress">
                                    <div><span>{{ data_get($operation->metadata, 'stage') ?: 'Đang xử lý tác vụ' }}</span><strong>{{ $progress }}%</strong></div>
                                    <div class="ai-progress-bar"><span style="width: {{ $progress }}%"></span></div>
                                    @if (data_get($operation->metadata, 'current_lesson'))<small>{{ data_get($operation->metadata, 'current_lesson') }}</small>@endif
                                </div>
                            @endif

                            <div class="ai-operation-metrics">
                                <span><i class="fa-solid fa-coins"></i><b>{{ number_format((int) $operation->total_tokens) }}</b> token</span>
                                <span><i class="fa-solid fa-dollar-sign"></i><b>${{ number_format((float) $operation->estimated_cost_usd, 8) }}</b></span>
                                <span><i class="fa-solid fa-stopwatch"></i><b>{{ $operation->duration_ms ? number_format($operation->duration_ms) . ' ms' : '—' }}</b></span>
                                <span><i class="fa-solid fa-rotate"></i>Lần chạy <b>{{ (int) $operation->attempts }}</b></span>
                                <span class="ai-operation-id"><i class="fa-solid fa-fingerprint"></i>{{ Str::limit($operation->uuid, 18, '…') }}</span>
                            </div>

                            @if ($hasDetails)
                                <details class="system-details">
                                    <summary><span><i class="fa-solid fa-code"></i> Chi tiết kỹ thuật</span><i class="fa-solid fa-chevron-down"></i></summary>
                                    <div class="ai-technical-details">
                                        @if ($operation->error_message)<div class="ai-error-message"><i class="fa-solid fa-triangle-exclamation"></i><span><strong>Thông báo lỗi</strong>{{ $operation->error_message }}</span></div>@endif
                                        @if (!empty($operation->metadata))<pre>{{ json_encode($operation->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>@endif
                                    </div>
                                </details>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="system-empty-state"><span><i class="fa-solid fa-microchip"></i></span><h3>Chưa có tác vụ phù hợp</h3><p>Thử thay đổi bộ lọc hoặc tạo một tác vụ AI mới.</p>@if ($hasActiveFilters)<x-ui.button :href="route('system.ai-operations.index')" tone="outline" icon="fa-rotate-left">Xóa bộ lọc</x-ui.button>@endif</div>
                @endforelse
            </div>

            @if ($operations->hasPages())<div class="system-pagination">{{ $operations->links() }}</div>@endif
        </section>
    </div>
@endsection
