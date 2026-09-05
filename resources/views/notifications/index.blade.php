@extends('layouts.app')

@section('title', 'Trung tâm thông báo')

@push('styles')
    @vite('resources/css/pages/notifications.css')
@endpush

@section('content')
    @php
        $notificationTypes = [
            'grade' => ['icon' => 'fa-star', 'label' => 'Điểm số', 'tone' => 'amber'],
            'assignment' => ['icon' => 'fa-file-pen', 'label' => 'Bài tập', 'tone' => 'blue'],
            'lesson' => ['icon' => 'fa-book-open', 'label' => 'Bài học', 'tone' => 'violet'],
            'material' => ['icon' => 'fa-folder-open', 'label' => 'Học liệu', 'tone' => 'cyan'],
            'quiz' => ['icon' => 'fa-clipboard-question', 'label' => 'Bài kiểm tra', 'tone' => 'indigo'],
            'schedule' => ['icon' => 'fa-calendar-days', 'label' => 'Lịch học', 'tone' => 'green'],
            'attendance_warning' => ['icon' => 'fa-user-clock', 'label' => 'Điểm danh', 'tone' => 'red'],
        ];
    @endphp

    <div class="lms-page notifications-page">
        <section class="notifications-hero">
            <span class="notifications-hero__accent" aria-hidden="true"></span>
            <x-ui.page-header title="Trung tâm thông báo">
                <x-slot:meta>
                    <span><i class="fa-solid fa-bell" aria-hidden="true"></i>Cập nhật học tập và lịch học trong SmartLMS</span>
                </x-slot:meta>

                @if ($unreadCount > 0)
                    <x-slot:actions>
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            @method('PATCH')
                            <button class="notification-button notification-button--primary" type="submit">
                                <i class="fa-solid fa-check-double" aria-hidden="true"></i>
                                Đánh dấu tất cả đã đọc
                            </button>
                        </form>
                    </x-slot:actions>
                @endif
            </x-ui.page-header>

            <div class="notifications-summary" aria-label="Tổng quan thông báo">
                <div class="notifications-summary__item">
                    <span class="notifications-summary__icon is-blue"><i class="fa-solid fa-inbox"></i></span>
                    <span><strong>{{ $totalCount }}</strong><small>Tổng thông báo</small></span>
                </div>
                <div class="notifications-summary__item">
                    <span class="notifications-summary__icon is-red"><i class="fa-solid fa-envelope"></i></span>
                    <span><strong>{{ $unreadCount }}</strong><small>Thông báo chưa đọc</small></span>
                </div>
                <div class="notifications-summary__item">
                    <span class="notifications-summary__icon is-green"><i class="fa-solid fa-circle-check"></i></span>
                    <span><strong>{{ max(0, $totalCount - $unreadCount) }}</strong><small>Đã được xem</small></span>
                </div>
            </div>
        </section>

        <section class="notifications-card" aria-labelledby="notification-list-title">
            <header class="notifications-card__head">
                <div>
                    <h2 id="notification-list-title">Dòng thông báo</h2>
                    <p>Ưu tiên các cập nhật chưa đọc và mở nhanh nội dung liên quan.</p>
                </div>
                <nav class="notification-filters" aria-label="Lọc thông báo">
                    <a href="{{ route('notifications.index') }}"
                        class="notification-filter {{ $filter !== 'unread' ? 'is-active' : '' }}"
                        @if ($filter !== 'unread') aria-current="page" @endif>
                        <i class="fa-solid fa-layer-group" aria-hidden="true"></i>Tất cả
                    </a>
                    <a href="{{ route('notifications.index', ['status' => 'unread']) }}"
                        class="notification-filter {{ $filter === 'unread' ? 'is-active' : '' }}"
                        @if ($filter === 'unread') aria-current="page" @endif>
                        <i class="fa-solid fa-envelope" aria-hidden="true"></i>Chưa đọc
                        <span>{{ $unreadCount }}</span>
                    </a>
                </nav>
            </header>

            <div class="notification-feed">
                @forelse ($notifications as $notification)
                    @php
                        $type = $notificationTypes[$notification->type] ?? [
                            'icon' => 'fa-bell',
                            'label' => 'Hệ thống',
                            'tone' => 'slate',
                        ];
                    @endphp
                    <article class="notification-feed-item {{ $notification->read_at ? 'is-read' : 'is-unread' }}">
                        <span class="notification-feed-item__icon is-{{ $type['tone'] }}" aria-hidden="true">
                            <i class="fa-solid {{ $type['icon'] }}"></i>
                        </span>
                        <div class="notification-feed-item__content">
                            <div class="notification-feed-item__topline">
                                <div class="notification-feed-item__badges">
                                    <span class="notification-type">{{ $type['label'] }}</span>
                                    @if (! $notification->read_at)
                                        <span class="notification-unread"><i class="fa-solid fa-circle"></i>Chưa đọc</span>
                                    @endif
                                </div>
                                <time datetime="{{ $notification->created_at->toIso8601String() }}">
                                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                    {{ $notification->created_at->diffForHumans() }}
                                </time>
                            </div>
                            <h3>{{ $notification->title }}</h3>
                            <p>{{ $notification->message }}</p>
                            <div class="notification-feed-item__actions">
                                @if ($notification->action_url)
                                    <a href="{{ route('notifications.open', $notification) }}"
                                        class="notification-button notification-button--primary notification-button--small">
                                        Xem chi tiết<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    </a>
                                @endif
                                @if (! $notification->read_at)
                                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="notification-button notification-button--small" type="submit">
                                            <i class="fa-solid fa-check" aria-hidden="true"></i>Đánh dấu đã đọc
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <x-ui.empty-state
                        icon="fa-bell-slash"
                        title="Không có thông báo phù hợp"
                        description="Các cập nhật mới về khóa học, bài tập và lịch học sẽ xuất hiện tại đây."
                    />
                @endforelse
            </div>

            @if ($notifications->hasPages())
                <div class="notifications-card__footer">
                    <x-ui.pagination :paginator="$notifications" item-label="thông báo" />
                </div>
            @endif
        </section>
    </div>
@endsection
