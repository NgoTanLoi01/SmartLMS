@extends('layouts.app')

@section('content')
    <div class="container py-4" style="max-width:960px">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h2 class="fw-bold mb-1"><i class="fa-solid fa-bell text-primary me-2"></i>Trung tâm thông báo</h2>
                <p class="text-muted mb-0">Cập nhật học tập và lịch học ngay trong SmartLMS.</p>
            </div>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-outline-primary"><i class="fa-solid fa-check-double me-1"></i>Đánh dấu tất cả đã đọc</button>
                </form>
            @endif
        </div>

        <div class="d-flex gap-2 mb-3">
            <a href="{{ route('notifications.index') }}" class="btn {{ $filter !== 'unread' ? 'btn-primary' : 'btn-light border' }}">Tất cả</a>
            <a href="{{ route('notifications.index', ['status' => 'unread']) }}" class="btn {{ $filter === 'unread' ? 'btn-primary' : 'btn-light border' }}">
                Chưa đọc <span class="badge bg-danger ms-1">{{ $unreadCount }}</span>
            </a>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            @forelse ($notifications as $notification)
                <div class="p-3 p-md-4 border-bottom {{ $notification->read_at ? 'bg-white' : 'bg-primary bg-opacity-10' }}">
                    <div class="d-flex gap-3">
                        <div class="rounded-circle bg-white shadow-sm d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
                            <i class="fa-solid fa-{{ match($notification->type) { 'grade' => 'star', 'assignment' => 'file-pen', 'lesson' => 'book-open', 'material' => 'folder-open', 'quiz' => 'clipboard-question', 'schedule' => 'calendar-days', 'attendance_warning' => 'user-clock', default => 'bell' } }} text-primary"></i>
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between gap-2">
                                <h6 class="fw-bold mb-1">{{ $notification->title }}</h6>
                                <small class="text-muted text-nowrap">{{ $notification->created_at->diffForHumans() }}</small>
                            </div>
                            <p class="text-secondary mb-2">{{ $notification->message }}</p>
                            <div class="d-flex flex-wrap gap-2">
                                @if ($notification->action_url)
                                    <a href="{{ route('notifications.open', $notification) }}" class="btn btn-sm btn-primary">Xem chi tiết</a>
                                @endif
                                @if (!$notification->read_at)
                                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-light border">Đánh dấu đã đọc</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="fa-regular fa-bell-slash fa-3x mb-3 opacity-50"></i>
                    <p class="mb-0">Không có thông báo phù hợp.</p>
                </div>
            @endforelse
        </div>
        <div class="mt-3">{{ $notifications->links() }}</div>
    </div>
@endsection
