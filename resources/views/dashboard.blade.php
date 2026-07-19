@extends('layouts.app')

@section('title', 'Bảng điều khiển')

@push('styles')
    @vite('resources/css/pages/dashboard.css')
@endpush

@section('content')
    <div class="dashboard-wrap container-fluid py-4">

        @php $role = auth()->user()->role; @endphp

        <section class="lms-hero anim-1">
            <div class="lms-hero__content">
                <div class="lms-hero__pill">
                    <span></span>
                    @if ($role === 'teacher')
                        SmartLMS Teacher Workspace
                    @elseif ($role === 'admin')
                        SmartLMS Admin Workspace
                    @else
                        SmartLMS Student Workspace
                    @endif
                </div>
                <h1 class="lms-hero__title">Xin chào, {{ auth()->user()->name }}!</h1>
                <p class="lms-hero__desc">
                    @if ($role === 'teacher')
                        Theo dõi lớp sắp dạy, bài cần chấm, học sinh cần chú ý và các gợi ý ưu tiên trong một bảng điều khiển gọn
                        gàng.
                    @elseif ($role === 'admin')
                        Quản lý tổng quan người dùng, lớp học, khóa học và các hoạt động vận hành quan trọng của hệ thống.
                    @else
                        Theo dõi khóa học, bài tập, quiz và tiến độ học tập của bạn trong một không gian rõ ràng.
                    @endif
                </p>
                <div class="lms-hero__date">
                    <i class="fa-regular fa-calendar-days"></i>
                    {{ \Carbon\Carbon::now()->translatedFormat('l, d/m/Y') }}
                </div>
            </div>
            <div class="lms-hero__side">
                <div class="hero-motion-icons" aria-hidden="true">
                    <span class="hero-motion-icon"><i class="fa-solid fa-book-open"></i></span>
                    <span class="hero-motion-icon"><i class="fa-solid fa-lightbulb"></i></span>
                    <span class="hero-motion-icon"><i class="fa-solid fa-graduation-cap"></i></span>
                    <span class="hero-motion-icon"><i class="fa-solid fa-chart-line"></i></span>
                </div>
                <img src="{{ asset('gretting-img.webp') }}" alt="" width="368" height="224">
            </div>
        </section>

        @if ($role !== 'teacher')
            {{-- QUICK ACTIONS --}}
            <div class="quick-actions anim-2">
                @if ($role === 'admin')
                    <a href="{{ route('users.index') }}" class="quick-action"><i class="fa-solid fa-users-gear"></i> Quản lý người
                        dùng</a>
                    <a href="{{ route('classes.index') }}" class="quick-action"><i class="fa-solid fa-school"></i> Quản lý
                        lớp</a>
                    <a href="{{ route('courses.index') }}" class="quick-action"><i class="fa-solid fa-book-open"></i> Quản lý
                        khóa
                        học</a>
                    <a href="{{ route('documents.upload') }}" class="quick-action"><i class="fa-solid fa-robot"></i> Huấn luyện
                        AI</a>
                @else
                    <a href="{{ route('courses.index') }}" class="quick-action"><i class="fa-solid fa-book-open"></i> Vào học</a>
                    <a href="{{ route('assignments.index') }}" class="quick-action"><i class="fa-solid fa-paper-plane"></i> Bài
                        tập</a>
                @endif
            </div>
        @endif

        {{-- ══════════════════════════════════════
             ADMIN VIEW
        ══════════════════════════════════════ --}}
        @if ($role === 'admin')
            @include('dashboard.partials.admin')
        @elseif ($role === 'teacher')
            @include('dashboard.partials.teacher')
        @else
            @include('dashboard.partials.student')
        @endif

    </div>

    @include('dashboard.partials.scripts')
@endsection
