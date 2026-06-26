@extends('layouts.app')

@section('title', $course->title)

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap');

        * {
            box-sizing: border-box;
        }

        body,
        .card,
        .card-body {
            font-family: 'Be Vietnam Pro', sans-serif;
        }

        /* ── LAYOUT ── */
        .page-wrapper {
            background: #f0f2f5;
            min-height: 100vh;
            padding: 16px 12px;
        }

        @media (min-width: 768px) {
            .page-wrapper {
                padding: 24px 20px;
            }
        }

        /* ── HEADER CARD ── */
        .header-card {
            background: #fff;
            border-radius: 16px;
            padding: 16px 18px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06), 0 4px 16px rgba(0, 0, 0, .04);
        }

        @media (min-width: 992px) {
            .header-card {
                border-radius: 20px;
                padding: 22px 28px;
                margin-bottom: 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                flex-wrap: wrap;
            }
        }

        .header-course-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #111827;
            margin: 0 0 4px;
            line-height: 1.3;
        }

        @media (min-width: 576px) {
            .header-course-title {
                font-size: 1.2rem;
            }
        }

        @media (min-width: 992px) {
            .header-course-title {
                font-size: 1.3rem;
            }
        }

        .header-teacher {
            font-size: 13px;
            color: #6b7280;
            margin: 0;
        }

        /* Progress */
        .progress-wrap {
            margin-top: 10px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #6b7280;
        }

        .progress-label span:last-child {
            color: #2563eb;
        }

        .progress-track {
            height: 7px;
            background: #e5e7eb;
            border-radius: 99px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 99px;
            background: linear-gradient(90deg, #60a5fa, #2563eb);
            transition: width .6s ease;
        }

        /* ── TOOLBAR ── */
        .toolbar {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 6px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        @media (min-width: 992px) {
            .toolbar {
                margin-top: 0;
                flex-wrap: nowrap;
            }
        }

        .tool-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 10px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all .18s;
            text-decoration: none;
            line-height: 1;
            white-space: nowrap;
        }

        .tool-btn i {
            font-size: 11px;
        }

        .tool-btn.blue {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .tool-btn.blue:hover {
            background: #dbeafe;
        }

        .tool-btn.amber {
            background: #fffbeb;
            color: #92400e;
        }

        .tool-btn.amber:hover {
            background: #fef3c7;
        }

        .tool-btn.purple {
            background: #f5f3ff;
            color: #5b21b6;
        }

        .tool-btn.purple:hover {
            background: #ede9fe;
        }

        .tool-btn.teal {
            background: #ecfdf5;
            color: #065f46;
        }

        .tool-btn.teal:hover {
            background: #d1fae5;
        }

        /* ── MOBILE SIDEBAR OVERLAY + DRAWER ── */
        /* The sidebar col stays in normal flow on desktop.
                                   On mobile we use a separate off-canvas panel + overlay. */

        #mobile-sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .5);
            z-index: 1099;
            backdrop-filter: blur(2px);
        }

        #mobile-sidebar-overlay.active {
            display: block;
        }

        #mobile-sidebar-drawer {
            position: fixed;
            top: 0;
            left: 0;
            width: min(85vw, 320px);
            height: 100%;
            background: #fff;
            z-index: 1100;
            transform: translateX(-110%);
            transition: transform .28s cubic-bezier(.4, 0, .2, 1);
            overflow-y: auto;
            padding: 0;
            border-radius: 0 16px 16px 0;
            box-shadow: 4px 0 24px rgba(0, 0, 0, .15);
        }

        #mobile-sidebar-drawer.open {
            transform: translateX(0);
        }

        .mobile-drawer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            border-bottom: 1px solid #f3f4f6;
            background: #fff;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        /* FAB to open drawer */
        #btn-open-sidebar {
            position: fixed;
            bottom: 20px;
            left: 16px;
            z-index: 1050;
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: #2563eb;
            color: #fff;
            border: none;
            box-shadow: 0 4px 20px rgba(37, 99, 235, .4);
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .2s;
        }

        #btn-open-sidebar:hover {
            background: #1d4ed8;
            transform: scale(1.06);
        }

        @media (min-width: 768px) {
            #btn-open-sidebar {
                display: none !important;
            }
        }

        /* ── DESKTOP SIDEBAR (normal flow, sticky) ── */
        .desktop-sidebar-wrap {
            display: none;
        }

        @media (min-width: 768px) {
            .desktop-sidebar-wrap {
                display: block;
                position: sticky;
                top: 16px;
            }
        }

        .sidebar-inner-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06), 0 4px 16px rgba(0, 0, 0, .04);
        }

        .sidebar-scroll {
            max-height: calc(100vh - 120px);
            overflow-y: auto;
        }

        .sidebar-scroll::-webkit-scrollbar {
            width: 1px;
        }

        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 99px;
        }

        /* Shared sidebar list styles */
        .lesson-item-wrapper,
        .assignment-item-wrapper,
        .quiz-item-wrapper {
            transition: all 0.15s ease;
            border-left: 3px solid transparent;
            cursor: pointer;
        }

        .lesson-item-wrapper:hover,
        .assignment-item-wrapper:hover,
        .quiz-item-wrapper:hover {
            background: #f9fafb !important;
            border-left-color: #2563eb;
        }

        .lesson-item-wrapper.active,
        .assignment-item-wrapper.active,
        .quiz-item-wrapper.active {
            background: #eff6ff !important;
            border-left-color: #2563eb;
        }

        .action-buttons {
            opacity: 0;
            transition: opacity 0.15s;
            flex-shrink: 0;
            padding-left: 4px;
        }

        @media (hover: none) {
            .action-buttons {
                opacity: 1;
            }
        }

        .lesson-item-wrapper:hover .action-buttons,
        .module-header-wrapper:hover .action-buttons,
        .assignment-item-wrapper:hover .action-buttons,
        .quiz-item-wrapper:hover .action-buttons {
            opacity: 1;
        }

        .btn-action {
            width: 26px;
            height: 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-size: 12px;
            transition: all .15s;
            text-decoration: none;
        }

        .btn-edit {
            color: #f59e0b;
        }

        .btn-edit:hover {
            background: #fef3c7;
        }

        .btn-delete {
            color: #ef4444;
        }

        .btn-delete:hover {
            background: #fee2e2;
        }

        .text-truncate-custom {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
            min-width: 0;
        }

        .accordion-button:not(.collapsed) {
            background: #fff;
            color: #2563eb;
            box-shadow: none;
        }

        .accordion-button:focus {
            box-shadow: none;
        }

        .accordion-button {
            padding-right: 3rem;
            font-weight: 700;
            font-size: 15px;
        }

        .accordion-item {
            border: none;
            border-bottom: 1px solid #f3f4f6;
        }

        .accordion-item:last-child {
            border-bottom: none;
        }

        /* ── CONTENT CARD ── */
        .content-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06), 0 4px 16px rgba(0, 0, 0, .04);
            display: flex;
            flex-direction: column;
        }

        #video-container iframe {
            border-radius: 16px 16px 0 0;
        }

        #external-link-container {
            background: linear-gradient(135deg, #eff6ff, #f0fdf4);
            border-bottom: 1px solid #e5e7eb;
        }

        /* ── LESSON ── */
        #lesson-content-area {
            padding: 20px 18px;
        }

        @media (min-width: 768px) {
            #lesson-content-area {
                padding: 36px 40px;
            }
        }

        .lesson-header-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: 14px;
        }

        @media (min-width: 768px) {
            .lesson-header-title {
                font-size: 1.4rem;
                margin-bottom: 18px;
            }
        }

        .lesson-divider {
            border: none;
            border-top: 2px solid #f3f4f6;
            margin-bottom: 20px;
        }

        .lesson-body {
            font-size: 14px;
            line-height: 1.85;
            color: #374151;
        }

        @media (min-width: 768px) {
            .lesson-body {
                font-size: 15px;
            }
        }

        .course-intro-card {
            background: linear-gradient(135deg, #fafbff, #f0f9ff);
            border: 1px solid #e0eaff;
            border-radius: 14px;
            padding: 20px;
        }

        @media (min-width: 768px) {
            .course-intro-card {
                padding: 32px 36px;
            }
        }

        /* Attachment */
        .attachment-box {
            margin: 0 18px 20px;
            background: #fafafa;
            border: 1.5px dashed #d1d5db;
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            transition: all .2s;
        }

        @media (min-width: 768px) {
            .attachment-box {
                margin: 0 40px 28px;
                flex-wrap: nowrap;
            }
        }

        .attachment-box:hover {
            border-color: #2563eb;
            background: #eff6ff;
        }

        /* ── ASSIGNMENT ── */
        #assignment-content-area {
            padding: 20px 18px;
            background: #fffdf5;
        }

        @media (min-width: 768px) {
            #assignment-content-area {
                padding: 36px 40px;
            }
        }

        .assignment-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: #111827;
        }

        @media (min-width: 768px) {
            .assignment-title {
                font-size: 1.35rem;
            }
        }

        .due-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 99px;
            border: 1px solid #fecaca;
        }

        .instructions-box {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 18px 20px;
            line-height: 1.8;
            color: #374151;
            font-size: 14px;
        }

        @media (min-width: 768px) {
            .instructions-box {
                padding: 24px 28px;
            }
        }

        .submission-dropzone {
            border: 2px dashed #fbbf24;
            background: #fffbeb;
            border-radius: 14px;
            padding: 20px 16px;
            transition: all .3s;
        }

        .submission-dropzone:hover {
            border-color: #f59e0b;
            background: #fef3c7;
        }

        .submitted-file-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        @media (min-width: 576px) {
            .submitted-file-card {
                flex-wrap: nowrap;
            }
        }

        .grading-result-box {
            border-radius: 12px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 16px 18px;
        }

        /* ── QUIZ ── */
        #quiz-content-area {
            padding: 24px 18px;
            background: #faf8ff;
        }

        @media (min-width: 768px) {
            #quiz-content-area {
                padding: 36px 40px;
            }
        }

        .quiz-display-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: 20px;
        }

        @media (min-width: 768px) {
            .quiz-display-title {
                font-size: 1.4rem;
                margin-bottom: 28px;
            }
        }

        .quiz-stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 14px 16px;
            text-align: center;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
            flex: 1;
            min-width: 100px;
        }

        @media (min-width: 768px) {
            .quiz-stat-card {
                padding: 20px 24px;
                min-width: 130px;
            }
        }

        .quiz-stat-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #9ca3af;
            margin-bottom: 6px;
        }

        .quiz-stat-value {
            font-size: 1.4rem;
            font-weight: 800;
            color: #7c3aed;
        }

        @media (min-width: 768px) {
            .quiz-stat-value {
                font-size: 1.6rem;
            }
        }

        .btn-quiz-start {
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            border: none;
            padding: 13px 28px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 99px;
            box-shadow: 0 6px 24px rgba(124, 58, 237, .28);
            transition: all .3s;
            color: #fff;
        }

        @media (min-width: 768px) {
            .btn-quiz-start {
                padding: 14px 36px;
                font-size: 15px;
            }
        }

        .btn-quiz-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(124, 58, 237, .36);
            color: #fff;
        }

        .quiz-notice {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 12px;
            padding: 16px 18px;
            text-align: left;
            max-width: 480px;
            width: 100%;
        }

        /* ── FOOTER NAV ── */
        .footer-nav {
            background: #fff;
            border-top: 1px solid #f3f4f6;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        @media (min-width: 768px) {
            .footer-nav {
                padding: 14px 28px;
            }
        }

        .btn-footer-nav {
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            padding: 8px 14px;
            border: 1.5px solid #e5e7eb;
            background: #fff;
            color: #374151;
            transition: all .18s;
        }

        .btn-footer-nav:hover:not(:disabled) {
            border-color: #2563eb;
            color: #2563eb;
            background: #eff6ff;
        }

        .btn-footer-nav:disabled {
            opacity: .4;
            cursor: not-allowed;
        }

        .btn-complete-lesson {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            padding: 8px 16px;
            color: #fff;
            box-shadow: 0 4px 14px rgba(34, 197, 94, .28);
            transition: all .3s;
        }

        .btn-complete-lesson:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(34, 197, 94, .36);
            color: #fff;
        }

        .modal-backdrop {
            z-index: 1050 !important;
        }

        .modal {
            z-index: 1060 !important;
        }

        /* Drawer không ảnh hưởng desktop */
        @media (min-width: 768px) {

            #mobile-sidebar-drawer,
            #mobile-sidebar-overlay {
                display: none !important;
            }
        }

        /* Ngăn sidebar tràn ngang trên desktop */
        .col-md-4.col-lg-3 {
            min-width: 0;
        }

        .desktop-sidebar-wrap {
            min-width: 0;
            max-width: 100%;
        }

        .sidebar-inner-card {
            min-width: 0;
            overflow: hidden;
        }

        .accordion-button:not(.collapsed) {
            background: #DBEAFE !important;
            color: #1E40AF;
            border-left-color: #1D4ED8 !important;
            box-shadow: none;
        }

        .accordion-button:hover {
            background: #DBEAFE !important;
        }

        .accordion-button:focus {
            box-shadow: none;
        }

        .accordion-item {
            border: none;
            border-bottom: 1px solid #E5E7EB;
        }

        .accordion-item:last-child {
            border-bottom: none;
        }

        .module-meta {
            font-size: 11px;
            color: #3B82F6;
            display: block;
            font-weight: 400;
            margin-top: 1px;
        }

        .sidebar-status-row {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 5px;
        }

        .sidebar-status-pill {
            align-items: center;
            border-radius: 99px;
            display: inline-flex;
            font-size: 10px;
            font-weight: 800;
            gap: 4px;
            line-height: 1;
            padding: 4px 7px;
            white-space: nowrap;
        }

        .sidebar-status-pill.done {
            background: #dcfce7;
            color: #166534;
        }

        .sidebar-status-pill.pending {
            background: #f3f4f6;
            color: #4b5563;
        }

        .sidebar-status-pill.assignment {
            background: #fef3c7;
            color: #92400e;
        }

        .sidebar-status-pill.quiz {
            background: #ede9fe;
            color: #5b21b6;
        }

        .sidebar-status-pill.overdue {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Bài học — trắng, viền trái xanh khi hover/active */
        .lesson-item-wrapper {
            background: var(--color-background-primary);
            border-bottom: 0.5px solid #F3F4F6;
            border-left: 3px solid transparent;
            transition: all 0.15s;
            cursor: pointer;
        }

        .lesson-item-wrapper:hover {
            background: #F8FAFF !important;
            border-left-color: #93C5FD;
        }

        .lesson-item-wrapper.active {
            background: #EFF6FF !important;
            border-left-color: #2563EB;
        }

        .lesson-icon-video {
            color: #2563EB;
            font-size: 14px;
        }

        .lesson-icon-done {
            color: #16A34A;
            font-size: 14px;
        }

        .lesson-icon-doc {
            color: #6B7280;
            font-size: 14px;
        }

        /* Bài tập — vàng amber */
        .assignment-item-wrapper {
            background: #FFFBEB !important;
            border-bottom: 0.5px solid #FDE68A;
            border-left: 3px solid #F59E0B;
            transition: all 0.15s;
            cursor: pointer;
        }

        .assignment-item-wrapper:hover {
            background: #FEF3C7 !important;
            border-left-color: #D97706;
        }

        .assignment-item-wrapper.active {
            background: #FEF3C7 !important;
            border-left-color: #D97706;
        }

        .lesson-name-text {
            font-size: 14px;
            color: #111827;
        }

        .assignment-item .lesson-name-text {
            color: #92400E;
            font-weight: 600;
        }

        .assignment-item .lesson-dur-text {
            color: #B45309;
        }

        .lesson-icon-assign {
            color: #D97706;
            font-size: 14px;
        }

        /* Bài tập đã nộp — xanh lá nhạt */
        .assignment-item-wrapper.submitted {
            background: #F0FFF4 !important;
            border-left-color: #16A34A;
            border-bottom-color: #BBF7D0;
        }

        .assignment-item-wrapper.submitted .lesson-name-text {
            color: #14532D;
        }

        .assignment-item-wrapper.submitted .lesson-dur-text {
            color: #15803D;
        }

        /* Bài kiểm tra — tím */
        .quiz-item-wrapper {
            background: #FAFAFA;
            border-bottom: 0.5px solid #EDE9FE;
            border-left: 3px solid transparent;
            transition: all 0.15s;
            cursor: pointer;
        }

        .quiz-item-wrapper:hover {
            background: #F5F3FF !important;
            border-left-color: #C4B5FD;
        }

        .quiz-item-wrapper.active {
            background: #F5F3FF !important;
            border-left-color: #7C3AED;
        }

        .quiz-item-wrapper .lesson-name-text {
            color: #5B21B6;
            font-weight: 600;
        }

        .quiz-item-wrapper .lesson-dur-text {
            color: #7C3AED;
        }

        .lesson-icon-quiz {
            color: #7C3AED;
            font-size: 14px;
        }

        /* Header section Quiz — tím đậm hơn */
        .quiz-section-header .accordion-button {
            background: #F5F3FF !important;
            border-left-color: #7C3AED !important;
            color: #5B21B6;
        }

        .quiz-section-header .accordion-button:not(.collapsed),
        .quiz-section-header .accordion-button:hover {
            background: #EDE9FE !important;
            color: #4C1D95;
        }

        /* Đã hoàn thành (quiz completed) */
        .quiz-item-wrapper.completed {
            background: #F0FDF4 !important;
            border-left-color: #16A34A;
            border-bottom-color: #BBF7D0;
        }

        .quiz-item-wrapper.completed .lesson-name-text {
            color: #14532D;
        }

        .quiz-item-wrapper.completed .lesson-dur-text {
            color: #16A34A;
        }

        .course-dashboard-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: 16px;
        }

        .course-dashboard-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .05);
        }

        .course-dashboard-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .course-dashboard-value {
            color: #0f172a;
            font-size: 26px;
            font-weight: 800;
            line-height: 1.1;
            margin-top: 6px;
        }

        .course-dashboard-sub {
            color: #64748b;
            font-size: 12px;
            margin-top: 5px;
        }

        .course-action-panel {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .05);
            margin-bottom: 16px;
            overflow: hidden;
        }

        .course-action-header {
            align-items: stretch;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 16px;
            background: #f8fafc;
        }

        .course-action-body {
            padding: 16px;
        }

        .next-action-main {
            align-items: flex-start;
            display: flex;
            gap: 12px;
            min-width: 0;
        }

        .next-action-icon {
            align-items: center;
            background: #dbeafe;
            border-radius: 12px;
            color: #1d4ed8;
            display: inline-flex;
            flex-shrink: 0;
            height: 42px;
            justify-content: center;
            width: 42px;
        }

        .next-action-eyebrow {
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .04em;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .next-action-title {
            color: #0f172a;
            font-size: 17px;
            font-weight: 800;
            line-height: 1.35;
            margin: 0;
        }

        .next-action-meta {
            color: #64748b;
            font-size: 13px;
            margin-top: 4px;
        }

        .course-next-btn {
            align-items: center;
            align-self: center;
            border-radius: 10px;
            display: inline-flex;
            font-weight: 800;
            gap: 7px;
            justify-content: center;
            min-height: 42px;
            padding: 10px 16px;
            white-space: nowrap;
        }

        .course-todo-list {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .course-todo-item {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px;
            background: #fff;
            min-width: 0;
        }

        .course-todo-item .fw-bold {
            line-height: 1.35;
        }

        .course-empty-state {
            align-items: center;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            color: #475569;
            display: flex;
            gap: 12px;
            justify-content: center;
            padding: 18px;
            text-align: left;
        }

        .welcome-guide {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 18px;
        }

        .welcome-guide-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 14px;
        }

        .welcome-guide-item {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px;
        }

        .welcome-guide-item i {
            margin-bottom: 8px;
        }

        .lesson-next-step-panel {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 14px;
            margin: 0 18px 20px;
            padding: 16px;
        }

        @media (min-width: 768px) {
            .lesson-next-step-panel {
                margin: 0 40px 28px;
                padding: 18px 20px;
            }
        }

        .lesson-next-step-panel.is-complete {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .lesson-next-step-content {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
        }

        .lesson-next-step-copy {
            min-width: 0;
        }

        .lesson-next-step-eyebrow {
            color: #2563eb;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .04em;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .lesson-next-step-title {
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
            line-height: 1.35;
            margin: 0;
        }

        .lesson-next-step-meta {
            color: #64748b;
            font-size: 12px;
            margin-top: 3px;
        }

        .lesson-next-step-actions {
            display: flex;
            flex-shrink: 0;
            gap: 8px;
        }

        .teacher-mode-panel {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .05);
            margin-bottom: 16px;
            padding: 14px 16px;
        }

        .teacher-mode-row {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
        }

        .teacher-mode-title {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
            margin: 0;
        }

        .teacher-mode-subtitle {
            color: #64748b;
            font-size: 12px;
            margin-top: 3px;
        }

        .teacher-mode-toggle {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            display: inline-flex;
            gap: 4px;
            padding: 4px;
        }

        .teacher-mode-btn {
            background: transparent;
            border: 0;
            border-radius: 8px;
            color: #475569;
            font-size: 12px;
            font-weight: 800;
            padding: 7px 10px;
        }

        .teacher-mode-btn.active {
            background: #fff;
            box-shadow: 0 1px 4px rgba(15, 23, 42, .12);
            color: #1d4ed8;
        }

        .teacher-quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .teacher-quick-actions .tool-btn {
            margin-top: 0;
        }

        .teacher-preview-banner {
            align-items: center;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            color: #1e40af;
            display: none;
            font-size: 13px;
            font-weight: 700;
            gap: 8px;
            margin-top: 12px;
            padding: 10px 12px;
        }

        .preview-student-mode .toolbar,
        .preview-student-mode .teacher-quick-actions,
        .preview-student-mode .course-dashboard-grid,
        .preview-student-mode .action-buttons {
            display: none !important;
        }

        .preview-student-mode .teacher-preview-banner {
            display: flex;
        }

        @media (max-width: 991.98px) {
            .welcome-guide-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 991.98px) {
            .course-dashboard-grid,
            .course-todo-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .course-dashboard-grid,
            .course-todo-list {
                grid-template-columns: 1fr;
            }

            .course-action-header {
                align-items: stretch;
                flex-direction: column;
            }

            .course-action-header .btn,
            .course-todo-item .btn,
            .lesson-next-step-actions .btn {
                width: 100%;
            }

            .lesson-next-step-content,
            .teacher-mode-row {
                align-items: stretch;
                flex-direction: column;
            }

            .lesson-next-step-actions,
            .teacher-mode-toggle {
                width: 100%;
            }

            .teacher-mode-btn {
                flex: 1;
            }
        }
    </style>

    {{-- Mobile overlay + drawer --}}
    <div id="mobile-sidebar-overlay"></div>
    <div id="mobile-sidebar-drawer">
        <div class="mobile-drawer-header">
            <h6 class="mb-0 fw-bold small text-uppercase text-muted ms-3">
                <i class="fas fa-list-ol me-2"></i>Nội dung học tập
            </h6>
            <button id="btn-close-sidebar" class="btn btn-sm btn-light border" type="button">
                <i class="fas fa-times"></i>
            </button>
        </div>
        {{-- Sidebar content injected by JS --}}
        <div id="mobile-sidebar-content"></div>
    </div>

    <div class="page-wrapper" id="course-page-wrapper">

        {{-- ── HEADER ── --}}
        <div class="header-card">
            <div class="flex-grow-1 min-w-0">
                <h1 class="header-course-title">{{ $course->title }}</h1>
                <p class="header-teacher">
                    <i class="fas fa-chalkboard-teacher me-1"></i> Giáo viên: {{ $course->teacher->name }}
                </p>
                @if (auth()->user()->role === 'student')
                    <div class="progress-wrap">
                        <div class="progress-label">
                            <span>Tiến độ học tập</span>
                            <span id="progress-text">{{ $completedCount }}/{{ $totalLessons }} bài
                                &nbsp;({{ $progress }}%)</span>
                        </div>
                        <div class="progress-track">
                            <div id="progress-bar" class="progress-fill" style="width: {{ $progress }}%;"></div>
                        </div>
                    </div>
                @endif
            </div>

            @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
                <div class="toolbar">
                    <button class="tool-btn blue" data-bs-toggle="modal" data-bs-target="#addModuleModal">
                        <i class="fas fa-folder-plus"></i> Thêm chương
                    </button>
                    <button class="tool-btn blue" data-bs-toggle="modal" data-bs-target="#addLessonModal">
                        <i class="fas fa-plus"></i> Bài học
                    </button>
                    <button class="tool-btn amber" data-bs-toggle="modal" data-bs-target="#addCourseAssignmentModal">
                        <i class="fas fa-tasks"></i> Bài tập
                    </button>
                    <button class="tool-btn purple" data-bs-toggle="modal" data-bs-target="#addQuizModal">
                        <i class="fas fa-question-circle"></i> Trắc nghiệm
                    </button>
                    <a href="{{ route('attendance.show', $course->id) }}" class="tool-btn teal">
                        <i class="fas fa-user-check"></i> Điểm danh
                    </a>
                </div>
            @endif
        </div>

        @if (auth()->id() === $course->teacher_id || auth()->user()->role === 'admin')
            <div class="teacher-mode-panel" id="teacher-mode-panel">
                <div class="teacher-mode-row">
                    <div>
                        <h6 class="teacher-mode-title"><i class="fas fa-layer-group me-2 text-primary"></i>Chế độ giáo viên</h6>
                        <div class="teacher-mode-subtitle">Quản lý nội dung, theo dõi tiến độ và thao tác nhanh trên khóa học.</div>
                    </div>
                    <div class="teacher-mode-toggle" role="group" aria-label="Chọn chế độ xem khóa học">
                        <button type="button" class="teacher-mode-btn active" data-course-mode="manage">
                            <i class="fas fa-pen-to-square me-1"></i>Quản lý
                        </button>
                        <button type="button" class="teacher-mode-btn" data-course-mode="preview">
                            <i class="fas fa-eye me-1"></i>Xem như học sinh
                        </button>
                    </div>
                </div>
                <div class="teacher-preview-banner">
                    <i class="fas fa-eye"></i>
                    <span>Bạn đang xem ở chế độ học sinh. Các nút sửa/xóa/tạo mới đang được ẩn tạm thời.</span>
                </div>
                <div class="teacher-quick-actions">
                    <button class="tool-btn blue" data-bs-toggle="modal" data-bs-target="#addLessonModal">
                        <i class="fas fa-plus"></i> Thêm bài học
                    </button>
                    <button class="tool-btn amber" data-bs-toggle="modal" data-bs-target="#addCourseAssignmentModal">
                        <i class="fas fa-file-signature"></i> Giao bài tập
                    </button>
                    <button class="tool-btn purple" data-bs-toggle="modal" data-bs-target="#addQuizModal">
                        <i class="fas fa-stopwatch"></i> Tạo quiz
                    </button>
                    <a href="{{ route('attendance.show', $course->id) }}" class="tool-btn teal">
                        <i class="fas fa-user-check"></i> Điểm danh
                    </a>
                    <a href="{{ route('quizzes.ai_generate') }}" class="tool-btn purple">
                        <i class="fas fa-wand-magic-sparkles"></i> Tạo câu hỏi AI
                    </a>
                </div>
            </div>
        @endif

        @if (auth()->user()->role === 'student')
            <div class="course-action-panel">
                <div class="course-action-header">
                    @if ($studentNextAction)
                        @php
                            $nextIcon = match ($studentNextAction['type']) {
                                'assignment' => 'fas fa-file-signature',
                                'quiz' => 'fas fa-stopwatch',
                                default => 'fas fa-play',
                            };
                        @endphp
                        <div class="next-action-main">
                            <div class="next-action-icon">
                                <i class="{{ $nextIcon }}"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="next-action-eyebrow">Việc nên làm tiếp theo</div>
                                <h2 class="next-action-title">{{ $studentNextAction['title'] }}</h2>
                                <div class="next-action-meta">{{ $studentNextAction['label'] }} · {{ $studentNextAction['meta'] }}</div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary course-next-btn course-jump-btn"
                            data-target-type="{{ $studentNextAction['type'] }}"
                            data-target-id="{{ $studentNextAction['target_id'] }}">
                            <i class="fas fa-arrow-right"></i> Mở ngay
                        </button>
                    @else
                        <div class="course-empty-state w-100">
                            <i class="fas fa-check-circle text-success fs-4"></i>
                            <div>
                                <div class="fw-bold">Bạn đã xử lý xong các việc chính trong khóa học này.</div>
                                <div class="small">Có thể xem lại bài học hoặc kiểm tra phần nhận xét/điểm số khi giáo viên cập nhật.</div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="course-action-body">
                    @if ($studentTodoItems->isNotEmpty())
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
                            <div>
                                <h6 class="fw-bold mb-1"><i class="fas fa-list-check me-2 text-primary"></i>Các mục còn lại</h6>
                                <div class="text-muted small">{{ $studentTodoItems->count() }} việc cần xử lý trong khóa học này</div>
                            </div>
                        </div>
                        <div class="course-todo-list">
                            @foreach ($studentTodoItems->take(4) as $todo)
                                <div class="course-todo-item">
                                    <span class="badge {{ $todo['type'] === 'assignment' ? 'bg-warning text-dark' : ($todo['type'] === 'quiz' ? 'bg-primary' : 'bg-success') }} mb-2">
                                        {{ $todo['label'] }}
                                    </span>
                                    <div class="fw-bold text-truncate-custom">{{ $todo['title'] }}</div>
                                    <div class="text-muted small mb-3 text-truncate-custom">{{ $todo['meta'] }}</div>
                                    <button type="button" class="btn btn-sm btn-outline-primary course-jump-btn"
                                        data-target-type="{{ $todo['type'] }}"
                                        data-target-id="{{ $todo['target_id'] }}">
                                        Mở mục này
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-2">
                            <i class="fas fa-check-circle text-success me-1"></i>Không còn bài học, bài tập hoặc quiz đang chờ xử lý.
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="course-dashboard-grid">
                <div class="course-dashboard-card">
                    <div class="course-dashboard-label">Học sinh</div>
                    <div class="course-dashboard-value">{{ $courseDashboard['students_count'] }}</div>
                    <div class="course-dashboard-sub">{{ $courseDashboard['modules_count'] }} chương · {{ $courseDashboard['lessons_count'] }} bài học</div>
                </div>
                <div class="course-dashboard-card">
                    <div class="course-dashboard-label">Hoàn thành bài học</div>
                    <div class="course-dashboard-value">{{ $courseDashboard['lesson_completion_rate'] }}%</div>
                    <div class="course-dashboard-sub">Tỷ lệ hoàn thành toàn khóa</div>
                </div>
                <div class="course-dashboard-card">
                    <div class="course-dashboard-label">Nộp bài tập</div>
                    <div class="course-dashboard-value">{{ $courseDashboard['assignment_submission_rate'] }}%</div>
                    <div class="course-dashboard-sub">{{ $courseDashboard['pending_grades'] }} bài đang chờ chấm</div>
                </div>
                <div class="course-dashboard-card">
                    <div class="course-dashboard-label">Quiz</div>
                    <div class="course-dashboard-value">{{ $courseDashboard['quiz_completion_rate'] }}%</div>
                    <div class="course-dashboard-sub">Điểm TB: {{ $courseDashboard['average_score'] !== null ? round($courseDashboard['average_score'], 1) : 'N/A' }}</div>
                </div>
            </div>
        @endif

        {{-- ── MAIN GRID ── --}}
        <div class="row g-3 align-items-start">

            {{-- DESKTOP SIDEBAR (hidden on mobile, shown md+) --}}
            <div class="col-md-4 col-lg-3 d-none d-md-block">
                <div class="desktop-sidebar-wrap">
                    <div class="sidebar-inner-card">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold small text-uppercase text-muted ms-3">
                                <i class="fas fa-list-ol me-2"></i>Nội dung học tập
                            </h6>
                        </div>
                        <div class="sidebar-scroll">
                            @include('courses.partials.sidebar')
                        </div>
                    </div>
                </div>
            </div>

            {{-- CONTENT --}}
            <div class="col-12 col-md-8 col-lg-9">
                <div class="content-card">

                    {{-- Video --}}
                    <div id="video-container" class="ratio ratio-16x9 bg-dark d-none">
                        <iframe id="lesson-video" src="" allowfullscreen></iframe>
                    </div>

                    {{-- External link banner --}}
                    <div id="external-link-container" class="p-4 p-md-5 text-center d-none border-bottom">
                        <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-circle p-3"
                            style="background:#dbeafe;">
                            <i class="fas fa-external-link-alt fa-2x text-primary"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Tài liệu / Video tham khảo ngoài</h5>
                        <p class="text-muted small mb-4">Bài học này chứa một liên kết ngoài hệ thống.</p>
                        <a href="#" id="external-link-btn" target="_blank"
                            class="btn btn-primary rounded-pill px-4 fw-bold">
                            <i class="fas fa-external-link-alt me-2"></i>Truy cập ngay
                        </a>
                    </div>

                    {{-- ══ LESSON AREA ══ --}}
                    <div id="lesson-content-area">
                        <h2 id="lesson-title" class="lesson-header-title">{{ $course->title }}</h2>
                        <hr class="lesson-divider">
                        <div id="lesson-body" class="lesson-body">
                            <div class="course-intro-card">
                                <div class="course-description">{!! nl2br(e($course->description)) !!}</div>
                            </div>
                            <div id="welcome-placeholder" class="welcome-guide mt-4">
                                @if (auth()->user()->role === 'student')
                                    <h6 class="fw-bold mb-2"><i class="fas fa-compass me-2 text-primary"></i>Bắt đầu học ở đâu?</h6>
                                    <div class="text-muted small">Bạn có thể bấm "Mở ngay" ở khối phía trên, hoặc chọn một mục trong danh sách nội dung. Các mục đã hoàn thành sẽ có dấu tích màu xanh.</div>
                                    <div class="welcome-guide-grid">
                                        <div class="welcome-guide-item">
                                            <i class="fas fa-play-circle text-primary d-block"></i>
                                            <div class="fw-bold small">Bài học</div>
                                            <div class="text-muted small">Đọc nội dung, xem video rồi đánh dấu hoàn thành.</div>
                                        </div>
                                        <div class="welcome-guide-item">
                                            <i class="fas fa-file-signature text-warning d-block"></i>
                                            <div class="fw-bold small">Bài tập</div>
                                            <div class="text-muted small">Xem yêu cầu, nộp file hoặc nhập bài tự luận.</div>
                                        </div>
                                        <div class="welcome-guide-item">
                                            <i class="fas fa-stopwatch text-purple d-block" style="color:#7c3aed;"></i>
                                            <div class="fw-bold small">Quiz</div>
                                            <div class="text-muted small">Bấm bắt đầu khi đã sẵn sàng vì hệ thống sẽ tính giờ.</div>
                                        </div>
                                    </div>
                                @else
                                    <h6 class="fw-bold mb-2"><i class="fas fa-pen-to-square me-2 text-primary"></i>Quản lý nội dung khóa học</h6>
                                    <div class="text-muted small">Chọn bài học, bài tập hoặc quiz ở danh sách bên trái để xem nhanh. Các nút thêm nội dung nằm ở thanh công cụ phía trên.</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Attachment --}}
                    <div id="lesson-attachment-container" class="attachment-box d-none">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary bg-opacity-10 rounded-3 p-2 p-md-3">
                                <i class="fas fa-file-pdf fa-lg text-primary"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-dark d-block" style="font-size:14px;">Tài liệu đính kèm</span>
                                <span id="lesson-attachment-name" class="text-muted"
                                    style="font-size:12px;">filename.pdf</span>
                            </div>
                        </div>
                        <a href="#" id="lesson-attachment-btn" download
                            class="btn btn-primary btn-sm rounded-pill px-3 fw-bold flex-shrink-0">
                            <i class="fas fa-download me-1"></i> Tải về
                        </a>
                    </div>

                    <div id="lesson-next-step-panel" class="lesson-next-step-panel d-none">
                        <div class="lesson-next-step-content">
                            <div class="lesson-next-step-copy">
                                <div id="lesson-next-step-eyebrow" class="lesson-next-step-eyebrow">Bước tiếp theo</div>
                                <h3 id="lesson-next-step-title" class="lesson-next-step-title">Tiếp tục học</h3>
                                <div id="lesson-next-step-meta" class="lesson-next-step-meta">Chọn hành động phù hợp để tiếp tục.</div>
                            </div>
                            <div class="lesson-next-step-actions">
                                <button type="button" id="lesson-next-step-primary"
                                    class="btn btn-primary btn-sm fw-bold course-jump-btn"
                                    data-target-type="" data-target-id="">
                                    Mở ngay
                                </button>
                                <button type="button" id="lesson-next-step-secondary"
                                    class="btn btn-outline-primary btn-sm fw-bold d-none course-jump-btn"
                                    data-target-type="" data-target-id="">
                                    Bài tiếp
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- ══ ASSIGNMENT AREA ══ --}}
                    <div id="assignment-content-area" class="d-none flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                            <h2 id="assignment-title" class="assignment-title">
                                <i class="fas fa-tasks me-2" style="color:#f59e0b;"></i>Tiêu đề bài tập
                            </h2>
                            <span id="assignment-badge" class="badge rounded-pill px-3 py-2 fs-6">Trạng thái</span>
                        </div>
                        <div class="mb-4">
                            <span class="due-badge">
                                <i class="fas fa-clock"></i> Hạn nộp: <span id="assignment-due-date"></span>
                            </span>
                        </div>
                        <hr class="lesson-divider">
                        <h6 class="fw-700 mb-3" style="font-weight:700;color:#374151;">
                            <i class="fas fa-list-check me-2 text-primary"></i>Yêu cầu bài tập
                        </h6>
                        <div id="assignment-instructions" class="instructions-box mb-4"></div>

                        @if (auth()->user()->role === 'student')
                            <div id="student-submission-area">
                                <div id="submitted-info-area" class="d-none">
                                    <h6 class="fw-bold text-success mb-3">
                                        <i class="fas fa-check-circle me-2"></i>Bài làm của bạn
                                    </h6>
                                    <div class="submitted-file-card mb-3">
                                        <div>
                                            <p class="mb-1 fw-bold" style="font-size:14px;">
                                                <i class="fas fa-clock me-2 text-success"></i>Thông tin nộp bài
                                            </p>
                                            <p class="mb-0 text-muted" style="font-size:12px;">
                                                Đã nộp lúc:
                                                <span id="submitted-time-text" class="fw-medium"></span>
                                            </p>
                                        </div>
                                    </div>
                                    <div id="submitted-file-card" class="submitted-file-card mb-3">
                                        <div>
                                            <p class="mb-1 fw-bold" style="font-size:14px;">
                                                <i class="fas fa-file-alt me-2 text-primary"></i>Tài liệu đã tải lên
                                            </p>
                                            <p class="mb-0 text-muted" style="font-size:12px;">Mở file để xem chi tiết bài làm.</p>
                                        </div>
                                        <a href="#" id="submitted-file-link" target="_blank"
                                            class="btn btn-outline-primary btn-sm rounded-pill px-3 flex-shrink-0">
                                            <i class="fas fa-eye me-1"></i> Xem file
                                        </a>
                                    </div>
                                    <div id="submitted-text-answer-card" class="submitted-file-card d-none mb-3 align-items-start">
                                        <div class="w-100">
                                            <p class="mb-2 fw-bold" style="font-size:14px;">
                                                <i class="fas fa-align-left me-2 text-primary"></i>Bài tự luận đã nộp
                                            </p>
                                            <div id="submitted-text-answer-text" class="bg-light rounded-3 p-3 text-dark"
                                                style="font-size:14px;line-height:1.7;white-space:pre-wrap;"></div>
                                        </div>
                                    </div>
                                    <div id="grading-result" class="d-none mb-3 grading-result-box">
                                        <h6 class="fw-bold text-success mb-2">
                                            <i class="fas fa-star me-2"></i>Điểm số:
                                            <span id="grade-score" class="text-dark fs-5"></span>/10
                                        </h6>
                                        <p class="mb-0 text-dark" style="font-size:14px;">
                                            <strong>Nhận xét:</strong> <span id="grade-feedback"></span>
                                        </p>
                                    </div>
                                    <div class="d-flex gap-2 flex-wrap" id="submission-actions">
                                        <button type="button" class="btn btn-primary rounded-pill px-4"
                                            id="btn-edit-submission">
                                            <i class="fas fa-edit me-1"></i> Chỉnh sửa bài nộp
                                        </button>
                                        <form id="delete-submission-form" method="POST" class="m-0"
                                            onsubmit="return confirm('Bạn có chắc chắn muốn hủy bài đã nộp?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger rounded-pill px-4">
                                                <i class="fas fa-trash-alt me-1"></i> Hủy bài nộp
                                            </button>
                                        </form>
                                    </div>
                                    <p id="graded-warning" class="text-danger small mt-2 d-none fst-italic">
                                        <i class="fas fa-lock me-1"></i>Giáo viên đã chấm điểm, bạn không thể sửa hoặc xóa
                                        bài.
                                    </p>
                                </div>
                                <div id="upload-form-area" class="d-none submission-dropzone">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold text-primary mb-0">
                                            <i class="fas fa-cloud-upload-alt me-2"></i>Nộp bài tập
                                        </h6>
                                        <button type="button" class="btn btn-sm btn-light d-none"
                                            id="btn-cancel-edit">Hủy sửa</button>
                                    </div>
                                    <form id="course-submit-assignment-form" method="POST" enctype="multipart/form-data"
                                        action="">
                                        @csrf
                                        <div id="essay-answer-field" class="mb-3 d-none">
                                            <label for="essay-answer-input" class="form-label small fw-bold text-muted">
                                                Nội dung bài tự luận
                                            </label>
                                            <textarea name="text_answer" id="essay-answer-input" class="form-control bg-white border-0 shadow-sm"
                                                rows="8" placeholder="Nhập bài làm tự luận của bạn..."></textarea>
                                        </div>
                                        <div class="d-flex flex-column flex-sm-row gap-2">
                                            <div id="file-upload-field" class="flex-grow-1">
                                                <input type="file" name="file" id="assignment-file-input"
                                                    class="form-control bg-white border-0 shadow-sm">
                                                <div class="form-text small">Chỉ cần chọn file với bài dạng nộp file hoặc file + tự luận.</div>
                                            </div>
                                            <button class="btn btn-warning text-dark px-4 fw-bold flex-shrink-0"
                                                type="submit">
                                                <i class="fas fa-paper-plane me-1"></i>Gửi bài
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="text-center p-4 p-md-5 bg-white rounded-4 border">
                                <i class="fas fa-users-cog fa-3x text-primary mb-3 d-block"></i>
                                <p class="text-muted mb-0">Bấm vào biểu tượng
                                    <i class="fas fa-users-cog text-primary mx-1"></i>
                                    ở danh sách bên trái để chấm điểm bài tập này.
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- ══ QUIZ AREA ══ --}}
                    <div id="quiz-content-area" class="d-none flex-column align-items-center">
                        <div class="w-100" style="max-width:520px;">
                            <div class="text-center mb-4">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                                    style="width:68px;height:68px;background:#f5f3ff;">
                                    <i id="quiz-main-icon" class="fas fa-stopwatch fa-2x" style="color:#7c3aed;"></i>
                                </div>
                                <h2 id="quiz-display-title" class="quiz-display-title">Tiêu đề bài kiểm tra</h2>
                            </div>
                            <div class="d-flex gap-2 mb-4 flex-wrap">
                                <div class="quiz-stat-card">
                                    <div class="quiz-stat-label"><i class="fas fa-clock me-1"></i> Thời gian</div>
                                    <div class="quiz-stat-value">
                                        <span id="quiz-display-duration">0</span>
                                        <small style="font-size:.8rem;font-weight:600;color:#6b7280;">phút</small>
                                    </div>
                                </div>
                                @if (auth()->user()->role === 'student')
                                    <div class="quiz-stat-card">
                                        <div class="quiz-stat-label"><i class="fas fa-tasks me-1"></i> Trạng thái</div>
                                        <div><span id="quiz-status-text" class="fw-bold text-warning"
                                                style="font-size:.95rem;">Chưa làm</span></div>
                                    </div>
                                    <div id="quiz-score-box" class="quiz-stat-card d-none"
                                        style="background:#f0fdf4;border-color:#bbf7d0;">
                                        <div class="quiz-stat-label text-success"><i class="fas fa-star me-1"></i> Điểm số
                                        </div>
                                        <div class="quiz-stat-value text-success"><span id="quiz-score-text">0</span>/10
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @if (auth()->user()->role === 'student')
                                <div id="quiz-student-action-area">
                                    <div class="quiz-notice mb-4">
                                        <h6 class="fw-bold mb-2" style="color:#92400e;font-size:13px;">
                                            <i class="fas fa-exclamation-triangle me-2"></i>Lưu ý quan trọng
                                        </h6>
                                        <ul class="mb-0 small text-dark ps-3">
                                            <li>Đồng hồ sẽ bắt đầu đếm ngược ngay khi bạn bấm nút.</li>
                                            <li>Hệ thống sẽ tự động nộp bài khi hết thời gian.</li>
                                        </ul>
                                    </div>
                                    <a href="#" id="start-quiz-btn" class="btn btn-quiz-start w-100">
                                        BẮT ĐẦU LÀM BÀI <i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                </div>
                                <div id="quiz-completed-msg" class="d-none">
                                    <div class="text-center p-4 rounded-4 mb-3" style="background:#dcfce7;color:#14532d;">
                                        <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                                        <h5 class="fw-bold mb-1">Hoàn thành!</h5>
                                        <p class="mb-0 small">Bạn đã nộp bài kiểm tra này thành công.</p>
                                    </div>
                                    <a href="#" id="review-quiz-btn"
                                        class="btn btn-success rounded-pill w-100 py-3 fw-bold">
                                        <i class="fas fa-search me-2"></i> Xem chi tiết bài làm
                                    </a>
                                </div>
                            @else
                                <div class="quiz-notice mb-4" style="background:#eff6ff;border-color:#bfdbfe;">
                                    <h6 class="fw-bold mb-2 text-primary" style="font-size:13px;">
                                        <i class="fas fa-info-circle me-2"></i>Khu vực Quản lý
                                    </h6>
                                    <p class="mb-0 small text-dark">Bạn có thể vào trang soạn thảo để thêm / sửa / xóa câu
                                        hỏi.</p>
                                </div>
                                <a href="#" id="manage-quiz-btn" class="btn btn-quiz-start w-100">
                                    <i class="fas fa-cog me-2"></i> VÀO TRANG SOẠN CÂU HỎI
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- ══ FOOTER NAV ══ --}}
                    <div class="footer-nav d-none" id="nav-footer">
                        <button class="btn-footer-nav" id="btn-prev" disabled>
                            <i class="fas fa-arrow-left me-1"></i>Bài trước
                        </button>
                        <button class="btn btn-complete-lesson d-none" id="btn-complete">
                            <i class="fas fa-check-circle me-1"></i> Hoàn thành
                        </button>
                        <button class="btn-footer-nav" id="btn-next" disabled>
                            Bài tiếp <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    </div>

                </div>{{-- /content-card --}}
            </div>

        </div>{{-- /row --}}
    </div>{{-- /page-wrapper --}}

    {{-- Mobile FAB --}}
    <button id="btn-open-sidebar" aria-label="Mở danh sách bài học" title="Danh sách bài học">
        <i class="fas fa-list"></i>
    </button>

    @include('courses.partials.modals')
    @include('courses.partials.scripts')

    <script>
        (function() {
            // Clone desktop sidebar content into mobile drawer
            var desktopAccordion = document.querySelector('.sidebar-scroll .accordion');
            var mobileContent = document.getElementById('mobile-sidebar-content');
            if (desktopAccordion && mobileContent) {
                var clone = desktopAccordion.cloneNode(true);
                // Give the cloned accordion a different id to avoid conflicts
                clone.id = 'courseAccordionMobile';
                clone.querySelectorAll('[data-bs-parent]').forEach(function(el) {
                    el.setAttribute('data-bs-parent', '#courseAccordionMobile');
                });
                mobileContent.appendChild(clone);
            }

            var drawer = document.getElementById('mobile-sidebar-drawer');
            var overlay = document.getElementById('mobile-sidebar-overlay');
            var btnOpen = document.getElementById('btn-open-sidebar');
            var btnClose = document.getElementById('btn-close-sidebar');

            function openDrawer() {
                drawer.classList.add('open');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeDrawer() {
                drawer.classList.remove('open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (btnOpen) btnOpen.addEventListener('click', openDrawer);
            if (btnClose) btnClose.addEventListener('click', closeDrawer);
            if (overlay) overlay.addEventListener('click', closeDrawer);

            // Close on lesson/assignment/quiz click inside mobile drawer
            if (mobileContent) {
                mobileContent.addEventListener('click', function(e) {
                    var target = e.target.closest('.lesson-item, .assignment-item, .quiz-item');
                    if (!target) return;

                    e.preventDefault();
                    var type = target.classList.contains('assignment-item') ? 'assignment' :
                        (target.classList.contains('quiz-item') ? 'quiz' : 'lesson');
                    var id = target.getAttribute('data-id');
                    var selector = type === 'assignment'
                        ? `.sidebar-scroll .assignment-item[data-id="${id}"]`
                        : (type === 'quiz'
                            ? `.sidebar-scroll .quiz-item[data-id="${id}"]`
                            : `.sidebar-scroll .lesson-item[data-id="${id}"]`);
                    var desktopTarget = document.querySelector(selector);
                    if (desktopTarget) desktopTarget.click();
                    closeDrawer();
                });
            }
        })();
    </script>
@endsection
