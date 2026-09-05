@extends('layouts.app')

@section('title', 'Nhật ký hệ thống')

@push('styles')
    @vite('resources/css/pages/audit-logs.css')
@endpush

@section('content')
    @php
        $hasActiveFilters = collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty();
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
                    <span><i class="fa-solid fa-shield-halved"></i> Theo dõi các thao tác quản trị quan trọng</span>
                    <span><i class="fa-solid fa-lock"></i> Chỉ quản trị viên được truy cập</span>
                </x-slot:meta>
                <x-slot:actions>
                    <x-ui.button tone="danger" icon="fa-trash-can" data-bs-toggle="modal"
                        data-bs-target="#auditCleanupModal" :disabled="(int) $stats->total === 0">
                        Dọn nhật ký
                    </x-ui.button>
                </x-slot:actions>
            </x-ui.page-header>

            <div class="audit-stats">
                <article class="audit-stat audit-stat--total"><span><i class="fa-solid fa-list-check"></i></span><div><strong>{{ number_format((int) $stats->total) }}</strong><small>Bản ghi phù hợp</small></div></article>
                <article class="audit-stat audit-stat--today"><span><i class="fa-solid fa-clock"></i></span><div><strong>{{ number_format((int) $stats->today_count) }}</strong><small>Phát sinh hôm nay</small></div></article>
                <article class="audit-stat audit-stat--actors"><span><i class="fa-solid fa-users"></i></span><div><strong>{{ number_format((int) $stats->actor_count) }}</strong><small>Người thao tác</small></div></article>
                <article class="audit-stat audit-stat--actions"><span><i class="fa-solid fa-bolt"></i></span><div><strong>{{ number_format((int) $stats->action_count) }}</strong><small>Loại hành động</small></div></article>
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
                                <span class="audit-avatar {{ $log->user ? '' : 'is-system' }}">@if ($log->user){{ Str::upper(Str::substr($log->user->name, 0, 1)) }}@else<i class="fa-solid fa-gear"></i>@endif</span>
                                <div class="audit-actor"><strong>{{ $log->user?->name ?? 'Hệ thống' }}</strong><span>{{ $log->user?->email ?? 'Tác vụ tự động' }}</span></div>
                                @if ($log->ip_address)<span class="audit-context-pill"><i class="fa-solid fa-network-wired"></i> {{ $log->ip_address }}</span>@endif
                                <span class="audit-context-pill audit-code"><i class="fa-solid fa-code"></i> {{ $log->action }}</span>
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

                                <form method="POST" action="{{ route('audit-logs.destroy', $log) }}" onsubmit="return confirm('Xóa vĩnh viễn bản ghi nhật ký này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="audit-delete-button" title="Xóa bản ghi" aria-label="Xóa bản ghi {{ $log->id }}"><i class="fa-solid fa-trash"></i></button>
                                </form>
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

    <div class="modal fade" id="auditCleanupModal" tabindex="-1" aria-labelledby="auditCleanupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content audit-cleanup-modal">
                <div class="modal-body">
                    <span class="audit-cleanup-icon"><i class="fa-solid fa-trash-can"></i></span>
                    <span class="audit-cleanup-eyebrow">THAO TÁC KHÔNG THỂ HOÀN TÁC</span>
                    <h2 id="auditCleanupModalLabel">Xóa {{ number_format((int) $stats->total) }} bản ghi nhật ký?</h2>
                    <p>@if ($hasActiveFilters) Chỉ các bản ghi khớp với bộ lọc hiện tại sẽ bị xóa vĩnh viễn. @else Bạn chưa áp dụng bộ lọc. Thao tác này sẽ xóa toàn bộ nhật ký hệ thống. @endif</p>
                    <div class="audit-cleanup-scope"><i class="fa-solid fa-circle-info"></i><span>Việc xóa nhật ký không thay đổi dữ liệu nghiệp vụ, nhưng sẽ làm mất thông tin truy vết.</span></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lms-btn lms-btn-outline" data-bs-dismiss="modal">Hủy</button>
                    <form method="POST" action="{{ route('audit-logs.bulk-destroy') }}" onsubmit="return confirm('Xác nhận xóa vĩnh viễn các bản ghi nhật ký trong phạm vi này?');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="action" value="{{ $filters['action'] }}">
                        <input type="hidden" name="user_id" value="{{ $filters['user_id'] }}">
                        <input type="hidden" name="from_date" value="{{ $filters['from_date'] }}">
                        <input type="hidden" name="to_date" value="{{ $filters['to_date'] }}">
                        <button type="submit" class="lms-btn lms-btn-danger"><i class="fa-solid fa-trash-can"></i> Xóa vĩnh viễn</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
