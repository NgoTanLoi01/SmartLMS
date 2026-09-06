@extends('layouts.app')

@section('title', 'Nhật ký hệ thống')

@push('styles')
    @vite('resources/css/pages/audit-logs.css')
@endpush

@section('content')
    @php
        $hasActiveFilters = collect($filters)
            ->except('storage')
            ->filter(fn ($value) => filled($value))
            ->isNotEmpty() || $filters['storage'] !== 'active';
        $selectedUser = $users->firstWhere('id', (int) $filters['user_id']);
        $actionLabels = [
            'grade_updated' => 'Cập nhật điểm',
            'grades_imported' => 'Nhập điểm',
            'grades_bulk_status_updated' => 'Cập nhật trạng thái điểm',
            'ai_assignment_analyzed' => 'AI phân tích bài tập',
            'ai_learning_analyzed' => 'AI phân tích học tập',
            'students_imported' => 'Nhập danh sách học viên',
            'schedule_created' => 'Tạo lịch học',
            'schedule_updated' => 'Cập nhật lịch học',
            'schedule_archived' => 'Lưu trữ lịch học',
            'schedule_copied' => 'Sao chép lịch học',
            'schedule_imported' => 'Nhập lịch học',
            'schedule_series_created' => 'Tạo chuỗi lịch học',
            'schedule_series_updated' => 'Cập nhật chuỗi lịch học',
            'schedule_series_archived' => 'Lưu trữ chuỗi lịch học',
            'contract_created' => 'Tạo hợp đồng',
            'contract_updated' => 'Cập nhật hợp đồng',
            'contract_archived' => 'Lưu trữ hợp đồng',
            'contract_imported' => 'Nhập hợp đồng',
            'account_lifecycle_updated' => 'Cập nhật trạng thái tài khoản',
            'account_profile_updated' => 'Cập nhật hồ sơ tài khoản',
            'content_cloned' => 'Sao chép nội dung',
            'questions_imported' => 'Nhập câu hỏi',
            'questions_bulk_updated' => 'Cập nhật câu hỏi hàng loạt',
            'trash_restored' => 'Khôi phục từ thùng rác',
            'trash_permanently_deleted' => 'Xóa vĩnh viễn dữ liệu',
            'audit_logs_archived' => 'Lưu trữ nhật ký theo chính sách',
            'audit_integrity_verified' => 'Xác minh toàn vẹn nhật ký',
            'audit_integrity_failed' => 'Phát hiện sai lệch nhật ký',
            'backup.created' => 'Tạo bản sao lưu',
            'backup.failed' => 'Tạo bản sao lưu thất bại',
            'backup.verified' => 'Kiểm tra bản sao lưu',
            'backup.verification_failed' => 'Bản sao lưu không toàn vẹn',
            'backup.restored' => 'Phục hồi bản sao lưu',
            'backup.restore_failed' => 'Phục hồi thất bại',
            'backup.restore_rejected' => 'Từ chối phục hồi',
        ];
        $entityLabels = [
            'AssignmentSubmission' => 'Bài nộp',
            'BackupRun' => 'Bản sao lưu',
            'Classroom' => 'Lớp học',
            'Course' => 'Khóa học',
            'Question' => 'Câu hỏi',
            'Schedule' => 'Lịch học',
            'TeachingContract' => 'Hợp đồng giảng dạy',
            'TeachingPayment' => 'Thanh toán giảng dạy',
            'User' => 'Tài khoản',
        ];
    @endphp

    <div class="lms-page audit-page">
        <section class="audit-overview" aria-label="Tổng quan nhật ký hệ thống">
            <div class="audit-overview__accent" aria-hidden="true"></div>
            <x-ui.page-header title="Nhật ký hệ thống">
                <x-slot:meta>
                    <span><i class="fa-solid fa-shield-halved"></i> Nhật ký append-only có chuỗi chữ ký HMAC</span>
                    <span><i class="fa-solid fa-box-archive"></i> Lưu trữ sau {{ number_format($archiveStats['retention_days']) }} ngày, không xóa dữ liệu</span>
                </x-slot:meta>
                <x-slot:actions>
                    <form method="POST" action="{{ route('audit-logs.verify') }}">
                        @csrf
                        <x-ui.button type="submit" tone="outline" icon="fa-shield-circle-check">
                            Kiểm tra toàn vẹn
                        </x-ui.button>
                    </form>
                </x-slot:actions>
            </x-ui.page-header>

            <div class="audit-stats">
                <article class="audit-stat audit-stat--total"><span><i class="fa-solid fa-list-check"></i></span><div><strong>{{ number_format((int) $stats->total) }}</strong><small>Bản ghi phù hợp</small></div></article>
                <article class="audit-stat audit-stat--today"><span><i class="fa-solid fa-clock"></i></span><div><strong>{{ number_format((int) $stats->today_count) }}</strong><small>Phát sinh hôm nay</small></div></article>
                <article class="audit-stat audit-stat--actors"><span><i class="fa-solid fa-users"></i></span><div><strong>{{ number_format((int) $stats->actor_count) }}</strong><small>Người thao tác</small></div></article>
                <article class="audit-stat audit-stat--actions"><span><i class="fa-solid fa-bolt"></i></span><div><strong>{{ number_format((int) $stats->action_count) }}</strong><small>Loại hành động</small></div></article>
            </div>
            <div class="audit-integrity-note">
                <span><i class="fa-solid fa-lock"></i></span>
                <p><strong>Bằng chứng không thể sửa hoặc xóa qua ứng dụng.</strong> {{ number_format($archiveStats['active']) }} bản ghi đang hoạt động và {{ number_format($archiveStats['archived']) }} bản ghi đã lưu trữ vẫn cùng nằm trong chuỗi kiểm tra toàn vẹn.</p>
            </div>
        </section>

        <section class="audit-filter-card" aria-labelledby="audit-filter-title">
            <header class="audit-filter-header">
                <div>
                    <span class="audit-section-icon"><i class="fa-solid fa-sliders"></i></span>
                    <div><h2 id="audit-filter-title">Bộ lọc nhật ký</h2><p>Thu hẹp dữ liệu theo hành động, người thực hiện hoặc thời gian.</p></div>
                </div>
                @if ($hasActiveFilters)
                    <a href="{{ route('audit-logs.index') }}" class="audit-reset-link"><i class="fa-solid fa-xmark"></i> Xóa bộ lọc</a>
                @endif
            </header>

            <div class="audit-quick-ranges" aria-label="Khoảng thời gian nhanh">
                <span>Xem nhanh</span>
                <a href="{{ route('audit-logs.index', array_merge(request()->except(['page', 'from_date', 'to_date']), ['from_date' => now()->toDateString(), 'to_date' => now()->toDateString()])) }}">Hôm nay</a>
                <a href="{{ route('audit-logs.index', array_merge(request()->except(['page', 'from_date', 'to_date']), ['from_date' => now()->subDays(6)->toDateString(), 'to_date' => now()->toDateString()])) }}">7 ngày</a>
                <a href="{{ route('audit-logs.index', array_merge(request()->except(['page', 'from_date', 'to_date']), ['from_date' => now()->subDays(29)->toDateString(), 'to_date' => now()->toDateString()])) }}">30 ngày</a>
            </div>

            <form method="GET" class="audit-filter-grid">
                <div class="audit-field">
                    <label for="audit-action-filter">Hành động</label>
                    <select name="action" id="audit-action-filter" class="form-select">
                        <option value="">Tất cả hành động</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ $actionLabels[$action] ?? $action }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="audit-field audit-field--actor">
                    <label for="audit-user-filter">Người thao tác</label>
                    <select name="user_id" id="audit-user-filter" class="form-select">
                        <option value="">Tất cả người dùng</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) $filters['user_id'] === (string) $user->id)>{{ $user->name }}{{ $user->email ? ' · ' . $user->email : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="audit-field">
                    <label for="audit-from-date">Từ ngày</label>
                    <input type="date" name="from_date" id="audit-from-date" class="form-control" value="{{ $filters['from_date'] }}">
                </div>
                <div class="audit-field">
                    <label for="audit-to-date">Đến ngày</label>
                    <input type="date" name="to_date" id="audit-to-date" class="form-control" value="{{ $filters['to_date'] }}">
                </div>
                <div class="audit-field">
                    <label for="audit-storage-filter">Tình trạng lưu trữ</label>
                    <select name="storage" id="audit-storage-filter" class="form-select">
                        <option value="active" @selected($filters['storage'] === 'active')>Đang hoạt động</option>
                        <option value="archived" @selected($filters['storage'] === 'archived')>Đã lưu trữ</option>
                        <option value="all" @selected($filters['storage'] === 'all')>Tất cả bản ghi</option>
                    </select>
                </div>
                <div class="audit-filter-actions">
                    <x-ui.button type="submit" icon="fa-filter">Áp dụng</x-ui.button>
                    <x-ui.button :href="route('audit-logs.index')" tone="outline" icon="fa-rotate-left" title="Đặt lại bộ lọc">Đặt lại</x-ui.button>
                </div>
            </form>

            @if ($hasActiveFilters)
                <div class="audit-active-filters">
                    <span><i class="fa-solid fa-filter-circle-check"></i> Đang lọc:</span>
                    @if ($filters['action'])<b>{{ $actionLabels[$filters['action']] ?? $filters['action'] }}</b>@endif
                    @if ($selectedUser)<b>{{ $selectedUser->name }}</b>@endif
                    @if ($filters['from_date'])<b>Từ {{ \Illuminate\Support\Carbon::parse($filters['from_date'])->format('d/m/Y') }}</b>@endif
                    @if ($filters['to_date'])<b>Đến {{ \Illuminate\Support\Carbon::parse($filters['to_date'])->format('d/m/Y') }}</b>@endif
                    @if ($filters['storage'] !== 'active')<b>{{ $filters['storage'] === 'archived' ? 'Đã lưu trữ' : 'Tất cả tình trạng' }}</b>@endif
                </div>
            @endif
        </section>

        <section class="audit-feed-card" aria-labelledby="audit-feed-title">
            <header class="audit-feed-header">
                <div>
                    <span class="audit-section-icon audit-section-icon--violet"><i class="fa-solid fa-timeline"></i></span>
                    <div><h2 id="audit-feed-title">Dòng hoạt động</h2><p>Mới nhất trước · trang {{ $logs->currentPage() }}/{{ $logs->lastPage() }}</p></div>
                </div>
                <span class="audit-result-count">{{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} / {{ $logs->total() }}</span>
            </header>

            <div class="audit-feed">
                @forelse ($logs as $log)
                    @php
                        $actionLower = strtolower($log->action);
                        $tone = match (true) {
                            str_contains($actionLower, 'delete'), str_contains($actionLower, 'destroy'), str_contains($actionLower, 'failed'), str_contains($actionLower, 'rejected') => 'danger',
                            str_contains($actionLower, 'create'), str_contains($actionLower, 'import'), str_contains($actionLower, 'restore'), str_contains($actionLower, 'clone'), str_contains($actionLower, 'copied') => 'success',
                            str_contains($actionLower, 'archive'), str_contains($actionLower, 'login'), str_contains($actionLower, 'logout') => 'warning',
                            str_contains($actionLower, 'backup'), str_contains($actionLower, 'ai_') => 'violet',
                            default => 'info',
                        };
                        $actionIcon = match ($tone) {
                            'danger' => 'fa-triangle-exclamation',
                            'success' => 'fa-circle-check',
                            'warning' => 'fa-box-archive',
                            'violet' => 'fa-wand-magic-sparkles',
                            default => 'fa-pen-to-square',
                        };
                        $entityName = class_basename($log->auditable_type);
                        $hasPayload = !empty($log->old_values) || !empty($log->new_values) || !empty($log->metadata);
                        $changedFieldCount = collect(array_keys($log->old_values ?? []))->merge(array_keys($log->new_values ?? []))->unique()->count();
                    @endphp
                    <article class="audit-entry audit-entry--{{ $tone }}">
                        <div class="audit-entry-marker" aria-hidden="true"><i class="fa-solid {{ $actionIcon }}"></i></div>
                        <div class="audit-entry-content">
                            <header class="audit-entry-header">
                                <div class="audit-entry-heading">
                                    <span class="audit-action audit-action--{{ $tone }}">{{ $actionLabels[$log->action] ?? $log->action }}</span>
                                    @if ($entityName)
                                        <span class="audit-subject"><i class="fa-solid fa-cube"></i> {{ $entityLabels[$entityName] ?? $entityName }} #{{ $log->auditable_id ?? '—' }}</span>
                                    @endif
                                </div>
                                <time datetime="{{ $log->created_at->toIso8601String() }}"><strong>{{ $log->created_at->diffForHumans() }}</strong><span>{{ $log->created_at->format('d/m/Y · H:i:s') }}</span></time>
                            </header>

                            <p class="audit-description">{{ $log->description ?: 'Không có mô tả bổ sung cho thao tác này.' }}</p>

                            <div class="audit-identity-row">
                                @php($actorName = $log->user?->name ?? $log->actor_name)
                                @php($actorEmail = $log->user?->email ?? $log->actor_email)
                                <span class="audit-avatar {{ $actorName ? '' : 'is-system' }}">@if ($actorName){{ Str::upper(Str::substr($actorName, 0, 1)) }}@else<i class="fa-solid fa-gear"></i>@endif</span>
                                <div class="audit-actor"><strong>{{ $actorName ?? 'Hệ thống' }}</strong><span>{{ $actorEmail ?? 'Tác vụ tự động' }}</span></div>
                                @if ($log->ip_address)<span class="audit-context-pill"><i class="fa-solid fa-network-wired"></i> {{ $log->ip_address }}</span>@endif
                                <span class="audit-context-pill audit-code"><i class="fa-solid fa-code"></i> {{ $log->action }}</span>
                                @if ($log->entry_hash)
                                    <span class="audit-context-pill audit-signature" title="{{ $log->entry_hash }}"><i class="fa-solid fa-fingerprint"></i> #{{ $log->chain_position }} · {{ Str::substr($log->entry_hash, 0, 10) }}</span>
                                @endif
                                @if ($log->archived_at)
                                    <span class="audit-context-pill audit-archived"><i class="fa-solid fa-box-archive"></i> Đã lưu trữ {{ $log->archived_at->format('d/m/Y') }}</span>
                                @endif
                            </div>

                            <div class="audit-entry-footer">
                                @if ($hasPayload)
                                    <details class="audit-payload">
                                        <summary>
                                            <span><i class="fa-solid fa-code-compare"></i> Xem dữ liệu thay đổi</span>
                                            <span class="audit-payload-summary">@if ($changedFieldCount)<b>{{ $changedFieldCount }} trường</b>@endif<i class="fa-solid fa-chevron-down"></i></span>
                                        </summary>
                                        <div class="audit-payload-grid">
                                            @if (!empty($log->old_values))
                                                <section class="audit-data-panel audit-data-panel--before"><header><span><i class="fa-solid fa-arrow-rotate-left"></i> Trước thay đổi</span><b>{{ count($log->old_values) }} trường</b></header><pre>{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></section>
                                            @endif
                                            @if (!empty($log->new_values))
                                                <section class="audit-data-panel audit-data-panel--after"><header><span><i class="fa-solid fa-arrow-right"></i> Sau thay đổi</span><b>{{ count($log->new_values) }} trường</b></header><pre>{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></section>
                                            @endif
                                            @if (!empty($log->metadata))
                                                <section class="audit-data-panel audit-data-panel--metadata"><header><span><i class="fa-solid fa-circle-info"></i> Thông tin thêm</span><b>{{ count($log->metadata) }} mục</b></header><pre>{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></section>
                                            @endif
                                        </div>
                                    </details>
                                @else
                                    <span class="audit-no-payload"><i class="fa-solid fa-minus"></i> Không có dữ liệu chi tiết</span>
                                @endif

                            </div>
                        </div>
                    </article>
                @empty
                    <div class="audit-empty-state">
                        <span><i class="fa-solid fa-shield-halved"></i></span>
                        <h3>Chưa có nhật ký phù hợp</h3>
                        <p>Thử thay đổi bộ lọc hoặc khoảng thời gian để xem thêm hoạt động.</p>
                        @if ($hasActiveFilters)<x-ui.button :href="route('audit-logs.index')" tone="outline" icon="fa-rotate-left">Xóa bộ lọc</x-ui.button>@endif
                    </div>
                @endforelse
            </div>

            @if ($logs->hasPages())<div class="audit-pagination">{{ $logs->links() }}</div>@endif
        </section>
    </div>

@endsection
