@extends('layouts.app')

@section('title', $course->title)

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap');

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        :root {
            --blue-50: #eff6ff;
            --blue-100: #dbeafe;
            --blue-500: #3b82f6;
            --blue-600: #2563eb;
            --blue-700: #1d4ed8;
            --blue-900: #1e3a8a;

            --amber-50: #fffbeb;
            --amber-100: #fef3c7;
            --amber-400: #fbbf24;
            --amber-500: #f59e0b;
            --amber-600: #d97706;
            --amber-800: #92400e;

            --purple-50: #f5f3ff;
            --purple-100: #ede9fe;
            --purple-500: #8b5cf6;
            --purple-600: #7c3aed;
            --purple-800: #5b21b6;

            --green-50: #f0fdf4;
            --green-100: #dcfce7;
            --green-500: #22c55e;
            --green-600: #16a34a;
            --green-800: #166534;
            --green-900: #14532d;

            --red-50: #fef2f2;
            --red-100: #fee2e2;
            --red-700: #b91c1c;
            --red-800: #991b1b;

            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-900: #111827;

            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-full: 9999px;

            --shadow-sm: 0 1px 3px rgba(0, 0, 0, .06), 0 1px 2px rgba(0, 0, 0, .04);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, .06), 0 1px 4px rgba(0, 0, 0, .04);
            --shadow-lg: 0 10px 32px rgba(0, 0, 0, .10), 0 2px 8px rgba(0, 0, 0, .05);
        }

        body,
        .card,
        .card-body {
            font-family: 'Be Vietnam Pro', sans-serif;
        }

        /* ── PAGE WRAPPER ── */
        .page-wrapper {
            background: #f0f4f8;
            min-height: 100vh;
            padding: 14px 12px;
        }

        @media (min-width: 768px) {
            .page-wrapper {
                padding: 20px 18px;
            }
        }

        /* ── TOP HEADER CARD ── */
        .header-card {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);
            border-radius: var(--radius-xl);
            padding: 18px 20px;
            margin-bottom: 16px;
            box-shadow: 0 8px 32px rgba(37, 99, 235, .28);
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .header-card::before {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .06);
            pointer-events: none;
        }

        .header-card::after {
            content: '';
            position: absolute;
            bottom: -60px;
            left: 40%;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .04);
            pointer-events: none;
        }

        @media (min-width: 992px) {
            .header-card {
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
            color: #fff;
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
                font-size: 1.35rem;
            }
        }

        .header-teacher {
            font-size: 13px;
            color: rgba(255, 255, 255, .78);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Progress */
        .progress-wrap {
            margin-top: 12px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 6px;
            color: rgba(255, 255, 255, .8);
        }

        .progress-label span:last-child {
            color: #fff;
        }

        .progress-track {
            height: 8px;
            background: rgba(255, 255, 255, .22);
            border-radius: var(--radius-full);
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: var(--radius-full);
            background: linear-gradient(90deg, #86efac, #22c55e);
            transition: width .7s cubic-bezier(.4, 0, .2, 1);
            box-shadow: 0 0 8px rgba(34, 197, 94, .5);
        }

        /* ── TOOLBAR (teacher) ── */
        .toolbar {
            display: flex;
            align-items: center;
            gap: 5px;
            background: rgba(255, 255, 255, .14);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: var(--radius-md);
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
            font-weight: 700;
            padding: 7px 11px;
            border-radius: var(--radius-sm);
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
            background: rgba(255, 255, 255, .9);
            color: var(--blue-700);
        }

        .tool-btn.blue:hover {
            background: #fff;
        }

        .tool-btn.amber {
            background: var(--amber-100);
            color: var(--amber-800);
        }

        .tool-btn.amber:hover {
            background: var(--amber-400);
            color: #fff;
        }

        .tool-btn.purple {
            background: var(--purple-100);
            color: var(--purple-800);
        }

        .tool-btn.purple:hover {
            background: var(--purple-500);
            color: #fff;
        }

        .tool-btn.teal {
            background: var(--green-100);
            color: var(--green-800);
        }

        .tool-btn.teal:hover {
            background: var(--green-500);
            color: #fff;
        }

        /* ── MOBILE SIDEBAR ── */
        #mobile-sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .5);
            z-index: 1099;
            backdrop-filter: blur(3px);
        }

        #mobile-sidebar-overlay.active {
            display: block;
        }

        #mobile-sidebar-drawer {
            position: fixed;
            top: 0;
            left: 0;
            width: min(88vw, 340px);
            height: 100%;
            background: #fff;
            z-index: 1100;
            transform: translateX(-110%);
            transition: transform .28s cubic-bezier(.4, 0, .2, 1);
            overflow-y: auto;
            border-radius: 0 var(--radius-xl) var(--radius-xl) 0;
            box-shadow: var(--shadow-lg);
        }

        #mobile-sidebar-drawer.open {
            transform: translateX(0);
        }

        .mobile-drawer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            border-bottom: 1px solid var(--gray-100);
            background: #fff;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        /* FAB */
        #btn-open-sidebar {
            position: fixed;
            bottom: 20px;
            left: 16px;
            z-index: 1050;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--blue-600);
            color: #fff;
            border: none;
            box-shadow: 0 6px 24px rgba(37, 99, 235, .42);
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .2s;
        }

        #btn-open-sidebar:hover {
            background: var(--blue-700);
            transform: scale(1.06);
        }

        @media (min-width: 768px) {
            #btn-open-sidebar {
                display: none !important;
            }
        }

        /* ── DESKTOP SIDEBAR ── */
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
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .sidebar-scroll {
            max-height: calc(100vh - 120px);
            overflow-y: auto;
        }

        .sidebar-scroll::-webkit-scrollbar {
            width: 3px;
        }

        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: var(--gray-200);
            border-radius: 99px;
        }

        /* ── SIDEBAR HEADER ── */
        .sidebar-head {
            padding: 14px 16px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .sidebar-head-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--gray-500);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .sidebar-head-count {
            font-size: 11px;
            font-weight: 700;
            background: var(--blue-50);
            color: var(--blue-700);
            padding: 2px 8px;
            border-radius: var(--radius-full);
        }

        /* ── ACCORDION ── */
        .accordion-button {
            padding: 0;
            font-weight: 700;
            font-size: 14px;
            background: #fff;
            box-shadow: none !important;
        }

        .accordion-button:not(.collapsed) {
            background: var(--blue-50) !important;
            color: var(--blue-700);
        }

        .accordion-button:hover {
            background: var(--gray-50) !important;
        }

        .accordion-button:focus {
            box-shadow: none !important;
        }

        .accordion-item {
            border: none;
            border-bottom: 1px solid var(--gray-100);
        }

        .accordion-item:last-child {
            border-bottom: none;
        }

        /* Module header wrapper */
        .module-header-wrapper {
            display: flex;
            align-items: center;
            position: relative;
            transition: background .15s;
        }

        .module-header-wrapper:hover {
            background: var(--gray-50);
        }

        .module-title-block {
            padding: 12px 14px 12px 16px;
            flex: 1;
            min-width: 0;
        }

        .module-title-text {
            display: block;
            font-size: 13.5px;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1.4;
        }

        .module-meta {
            font-size: 11px;
            color: var(--blue-500);
            display: block;
            font-weight: 500;
            margin-top: 2px;
        }

        /* ── LESSON ITEM ── */
        .lesson-item-wrapper {
            background: #fff;
            border-bottom: 1px solid var(--gray-100);
            border-left: 3px solid transparent;
            transition: all .15s;
            cursor: pointer;
        }

        .lesson-item-wrapper:hover {
            background: var(--blue-50) !important;
            border-left-color: var(--blue-500);
        }

        .lesson-item-wrapper.active {
            background: var(--blue-50) !important;
            border-left-color: var(--blue-600);
        }

        /* ── ASSIGNMENT ITEM ── */
        .assignment-item-wrapper {
            background: var(--amber-50) !important;
            border-bottom: 1px solid #fde68a;
            border-left: 3px solid var(--amber-400);
            transition: all .15s;
            cursor: pointer;
        }

        .assignment-item-wrapper:hover {
            background: var(--amber-100) !important;
            border-left-color: var(--amber-600);
        }

        .assignment-item-wrapper.active {
            background: var(--amber-100) !important;
            border-left-color: var(--amber-600);
        }

        .assignment-item-wrapper.submitted {
            background: var(--green-50) !important;
            border-left-color: var(--green-600);
            border-bottom-color: #bbf7d0;
        }

        /* ── QUIZ ITEM ── */
        .quiz-item-wrapper {
            background: var(--purple-50);
            border-bottom: 1px solid var(--purple-100);
            border-left: 3px solid transparent;
            transition: all .15s;
            cursor: pointer;
        }

        .quiz-item-wrapper:hover {
            background: var(--purple-100) !important;
            border-left-color: var(--purple-500);
        }

        .quiz-item-wrapper.active {
            background: var(--purple-100) !important;
            border-left-color: var(--purple-600);
        }

        .quiz-item-wrapper.completed {
            background: var(--green-50) !important;
            border-left-color: var(--green-600);
            border-bottom-color: #bbf7d0;
        }

        /* Shared item text */
        .lesson-name-text {
            font-size: 13.5px;
            color: var(--gray-900);
            font-weight: 500;
            line-height: 1.4;
        }

        .lesson-dur-text {
            font-size: 11px;
            color: var(--gray-400);
            margin-top: 2px;
        }

        /* Status pills */
        .sidebar-status-row {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 4px;
        }

        .sidebar-status-pill {
            align-items: center;
            border-radius: var(--radius-full);
            display: inline-flex;
            font-size: 10px;
            font-weight: 800;
            gap: 3px;
            line-height: 1;
            padding: 3px 7px;
            white-space: nowrap;
        }

        .sidebar-status-pill.done {
            background: var(--green-100);
            color: var(--green-800);
        }

        .sidebar-status-pill.pending {
            background: var(--gray-100);
            color: var(--gray-600);
        }

        .sidebar-status-pill.assignment {
            background: var(--amber-100);
            color: var(--amber-800);
        }

        .sidebar-status-pill.quiz {
            background: var(--purple-100);
            color: var(--purple-800);
        }

        .sidebar-status-pill.overdue {
            background: var(--red-100);
            color: var(--red-800);
        }

        /* Action buttons */
        .action-buttons {
            opacity: 0;
            transition: opacity .15s;
            flex-shrink: 0;
            padding-right: 6px;
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
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            font-size: 12px;
            transition: all .15s;
            text-decoration: none;
        }

        .btn-edit {
            color: var(--amber-500);
        }

        .btn-edit:hover {
            background: var(--amber-100);
        }

        .btn-delete {
            color: #ef4444;
        }

        .btn-delete:hover {
            background: var(--red-100);
        }

        /* ── CONTENT CARD ── */
        .content-card {
            background: #fff;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            display: flex;
            flex-direction: column;
        }

        #video-container iframe {
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        #external-link-container {
            background: linear-gradient(135deg, var(--blue-50), #f0fdf4);
            border-bottom: 1px solid var(--gray-200);
        }

        /* ── LESSON AREA ── */
        #lesson-content-area {
            padding: 20px 18px;
        }

        @media (min-width: 768px) {
            #lesson-content-area {
                padding: 32px 36px;
            }
        }

        .lesson-header-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 12px;
        }

        @media (min-width: 768px) {
            .lesson-header-title {
                font-size: 1.35rem;
                margin-bottom: 16px;
            }
        }

        .lesson-divider {
            border: none;
            border-top: 2px solid var(--gray-100);
            margin-bottom: 20px;
        }

        .lesson-body {
            font-size: 14.5px;
            line-height: 1.85;
            color: var(--gray-700);
        }

        @media (min-width: 768px) {
            .lesson-body {
                font-size: 15px;
            }
        }

        .lesson-ai-toolbar {
            align-items: center;
            background: var(--blue-50);
            border: 1px solid var(--blue-100);
            border-radius: var(--radius-md);
            display: none;
            gap: 10px;
            justify-content: space-between;
            margin: 0 18px 18px;
            padding: 12px 14px;
        }

        @media (min-width: 768px) {
            .lesson-ai-toolbar {
                margin: 0 36px 22px;
            }
        }

        .lesson-ai-toolbar.active {
            display: flex;
        }

        .lesson-ai-title {
            color: var(--gray-900);
            font-size: 13px;
            font-weight: 800;
            margin: 0;
        }

        .lesson-ai-subtitle {
            color: var(--gray-500);
            font-size: 12px;
            margin-top: 2px;
        }

        .lesson-ai-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: flex-end;
        }

        .lesson-ai-btn {
            align-items: center;
            background: #fff;
            border: 1px solid var(--blue-100);
            border-radius: 8px;
            color: var(--blue-700);
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            gap: 5px;
            min-height: 34px;
            padding: 7px 10px;
        }

        .lesson-ai-btn:hover {
            background: var(--blue-600);
            border-color: var(--blue-600);
            color: #fff;
        }

        .course-intro-card {
            background: linear-gradient(135deg, #fafbff, #f0f9ff);
            border: 1px solid #e0eaff;
            border-radius: var(--radius-md);
            padding: 20px;
        }

        @media (min-width: 768px) {
            .course-intro-card {
                padding: 28px 32px;
            }
        }

        /* Welcome guide */
        .welcome-guide {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 18px;
        }

        .welcome-guide-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 14px;
        }

        @media (max-width: 991.98px) {
            .welcome-guide-grid {
                grid-template-columns: 1fr;
            }
        }

        .welcome-guide-item {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 14px 12px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .welcome-guide-icon {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 15px;
        }

        /* ── ATTACHMENT ── */
        .attachment-box {
            margin: 0 18px 20px;
            background: var(--gray-50);
            border: 1.5px dashed var(--gray-300);
            border-radius: var(--radius-md);
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
                margin: 0 36px 24px;
                flex-wrap: nowrap;
            }
        }

        .attachment-box:hover {
            border-color: var(--blue-500);
            background: var(--blue-50);
        }

        /* ── ASSIGNMENT AREA ── */
        #assignment-content-area {
            padding: 20px 18px;
            background: #fffdf5;
        }

        @media (min-width: 768px) {
            #assignment-content-area {
                padding: 32px 36px;
            }
        }

        .assignment-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--gray-900);
        }

        @media (min-width: 768px) {
            .assignment-title {
                font-size: 1.3rem;
            }
        }

        .due-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--red-50);
            color: var(--red-700);
            font-size: 12px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: var(--radius-full);
            border: 1px solid #fecaca;
        }

        .instructions-box {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 18px 20px;
            line-height: 1.8;
            color: var(--gray-700);
            font-size: 14px;
        }

        @media (min-width: 768px) {
            .instructions-box {
                padding: 22px 26px;
            }
        }

        .submission-dropzone {
            border: 2px dashed var(--amber-400);
            background: var(--amber-50);
            border-radius: var(--radius-md);
            padding: 20px 16px;
            transition: all .3s;
        }

        .submission-dropzone:hover {
            border-color: var(--amber-500);
            background: var(--amber-100);
        }

        .submitted-file-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
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
            border-radius: var(--radius-md);
            background: var(--green-50);
            border: 1px solid #bbf7d0;
            padding: 16px 18px;
        }

        /* ── QUIZ AREA ── */
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
            color: var(--gray-900);
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
            border-radius: var(--radius-md);
            padding: 14px 16px;
            text-align: center;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            flex: 1;
            min-width: 100px;
        }

        @media (min-width: 768px) {
            .quiz-stat-card {
                padding: 18px 22px;
                min-width: 130px;
            }
        }

        .quiz-stat-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--gray-400);
            margin-bottom: 6px;
        }

        .quiz-stat-value {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--purple-600);
        }

        @media (min-width: 768px) {
            .quiz-stat-value {
                font-size: 1.6rem;
            }
        }

        .btn-quiz-start {
            background: linear-gradient(135deg, var(--purple-600), var(--purple-500));
            border: none;
            padding: 13px 28px;
            font-size: 14px;
            font-weight: 700;
            border-radius: var(--radius-full);
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
            background: var(--amber-50);
            border: 1px solid #fde68a;
            border-radius: var(--radius-md);
            padding: 16px 18px;
            text-align: left;
            max-width: 480px;
            width: 100%;
        }

        /* ── FOOTER NAV ── */
        .footer-nav {
            background: #fff;
            border-top: 1px solid var(--gray-100);
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
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 13px;
            padding: 8px 14px;
            border: 1.5px solid var(--gray-200);
            background: #fff;
            color: var(--gray-700);
            transition: all .18s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-footer-nav:hover:not(:disabled) {
            border-color: var(--blue-500);
            color: var(--blue-600);
            background: var(--blue-50);
        }

        .btn-footer-nav:disabled {
            opacity: .4;
            cursor: not-allowed;
        }

        .btn-complete-lesson {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 700;
            font-size: 13px;
            padding: 8px 18px;
            color: #fff;
            box-shadow: 0 4px 14px rgba(34, 197, 94, .28);
            transition: all .3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-complete-lesson:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(34, 197, 94, .36);
            color: #fff;
        }

        .btn-complete-lesson.completed-state {
            background: var(--green-50);
            color: var(--green-800);
            box-shadow: none;
            border: 1.5px solid #bbf7d0;
        }

        .btn-complete-lesson.completed-state:hover {
            transform: none;
            box-shadow: none;
        }

        /* ── TEACHER PANEL ── */
        .teacher-mode-panel {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
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
            color: var(--gray-500);
            font-size: 12px;
            margin-top: 3px;
        }

        .teacher-mode-toggle {
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            display: inline-flex;
            gap: 3px;
            padding: 3px;
        }

        .teacher-mode-btn {
            background: transparent;
            border: 0;
            border-radius: 6px;
            color: var(--gray-500);
            font-size: 12px;
            font-weight: 700;
            padding: 7px 10px;
        }

        .teacher-mode-btn.active {
            background: #fff;
            box-shadow: var(--shadow-sm);
            color: var(--blue-700);
        }

        .teacher-quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .teacher-quick-actions .tool-btn {
            background: var(--gray-50);
            color: var(--gray-700);
            border: 1px solid var(--gray-200);
        }

        .teacher-quick-actions .tool-btn:hover {
            background: var(--gray-100);
        }

        .teacher-preview-banner {
            align-items: center;
            background: var(--blue-50);
            border: 1px solid var(--blue-100);
            border-radius: var(--radius-sm);
            color: #1e40af;
            display: none;
            font-size: 13px;
            font-weight: 700;
            gap: 8px;
            margin-top: 12px;
            padding: 10px 12px;
        }

        /* ── DASHBOARD GRID ── */
        .course-dashboard-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: 16px;
        }

        @media (max-width: 991.98px) {
            .course-dashboard-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .course-dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .course-dashboard-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 16px;
            box-shadow: var(--shadow-sm);
        }

        .course-dashboard-label {
            color: var(--gray-500);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .05em;
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
            color: var(--gray-500);
            font-size: 12px;
            margin-top: 5px;
        }

        /* ── SORTABLE ── */
        .drag-handle {
            color: var(--gray-300);
            cursor: grab;
            font-size: 13px;
            padding-left: 8px;
            transition: color .15s;
        }

        .drag-handle:hover {
            color: var(--gray-500);
        }

        .sortable-ghost {
            opacity: .45;
        }

        .reorder-toast {
            background: var(--green-50);
            border: 1px solid #bbf7d0;
            border-radius: var(--radius-sm);
            color: var(--green-800);
            display: none;
            font-size: 12px;
            font-weight: 800;
            margin: 0 0 10px;
            padding: 9px 11px;
        }

        .reorder-toast.show {
            display: block;
        }

        /* ── PREVIEW MODE ── */
        .preview-student-mode .toolbar,
        .preview-student-mode .teacher-quick-actions,
        .preview-student-mode .course-dashboard-grid,
        .preview-student-mode .action-buttons {
            display: none !important;
        }

        .preview-student-mode .teacher-preview-banner {
            display: flex;
        }

        /* ── MISC ── */
        .text-truncate-custom {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
            min-width: 0;
        }

        .col-md-4.col-lg-3,
        .desktop-sidebar-wrap,
        .sidebar-inner-card {
            min-width: 0;
            max-width: 100%;
            overflow: hidden;
        }

        .modal-backdrop {
            z-index: 1050 !important;
        }

        .modal {
            z-index: 1060 !important;
        }

        @media (min-width: 768px) {

            #mobile-sidebar-drawer,
            #mobile-sidebar-overlay {
                display: none !important;
            }
        }

        @media (max-width: 767.98px) {

            .teacher-mode-row {
                align-items: stretch;
                flex-direction: column;
            }

            .lesson-ai-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .teacher-mode-toggle {
                width: 100%;
            }

            .lesson-ai-actions {
                justify-content: flex-start;
            }

            .teacher-mode-btn {
                flex: 1;
            }
        }

        /* Reference-based course detail redesign */
        .page-wrapper {
            background: #f4f6fb;
            margin: 0 auto;
            max-width: 1760px;
            padding: 28px 22px 76px;
        }

        .header-card.course-ref-header {
            background:
                linear-gradient(135deg, rgba(255, 255, 255, .96) 0%, rgba(255, 255, 255, .96) 56%, rgba(234, 241, 255, .96) 100%),
                linear-gradient(120deg, rgba(47, 111, 237, .08), transparent 42%);
            border: 1px solid rgba(215, 224, 240, .95);
            border-radius: 24px;
            box-shadow: 0 18px 48px rgba(32, 38, 52, .08);
            color: #202634;
            display: grid;
            gap: 28px;
            grid-template-columns: minmax(0, 1fr);
            margin-bottom: 24px;
            overflow: hidden;
            padding: 30px;
            position: relative;
        }

        .header-card.course-ref-header::before {
            background:
                linear-gradient(90deg, rgba(47, 111, 237, .075) 1px, transparent 1px),
                linear-gradient(0deg, rgba(47, 111, 237, .055) 1px, transparent 1px);
            background-size: 36px 36px;
            content: '';
            inset: 0;
            mask-image: linear-gradient(90deg, transparent 0%, #000 68%, #000 100%);
            opacity: .75;
            pointer-events: none;
            position: absolute;
        }

        .header-card.course-ref-header::after {
            background: linear-gradient(135deg, rgba(47, 111, 237, .18), rgba(111, 66, 193, .12));
            border-radius: 999px;
            content: '';
            filter: blur(8px);
            height: 180px;
            opacity: .42;
            pointer-events: none;
            position: absolute;
            right: -80px;
            top: -80px;
            width: 180px;
        }

        @media (min-width: 992px) {
            .header-card.course-ref-header {
                align-items: center;
                grid-template-columns: minmax(0, 1fr) minmax(420px, 520px);
            }
        }

        .course-ref-header > * {
            position: relative;
            z-index: 1;
        }

        .course-ref-badges {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
        }

        .course-ref-badge {
            background: #2f6fed;
            border-radius: 7px;
            color: #fff;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            gap: 6px;
            line-height: 1;
            padding: 6px 9px;
        }

        .course-ref-badge.light {
            background: #fff;
            border: 1px solid #e7eaf2;
            color: #202634;
        }

        .course-ref-header .header-course-title {
            color: #202634;
            font-size: clamp(1.35rem, 2.1vw, 1.9rem);
            font-weight: 900;
            letter-spacing: -.025em;
            margin-bottom: 8px;
        }

        .course-ref-header .header-teacher {
            color: #6b7386;
            font-size: 14px;
            margin-bottom: 14px;
        }

        .course-ref-description {
            color: #202634;
            font-size: 15px;
            font-weight: 600;
            line-height: 1.7;
            margin: 0 0 18px;
            max-width: 980px;
        }

        .course-ref-header .progress-wrap {
            margin-top: 0;
            max-width: none;
        }

        .course-ref-header .progress-label {
            color: #6b7386;
            font-size: 12.5px;
        }

        .course-ref-header .progress-label span:last-child {
            color: #202634;
        }

        .course-ref-header .progress-track {
            background: #e4e9f5;
            height: 10px;
        }

        .course-ref-header .progress-fill {
            background: #2f6fed;
            box-shadow: none;
        }

        .course-ref-side {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .course-ref-stats {
            background: rgba(255, 255, 255, .92);
            border: 1px solid #e7eaf2;
            border-radius: 18px;
            box-shadow: 0 14px 34px rgba(20, 30, 60, .07);
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            padding: 18px 10px;
            text-align: center;
        }

        .course-ref-stat strong {
            color: #202634;
            display: block;
            font-size: 20px;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 7px;
        }

        .course-ref-stat span {
            color: #6b7386;
            display: block;
            font-size: 12px;
            font-weight: 700;
        }

        .course-ref-header .toolbar {
            background: transparent;
            border: 0;
            box-shadow: none;
            gap: 8px;
            margin-top: 0;
            padding: 0;
        }

        .course-ref-header .tool-btn {
            border-radius: 999px;
            min-height: 36px;
            padding: 9px 12px;
        }

        .course-ref-grid {
            --bs-gutter-x: 1.5rem;
            --bs-gutter-y: 1.5rem;
        }

        @media (min-width: 1200px) {
            .course-ref-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(460px, 540px);
            }

            .course-ref-grid > [class*="col-"] {
                max-width: none;
                width: auto;
            }
        }

        .content-card,
        .sidebar-inner-card {
            background: #fff;
            border: 1px solid #e7eaf2;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(20, 30, 60, .04);
        }

        .desktop-sidebar-wrap {
            top: 88px;
        }

        .course-sidebar-stack {
            display: grid;
            gap: 18px;
        }

        @media (min-width: 768px) {
            .desktop-sidebar-wrap.course-sidebar-stack {
                max-height: none;
                overflow: visible;
                padding-right: 4px;
                position: static;
                top: auto;
            }
        }

        @media (min-width: 768px) {
            .course-sidebar-column {
                align-self: flex-start;
                max-height: calc(100vh - 104px);
                overflow-y: auto;
                padding-right: 6px;
                position: sticky;
                top: 88px;
            }

            .course-sidebar-column::-webkit-scrollbar {
                width: 4px;
            }

            .course-sidebar-column::-webkit-scrollbar-track {
                background: transparent;
            }

            .course-sidebar-column::-webkit-scrollbar-thumb {
                background: #d7e0f0;
                border-radius: 999px;
            }
        }

        .sidebar-inner-card {
            overflow: hidden;
        }

        @media (min-width: 768px) {
            .course-outline-card {
                position: static;
                z-index: 3;
            }
        }

        .sidebar-head {
            align-items: stretch;
            background: #fff;
            border-bottom: 1px solid #e7eaf2;
            display: flex;
            flex-direction: column;
            gap: 9px;
            padding: 18px;
        }

        .sidebar-head-row {
            align-items: center;
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }

        .sidebar-head-title {
            color: #202634;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 0;
            text-transform: none;
        }

        .sidebar-head-count {
            background: #eaf1ff;
            color: #2f6fed;
            font-size: 12px;
            padding: 5px 9px;
        }

        .course-sidebar-progress {
            color: #6b7386;
            font-size: 12.5px;
            font-weight: 700;
        }

        .course-sidebar-progress-track {
            background: #e4e9f5;
            border-radius: 999px;
            height: 8px;
            overflow: hidden;
        }

        .course-sidebar-progress-fill {
            background: #2f6fed;
            border-radius: inherit;
            display: block;
            height: 100%;
        }

        .sidebar-scroll {
            max-height: calc(100vh - 210px);
            padding: 0;
            overflow-y: auto;
        }

        @media (min-width: 1200px) {
            .sidebar-scroll {
                max-height: min(62vh, calc(100vh - 280px));
            }
        }

        .course-side-card {
            background: #fff;
            border: 1px solid #e7eaf2;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(20, 30, 60, .04);
            padding: 18px;
        }

        .course-side-card__title {
            align-items: center;
            color: #202634;
            display: flex;
            font-size: 18px;
            font-weight: 900;
            gap: 10px;
            margin: 0 0 14px;
        }

        .course-side-card__title i {
            color: #2f6fed;
            width: 20px;
        }

        .course-todo-list,
        .course-info-list {
            display: grid;
            gap: 0;
        }

        .course-todo-item {
            align-items: flex-start;
            border-bottom: 1px solid #e7eaf2;
            display: flex;
            gap: 12px;
            padding: 13px 0;
            text-decoration: none;
        }

        .course-todo-item:first-child {
            padding-top: 0;
        }

        .course-todo-item:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .course-todo-icon {
            align-items: center;
            border-radius: 999px;
            display: inline-flex;
            flex-shrink: 0;
            height: 30px;
            justify-content: center;
            width: 30px;
        }

        .course-todo-icon.lesson {
            background: #eaf1ff;
            color: #2f6fed;
        }

        .course-todo-icon.assignment {
            background: #fdedd8;
            color: #d97a2e;
        }

        .course-todo-icon.quiz {
            background: #ede7f9;
            color: #6f42c1;
        }

        .course-todo-icon.exam {
            background: #fbe0e0;
            color: #d64545;
        }

        .course-todo-title {
            color: #202634;
            font-size: 14px;
            font-weight: 850;
            line-height: 1.35;
        }

        .course-todo-meta {
            color: #6b7386;
            font-size: 12.5px;
            line-height: 1.45;
            margin-top: 2px;
        }

        .course-info-row {
            align-items: center;
            border-bottom: 1px solid #e7eaf2;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            padding: 11px 0;
        }

        .course-info-row:first-child {
            padding-top: 0;
        }

        .course-info-row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .course-info-label {
            color: #6b7386;
            font-size: 13.5px;
            font-weight: 700;
        }

        .course-info-value {
            color: #202634;
            font-size: 13.5px;
            font-weight: 900;
            text-align: right;
        }

        .sidebar-inner-card .accordion-button {
            padding-right: 18px;
        }

        .sidebar-inner-card .accordion-button::after {
            margin-left: 12px;
            margin-right: 4px;
            flex-shrink: 0;
        }

        /* Final polish: status, active state, mobile, teacher actions, empty states */
        .lesson-item-wrapper.active,
        .assignment-item-wrapper.active,
        .quiz-item-wrapper.active {
            box-shadow: inset 0 0 0 1px rgba(47, 111, 237, .16), 0 8px 18px rgba(47, 111, 237, .08);
        }

        .lesson-item-wrapper.active .lesson-name-text,
        .assignment-item-wrapper.active .lesson-name-text,
        .quiz-item-wrapper.active .lesson-name-text {
            font-weight: 900;
        }

        .sidebar-status-row {
            gap: 6px;
            margin-top: 6px;
        }

        .sidebar-status-pill {
            border: 1px solid transparent;
            font-size: 10.5px;
            font-weight: 850;
            letter-spacing: 0;
            min-height: 22px;
            padding: 5px 8px;
        }

        .sidebar-status-pill.done {
            background: #dcf5ea;
            border-color: #b9ead8;
            color: #0f7a52;
        }

        .sidebar-status-pill.pending {
            background: #f4f6fb;
            border-color: #e7eaf2;
            color: #6b7386;
        }

        .sidebar-status-pill.assignment {
            background: #fdedd8;
            border-color: #f8d6a8;
            color: #b6740a;
        }

        .sidebar-status-pill.quiz {
            background: #ede7f9;
            border-color: #ded2f3;
            color: #5e35b1;
        }

        .sidebar-status-pill.overdue {
            background: #fbe0e0;
            border-color: #f5caca;
            color: #b02a37;
        }

        .teacher-mode-panel {
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(20, 30, 60, .04);
        }

        .teacher-quick-actions {
            background: #f8fbff;
            border: 1px solid #e4efff;
            border-radius: 14px;
            padding: 10px;
        }

        .teacher-quick-actions .tool-btn {
            border-radius: 999px;
            min-height: 36px;
            padding: 9px 12px;
        }

        .course-empty-state {
            padding: 34px 22px;
            text-align: center;
        }

        .course-empty-state__icon {
            align-items: center;
            background: #f4f6fb;
            border: 1px solid #e7eaf2;
            border-radius: 16px;
            color: #9aa3b5;
            display: inline-flex;
            font-size: 22px;
            height: 58px;
            justify-content: center;
            margin-bottom: 12px;
            width: 58px;
        }

        .course-empty-state__title {
            color: #202634;
            font-size: 15px;
            font-weight: 900;
            margin-bottom: 4px;
        }

        .course-empty-state__desc {
            color: #6b7386;
            font-size: 13px;
            line-height: 1.55;
            margin: 0;
        }

        #mobile-sidebar-drawer {
            width: min(92vw, 430px);
        }

        .mobile-drawer-header {
            border-bottom-color: #e7eaf2;
            padding: 16px 18px;
        }

        #btn-open-sidebar {
            border-radius: 18px;
            bottom: 18px;
            box-shadow: 0 12px 28px rgba(47, 111, 237, .32);
            height: 54px;
            left: 18px;
            width: 54px;
        }

        @media (max-width: 767.98px) {
            .header-card.course-ref-header {
                border-radius: 18px;
                padding: 20px;
            }

            .course-ref-header .toolbar,
            .teacher-quick-actions {
                display: grid;
                grid-template-columns: 1fr;
            }

            .course-ref-header .tool-btn,
            .teacher-quick-actions .tool-btn {
                justify-content: center;
                width: 100%;
            }

            .footer-nav {
                align-items: stretch;
                bottom: 10px;
                flex-direction: column;
                margin: 10px;
                padding: 12px;
            }

            .footer-nav .btn,
            .footer-nav button {
                justify-content: center;
                width: 100%;
            }

            #lesson-content-area,
            #assignment-content-area,
            #quiz-content-area {
                padding: 20px 16px;
            }
        }

        .accordion-item {
            border-bottom: 1px solid #e7eaf2;
        }

        .accordion-item:last-child {
            border-bottom: 0;
        }

        .module-header-wrapper {
            background: #fff;
        }

        .module-header-wrapper:hover {
            background: #fafbfe;
        }

        .accordion-button:not(.collapsed) {
            background: #fff !important;
        }

        .module-title-block {
            padding: 16px;
        }

        .lesson-item-wrapper,
        .assignment-item-wrapper,
        .quiz-item-wrapper {
            border-bottom: 1px solid #e7eaf2 !important;
            border-left-width: 3px !important;
            border-radius: 0;
        }

        .lesson-item-wrapper {
            border-left-color: transparent !important;
        }

        .lesson-item-wrapper:hover,
        .lesson-item-wrapper.active {
            background: #eaf1ff !important;
            border-left-color: #2f6fed !important;
        }

        .assignment-item-wrapper {
            background: #fff !important;
            border-left-color: transparent !important;
        }

        .assignment-item-wrapper:hover,
        .assignment-item-wrapper.active {
            background: #fff7ed !important;
            border-left-color: #d97a2e !important;
        }

        .assignment-item-wrapper.submitted {
            border-left-color: #1a9e6e !important;
        }

        .quiz-item-wrapper {
            background: #fff !important;
            border-left-color: transparent !important;
        }

        .quiz-item-wrapper:hover,
        .quiz-item-wrapper.active {
            background: #f5f3ff !important;
            border-left-color: #6f42c1 !important;
        }

        .quiz-item-wrapper.completed {
            border-left-color: #1a9e6e !important;
        }

        .lesson-current-head {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 22px;
        }

        .lesson-current-badge {
            background: #2f6fed;
            border-radius: 7px;
            color: #fff;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 8px;
            padding: 5px 9px;
        }

        .lesson-header-title {
            color: #202634;
            font-size: clamp(1.35rem, 2vw, 1.75rem);
            font-weight: 900;
            letter-spacing: -.025em;
            margin-bottom: 5px;
        }

        .lesson-current-meta,
        .small-text {
            color: #6b7386;
            font-size: 13px;
        }

        .lesson-duration-box {
            color: #6b7386;
            flex-shrink: 0;
            font-size: 12.5px;
            text-align: right;
        }

        .lesson-duration-box strong {
            align-items: center;
            color: #202634;
            display: inline-flex;
            font-size: 14px;
            gap: 6px;
            margin-top: 4px;
        }

        .lesson-duration-box i {
            color: #2f6fed;
        }

        .attachment-box {
            background: #f8fbff;
            border: 1px solid #e4efff;
            border-radius: 14px;
            display: block;
            margin: 0 0 22px;
            padding: 16px;
        }

        .attachment-box__head {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 13px;
        }

        .attachment-box__head h6 {
            align-items: center;
            color: #202634;
            display: flex;
            font-size: 14px;
            font-weight: 900;
            gap: 9px;
            margin: 0;
        }

        .attachment-box__head i {
            color: #2f6fed;
        }

        .attachment-item {
            align-items: center;
            background: #fff;
            border: 1px solid #e7eaf2;
            border-radius: 12px;
            display: flex;
            gap: 12px;
            margin-bottom: 0;
            padding: 12px;
        }

        .attachment-icon {
            align-items: center;
            border-radius: 10px;
            display: flex;
            flex-shrink: 0;
            font-size: 18px;
            height: 42px;
            justify-content: center;
            width: 42px;
        }

        .file-doc {
            background: #eaf1ff;
            color: #2f6fed;
        }

        .btn-primary-action {
            background: #2f6fed;
            border: 0;
            color: #fff;
            font-weight: 800;
        }

        .btn-primary-action:hover {
            background: #1d4fbf;
            color: #fff;
        }

        .btn-secondary-action {
            background: #fff;
            border: 1px solid #e7eaf2;
            color: #202634;
            font-weight: 800;
        }

        .btn-secondary-action:hover {
            background: #f4f6fb;
            color: #202634;
        }

        .lesson-body {
            color: #202634;
            font-size: 15px;
            line-height: 1.78;
        }

        .lesson-body pre {
            background: #111827;
            border-radius: 12px;
            color: #e5e7eb;
            overflow-x: auto;
            padding: 16px;
        }

        .lesson-body pre code {
            color: #e5e7eb;
        }

        .footer-nav {
            background: #fff;
            border: 1px solid #e7eaf2;
            border-radius: 16px;
            bottom: 14px;
            box-shadow: 0 6px 20px rgba(20, 30, 60, .10);
            margin: 12px;
            position: sticky;
            z-index: 5;
        }

        @media (max-width: 767.98px) {
            .page-wrapper {
                padding: 16px 12px 72px;
            }

            .course-ref-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .lesson-current-head,
            .attachment-item,
            .attachment-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .lesson-duration-box {
                text-align: left;
            }

            .attachment-actions .btn {
                width: 100%;
            }
        }
    </style>

    {{-- Mobile overlay + drawer --}}
    <div id="mobile-sidebar-overlay"></div>
    <div id="mobile-sidebar-drawer">
        <div class="mobile-drawer-header">
            <h6 class="mb-0 fw-bold small text-uppercase text-muted" style="font-size:11px;letter-spacing:.05em;">
                <i class="fas fa-list-ul me-2 text-primary"></i>Nội dung khóa học
            </h6>
            <button id="btn-close-sidebar" class="btn btn-sm btn-light border" type="button">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="mobile-sidebar-content"></div>
    </div>

    <div class="page-wrapper" id="course-page-wrapper">

        @php
            $courseLessonCount = $course->modules->sum(fn($module) => $module->lessons->count());
            $courseAssignmentCount = $course->modules->sum(
                fn($module) => $module->lessons->sum(fn($lesson) => $lesson->assignments->count()),
            );
            $courseQuizCount = $course->quizzes->filter(fn($quiz) => !str_contains(mb_strtolower($quiz->title), 'thi'))->count();
            $courseExamCount = $course->quizzes->filter(fn($quiz) => str_contains(mb_strtolower($quiz->title), 'thi'))->count();
            $isCourseManager = auth()->id() === $course->teacher_id || auth()->user()->role === 'admin';
            $allLessons = $course->modules->flatMap(fn($module) => $module->lessons);
            $allAssignments = $allLessons->flatMap(fn($lesson) => $lesson->assignments);
            $nextLesson = auth()->user()->role === 'student'
                ? $allLessons->first(fn($lesson) => !in_array($lesson->id, $completedLessonIds ?? []))
                : $allLessons->first();
            $nextAssignment = auth()->user()->role === 'student'
                ? $allAssignments->first(fn($assignment) => !isset($userSubmissions[$assignment->id]))
                : $allAssignments->first();
            $finalExam = $course->quizzes->first(fn($quiz) => str_contains(mb_strtolower($quiz->title), 'thi'));
            $regularQuizzes = $course->quizzes->filter(fn($quiz) => !str_contains(mb_strtolower($quiz->title), 'thi'));
            $nextQuiz = auth()->user()->role === 'student'
                ? $regularQuizzes->first(fn($quiz) => !isset($userQuizAttempts[$quiz->id]))
                : $regularQuizzes->first();
        @endphp

        {{-- ── HEADER ── --}}
        <div class="header-card course-ref-header">
            <div class="min-w-0">
                <div class="course-ref-badges">
                    <span class="course-ref-badge">
                        {{ auth()->user()->role === 'student' ? 'Đang học' : 'Khóa học' }}
                    </span>
                    <span class="course-ref-badge light">{{ strtoupper($course->status ?? 'published') }}</span>
                </div>
                <h1 class="header-course-title">{{ $course->title }}</h1>
                <p class="header-teacher">
                    <i class="fas fa-chalkboard-teacher"></i> {{ $course->teacher->name }}
                </p>
                @if (!empty($course->description))
                    <p class="course-ref-description">{{ \Illuminate\Support\Str::limit($course->description, 220) }}</p>
                @endif
                @if (auth()->user()->role === 'student')
                    <div class="progress-wrap">
                        <div class="progress-label">
                            <span>Tiến độ</span>
                            <span id="progress-text">{{ $completedCount }}/{{ $totalLessons }} bài &nbsp;·&nbsp;
                                {{ $progress }}%</span>
                        </div>
                        <div class="progress-track">
                            <div id="progress-bar" class="progress-fill" style="width: {{ $progress }}%;"></div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="course-ref-side">
                <div class="course-ref-stats">
                    <div class="course-ref-stat">
                        <strong>{{ $courseLessonCount }}</strong>
                        <span>Bài học</span>
                    </div>
                    <div class="course-ref-stat">
                        <strong>{{ $courseAssignmentCount }}</strong>
                        <span>Bài tập</span>
                    </div>
                    <div class="course-ref-stat">
                        <strong>{{ $courseQuizCount }}</strong>
                        <span>Kiểm tra</span>
                    </div>
                    <div class="course-ref-stat">
                        <strong>{{ $courseExamCount }}</strong>
                        <span>Bài thi</span>
                    </div>
                </div>

                @if ($isCourseManager)
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
        </div>

        @if ($isCourseManager)
            <div class="teacher-mode-panel" id="teacher-mode-panel">
                <div class="teacher-mode-row">
                    <div>
                        <h6 class="teacher-mode-title"><i class="fas fa-layer-group me-2 text-primary"></i>Chế độ giáo viên
                        </h6>
                        <div class="teacher-mode-subtitle">Quản lý nội dung, theo dõi tiến độ và thao tác nhanh.</div>
                    </div>
                    <div class="teacher-mode-toggle" role="group">
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
                    <span>Đang xem ở chế độ học sinh. Nút sửa/xóa đang ẩn.</span>
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
                    <button type="button" class="tool-btn amber" id="course-quality-check-btn"
                        data-url="{{ route('courses.quality-check', $course->id) }}">
                        <i class="fas fa-shield-halved"></i> Kiểm tra chất lượng
                    </button>
                </div>
            </div>
        @endif

        @if (auth()->user()->role !== 'student')
            <div class="course-dashboard-grid">
                <div class="course-dashboard-card">
                    <div class="course-dashboard-label"><i class="fas fa-users me-1 text-blue-500"></i> Học sinh</div>
                    <div class="course-dashboard-value">{{ $courseDashboard['students_count'] }}</div>
                    <div class="course-dashboard-sub">{{ $courseDashboard['modules_count'] }} chương ·
                        {{ $courseDashboard['lessons_count'] }} bài</div>
                </div>
                <div class="course-dashboard-card">
                    <div class="course-dashboard-label"><i class="fas fa-graduation-cap me-1"></i> Hoàn thành bài học</div>
                    <div class="course-dashboard-value">{{ $courseDashboard['lesson_completion_rate'] }}%</div>
                    <div class="course-dashboard-sub">Tỷ lệ toàn khóa</div>
                </div>
                <div class="course-dashboard-card">
                    <div class="course-dashboard-label"><i class="fas fa-file-signature me-1 text-warning"></i> Nộp bài tập
                    </div>
                    <div class="course-dashboard-value">{{ $courseDashboard['assignment_submission_rate'] }}%</div>
                    <div class="course-dashboard-sub">{{ $courseDashboard['pending_grades'] }} bài chờ chấm</div>
                </div>
                <div class="course-dashboard-card">
                    <div class="course-dashboard-label"><i class="fas fa-stopwatch me-1 text-purple"></i> Quiz</div>
                    <div class="course-dashboard-value">{{ $courseDashboard['quiz_completion_rate'] }}%</div>
                    <div class="course-dashboard-sub">Điểm TB:
                        {{ $courseDashboard['average_score'] !== null ? round($courseDashboard['average_score'], 1) : 'N/A' }}
                    </div>
                </div>
            </div>
        @endif

        {{-- ── MAIN GRID ── --}}
        <div class="course-ref-grid row align-items-start">

            {{-- DESKTOP SIDEBAR --}}
            <div class="col-md-4 col-xl-4 d-none d-md-block order-md-2 course-sidebar-column">
                <div class="desktop-sidebar-wrap course-sidebar-stack">
                    <div class="sidebar-inner-card course-outline-card">
                        <div class="sidebar-head">
                            <div class="sidebar-head-row">
                                <h6 class="sidebar-head-title">
                                    <i class="fas fa-list-ul text-primary"></i>Nội dung khóa học
                                </h6>
                                @if (auth()->user()->role === 'student')
                                    <span class="sidebar-head-count">{{ $progress }}%</span>
                                @endif
                            </div>
                            @if (auth()->user()->role === 'student')
                                <div class="course-sidebar-progress">
                                    <span id="sidebar-progress-text">Đã học {{ $completedCount }}/{{ $totalLessons }} bài · Tiến độ {{ $progress }}%</span>
                                </div>
                                <div class="course-sidebar-progress-track" aria-hidden="true">
                                    <span id="sidebar-progress-bar" class="course-sidebar-progress-fill" style="width: {{ $progress }}%;"></span>
                                </div>
                            @endif
                        </div>
                        @if ($isCourseManager)
                            <div id="reorder-toast" class="reorder-toast mx-3 mt-3">
                                <i class="fas fa-check me-1"></i>Đã lưu thứ tự nội dung
                            </div>
                        @endif
                        <div class="sidebar-scroll">
                            @include('courses.partials.sidebar')
                        </div>
                    </div>

                    <div class="course-side-card">
                        <h6 class="course-side-card__title">
                            <i class="far fa-check-square"></i> Việc cần làm
                        </h6>
                        <div class="course-todo-list">
                            @if ($nextLesson)
                                <a href="javascript:void(0)" class="course-todo-item"
                                    onclick="document.querySelector('.sidebar-scroll .lesson-item[data-id=&quot;{{ $nextLesson->id }}&quot;]')?.click()">
                                    <span class="course-todo-icon lesson"><i class="fas fa-play"></i></span>
                                    <span>
                                        <span class="course-todo-title">Học tiếp bài hiện tại</span>
                                        <span class="course-todo-meta">{{ $nextLesson->title }}</span>
                                    </span>
                                </a>
                            @endif

                            @if ($nextAssignment)
                                <a href="javascript:void(0)" class="course-todo-item"
                                    onclick="document.querySelector('.sidebar-scroll .assignment-item[data-id=&quot;{{ $nextAssignment->id }}&quot;]')?.click()">
                                    <span class="course-todo-icon assignment"><i class="fas fa-file-signature"></i></span>
                                    <span>
                                        <span class="course-todo-title">{{ auth()->user()->role === 'student' ? 'Nộp bài tập' : 'Bài tập trong khóa' }}</span>
                                        <span class="course-todo-meta">
                                            {{ $nextAssignment->title }}
                                            @if ($nextAssignment->due_date)
                                                · Hạn {{ $nextAssignment->due_date->format('d/m/Y') }}
                                            @endif
                                        </span>
                                    </span>
                                </a>
                            @endif

                            @if ($nextQuiz)
                                <a href="javascript:void(0)" class="course-todo-item"
                                    onclick="document.querySelector('.sidebar-scroll .quiz-item[data-id=&quot;{{ $nextQuiz->id }}&quot;]')?.click()">
                                    <span class="course-todo-icon quiz"><i class="fas fa-list-check"></i></span>
                                    <span>
                                        <span class="course-todo-title">{{ auth()->user()->role === 'student' ? 'Làm kiểm tra' : 'Kiểm tra trong khóa' }}</span>
                                        <span class="course-todo-meta">{{ $nextQuiz->title }} · {{ $nextQuiz->time_limit }} phút</span>
                                    </span>
                                </a>
                            @endif

                            @if ($finalExam)
                                <a href="javascript:void(0)" class="course-todo-item"
                                    onclick="document.querySelector('.sidebar-scroll .quiz-item[data-id=&quot;{{ $finalExam->id }}&quot;]')?.click()">
                                    <span class="course-todo-icon exam"><i class="fas fa-award"></i></span>
                                    <span>
                                        <span class="course-todo-title">Thi kết thúc học phần</span>
                                        <span class="course-todo-meta">{{ $finalExam->title }}</span>
                                    </span>
                                </a>
                            @endif

                            @if (!$nextLesson && !$nextAssignment && !$nextQuiz && !$finalExam)
                                <div class="course-todo-item">
                                    <span class="course-todo-icon lesson"><i class="fas fa-check"></i></span>
                                    <span>
                                        <span class="course-todo-title">Không còn việc cần làm</span>
                                        <span class="course-todo-meta">Nội dung hiện tại đã hoàn tất.</span>
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="course-side-card">
                        <h6 class="course-side-card__title">
                            <i class="fas fa-circle-info"></i> Thông tin khóa học
                        </h6>
                        <div class="course-info-list">
                            <div class="course-info-row">
                                <span class="course-info-label">Số bài học</span>
                                <span class="course-info-value">{{ $courseLessonCount }} bài</span>
                            </div>
                            <div class="course-info-row">
                                <span class="course-info-label">Bài tập</span>
                                <span class="course-info-value">{{ $courseAssignmentCount }} bài</span>
                            </div>
                            <div class="course-info-row">
                                <span class="course-info-label">Bài kiểm tra</span>
                                <span class="course-info-value">{{ $courseQuizCount }} bài</span>
                            </div>
                            <div class="course-info-row">
                                <span class="course-info-label">Bài thi</span>
                                <span class="course-info-value">{{ $courseExamCount }} bài</span>
                            </div>
                            <div class="course-info-row">
                                <span class="course-info-label">Giáo viên</span>
                                <span class="course-info-value">{{ $course->teacher->name }}</span>
                            </div>
                            <div class="course-info-row">
                                <span class="course-info-label">Trạng thái</span>
                                <span class="course-info-value">{{ strtoupper($course->status ?? 'published') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CONTENT --}}
            <div class="col-12 col-md-8 col-xl-8 order-md-1">
                <div class="content-card">

                    {{-- Video --}}
                    <div id="video-container" class="ratio ratio-16x9 bg-dark d-none">
                        <iframe id="lesson-video" src="" allowfullscreen></iframe>
                    </div>

                    {{-- External link banner --}}
                    <div id="external-link-container" class="p-4 p-md-5 text-center d-none border-bottom">
                        <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-circle p-3"
                            style="background:var(--blue-100);">
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
                        <div class="lesson-current-head">
                            <div>
                                <span class="lesson-current-badge">Bài học hiện tại</span>
                                <h2 id="lesson-title" class="lesson-header-title">{{ $course->title }}</h2>
                                <div id="lesson-module-title" class="lesson-current-meta">
                                    {{ $course->modules->first()?->title ? 'Module: ' . $course->modules->first()?->title : 'Chọn bài học trong nội dung khóa học' }}
                                </div>
                            </div>
                            <div id="lesson-duration-box" class="lesson-duration-box d-none">
                                <span>Thời lượng dự kiến</span>
                                <strong><i class="far fa-clock"></i> <span id="lesson-duration-text"></span></strong>
                            </div>
                        </div>

                        {{-- Attachment --}}
                        <div id="lesson-attachment-container" class="attachment-box d-none">
                            <div class="attachment-box__head">
                                <h6>
                                    <i class="fas fa-paperclip"></i>
                                    Tài liệu đính kèm bài học
                                </h6>
                                <span class="badge bg-light text-dark border">1 file</span>
                            </div>
                            <div class="attachment-item">
                                <div class="attachment-icon file-doc">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <strong id="lesson-attachment-name">filename.pdf</strong>
                                    <div class="small-text">Tài liệu bài học · Có thể tải về</div>
                                </div>
                                <div class="d-flex gap-2 attachment-actions">
                                    <a href="#" id="lesson-attachment-view-btn" target="_blank"
                                        class="btn btn-sm btn-secondary-action">
                                        <i class="fas fa-eye me-1"></i>Xem
                                    </a>
                                    <a href="#" id="lesson-attachment-btn" download
                                        class="btn btn-sm btn-primary-action">
                                        <i class="fas fa-download me-1"></i>Tải
                                    </a>
                                </div>
                            </div>
                        </div>

                        <hr class="lesson-divider">
                        <div id="lesson-body" class="lesson-body">
                            <div class="course-intro-card">
                                <div class="course-description">{!! nl2br(e($course->description)) !!}</div>
                            </div>
                            <div id="welcome-placeholder" class="welcome-guide mt-4">
                                @if (auth()->user()->role === 'student')
                                    <h6 class="fw-bold mb-1" style="font-size:14px;"><i
                                            class="fas fa-compass me-2 text-primary"></i>Bắt đầu từ đâu?</h6>
                                    <p class="text-muted small mb-0">Chọn một mục trong danh sách bên trái để bắt đầu. Bài
                                        đã xong sẽ có dấu tích xanh.</p>
                                    <div class="welcome-guide-grid">
                                        <div class="welcome-guide-item">
                                            <div class="welcome-guide-icon" style="background:var(--blue-50);">
                                                <i class="fas fa-play-circle" style="color:var(--blue-600);"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold" style="font-size:13px;">Bài học</div>
                                                <div class="text-muted" style="font-size:12px;line-height:1.5;">Đọc nội
                                                    dung, xem video rồi đánh dấu hoàn thành.</div>
                                            </div>
                                        </div>
                                        <div class="welcome-guide-item">
                                            <div class="welcome-guide-icon" style="background:var(--amber-50);">
                                                <i class="fas fa-file-signature" style="color:var(--amber-500);"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold" style="font-size:13px;">Bài tập</div>
                                                <div class="text-muted" style="font-size:12px;line-height:1.5;">Xem yêu
                                                    cầu, nộp file hoặc viết bài tự luận.</div>
                                            </div>
                                        </div>
                                        <div class="welcome-guide-item">
                                            <div class="welcome-guide-icon" style="background:var(--purple-50);">
                                                <i class="fas fa-stopwatch" style="color:var(--purple-600);"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold" style="font-size:13px;">Quiz</div>
                                                <div class="text-muted" style="font-size:12px;line-height:1.5;">Bấm bắt
                                                    đầu khi sẵn sàng vì hệ thống sẽ tính giờ.</div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <h6 class="fw-bold mb-2" style="font-size:14px;"><i
                                            class="fas fa-pen-to-square me-2 text-primary"></i>Quản lý nội dung</h6>
                                    <div class="text-muted small">Chọn bài học, bài tập hoặc quiz ở danh sách bên trái để
                                        xem nhanh. Dùng các nút thêm nội dung ở phần trên.</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div id="lesson-ai-toolbar" class="lesson-ai-toolbar">
                        <div>
                            <h6 class="lesson-ai-title">
                                <i class="fas fa-robot me-2 text-primary"></i>AI trợ giảng bài này
                            </h6>
                            <div class="lesson-ai-subtitle">Hỏi nhanh theo đúng nội dung bài học đang mở.</div>
                        </div>
                        <div class="lesson-ai-actions">
                            <button type="button" class="lesson-ai-btn" data-ai-assist-mode="summary"
                                data-ai-prompt="Tóm tắt bài học này thành các ý chính dễ nhớ.">
                                <i class="fas fa-align-left"></i>Tóm tắt
                            </button>
                            <button type="button" class="lesson-ai-btn" data-ai-assist-mode="explain"
                                data-ai-prompt="Giải thích lại bài học này theo cách dễ hiểu hơn, từng bước ngắn gọn.">
                                <i class="fas fa-lightbulb"></i>Dễ hiểu hơn
                            </button>
                            <button type="button" class="lesson-ai-btn" data-ai-assist-mode="examples"
                                data-ai-prompt="Tạo ví dụ minh họa ngắn cho nội dung chính của bài học này.">
                                <i class="fas fa-shapes"></i>Ví dụ
                            </button>
                            <button type="button" class="lesson-ai-btn" data-ai-assist-mode="review"
                                data-ai-prompt="Gợi ý cách ôn tập sau bài học này và vài câu hỏi tự kiểm tra.">
                                <i class="fas fa-list-check"></i>Ôn tập
                            </button>
                        </div>
                    </div>

                    {{-- ══ ASSIGNMENT AREA ══ --}}
                    <div id="assignment-content-area" class="d-none flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                            <h2 id="assignment-title" class="assignment-title">
                                <i class="fas fa-tasks me-2" style="color:var(--amber-500);"></i>Tiêu đề bài tập
                            </h2>
                            <span id="assignment-badge" class="badge rounded-pill px-3 py-2 fs-6">Trạng thái</span>
                        </div>
                        <div class="mb-4">
                            <span class="due-badge">
                                <i class="fas fa-clock"></i> Hạn nộp: <span id="assignment-due-date"></span>
                            </span>
                        </div>
                        <hr class="lesson-divider">
                        <h6 class="fw-bold mb-3" style="color:var(--gray-700);">
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
                                                Đã nộp lúc: <span id="submitted-time-text" class="fw-medium"></span>
                                            </p>
                                        </div>
                                    </div>
                                    <div id="submitted-file-card" class="submitted-file-card mb-3">
                                        <div>
                                            <p class="mb-1 fw-bold" style="font-size:14px;">
                                                <i class="fas fa-file-alt me-2 text-primary"></i>Tài liệu đã tải lên
                                            </p>
                                            <p class="mb-0 text-muted" style="font-size:12px;">Mở file để xem chi tiết bài
                                                làm.</p>
                                        </div>
                                        <a href="#" id="submitted-file-link" target="_blank"
                                            class="btn btn-outline-primary btn-sm rounded-pill px-3 flex-shrink-0">
                                            <i class="fas fa-eye me-1"></i> Xem file
                                        </a>
                                    </div>
                                    <div id="submitted-text-answer-card"
                                        class="submitted-file-card d-none mb-3 align-items-start">
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
                                            <textarea name="text_answer" id="essay-answer-input" class="form-control bg-white border-0 shadow-sm" rows="8"
                                                placeholder="Nhập bài làm tự luận của bạn..."></textarea>
                                        </div>
                                        <div class="d-flex flex-column flex-sm-row gap-2">
                                            <div id="file-upload-field" class="flex-grow-1">
                                                <input type="file" name="file" id="assignment-file-input"
                                                    class="form-control bg-white border-0 shadow-sm">
                                                <div class="form-text small">Chỉ cần chọn file với bài dạng nộp file.</div>
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
                                    style="width:68px;height:68px;background:var(--purple-50);">
                                    <i id="quiz-main-icon" class="fas fa-stopwatch fa-2x"
                                        style="color:var(--purple-600);"></i>
                                </div>
                                <h2 id="quiz-display-title" class="quiz-display-title">Tiêu đề bài kiểm tra</h2>
                            </div>
                            <div class="d-flex gap-2 mb-4 flex-wrap">
                                <div class="quiz-stat-card">
                                    <div class="quiz-stat-label"><i class="fas fa-clock me-1"></i> Thời gian</div>
                                    <div class="quiz-stat-value">
                                        <span id="quiz-display-duration">0</span>
                                        <small style="font-size:.8rem;font-weight:600;color:var(--gray-500);">phút</small>
                                    </div>
                                </div>
                                @if (auth()->user()->role === 'student')
                                    <div class="quiz-stat-card">
                                        <div class="quiz-stat-label"><i class="fas fa-tasks me-1"></i> Trạng thái</div>
                                        <div><span id="quiz-status-text" class="fw-bold text-warning"
                                                style="font-size:.95rem;">Chưa làm</span></div>
                                    </div>
                                    <div id="quiz-score-box" class="quiz-stat-card d-none"
                                        style="background:var(--green-50);border-color:#bbf7d0;">
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
                                        <h6 class="fw-bold mb-2" style="color:var(--amber-800);font-size:13px;">
                                            <i class="fas fa-exclamation-triangle me-2"></i>Lưu ý quan trọng
                                        </h6>
                                        <ul class="mb-0 small text-dark ps-3">
                                            <li>Đồng hồ bắt đầu ngay khi bạn bấm nút.</li>
                                            <li>Hệ thống tự nộp bài khi hết thời gian.</li>
                                        </ul>
                                    </div>
                                    <a href="#" id="start-quiz-btn" class="btn btn-quiz-start w-100">
                                        BẮT ĐẦU LÀM BÀI <i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                </div>
                                <div id="quiz-completed-msg" class="d-none">
                                    <div class="text-center p-4 rounded-4 mb-3"
                                        style="background:var(--green-100);color:var(--green-900);">
                                        <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                                        <h5 class="fw-bold mb-1">Hoàn thành!</h5>
                                        <p class="mb-0 small">Bài kiểm tra đã được nộp thành công.</p>
                                    </div>
                                    <a href="#" id="review-quiz-btn"
                                        class="btn btn-success rounded-pill w-100 py-3 fw-bold">
                                        <i class="fas fa-search me-2"></i> Xem chi tiết bài làm
                                    </a>
                                </div>
                            @else
                                <div class="quiz-notice mb-4"
                                    style="background:var(--blue-50);border-color:var(--blue-100);">
                                    <h6 class="fw-bold mb-2 text-primary" style="font-size:13px;">
                                        <i class="fas fa-info-circle me-2"></i>Khu vực Quản lý
                                    </h6>
                                    <p class="mb-0 small text-dark">Vào trang soạn thảo để thêm / sửa / xóa câu hỏi.</p>
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
                            <i class="fas fa-arrow-left"></i>Bài trước
                        </button>
                        <button class="btn btn-complete-lesson d-none" id="btn-complete">
                            <i class="fas fa-check-circle"></i> Hoàn thành
                        </button>
                        <button class="btn-footer-nav" id="btn-next" disabled>
                            Bài tiếp <i class="fas fa-arrow-right"></i>
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
            var desktopAccordion = document.querySelector('.sidebar-scroll .accordion');
            var mobileContent = document.getElementById('mobile-sidebar-content');
            if (desktopAccordion && mobileContent) {
                var clone = desktopAccordion.cloneNode(true);
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

            if (mobileContent) {
                mobileContent.addEventListener('click', function(e) {
                    var target = e.target.closest('.lesson-item, .assignment-item, .quiz-item');
                    if (!target) return;
                    e.preventDefault();
                    var type = target.classList.contains('assignment-item') ? 'assignment' :
                        (target.classList.contains('quiz-item') ? 'quiz' : 'lesson');
                    var id = target.getAttribute('data-id');
                    var selector = type === 'assignment' ?
                        `.sidebar-scroll .assignment-item[data-id="${id}"]` :
                        (type === 'quiz' ?
                            `.sidebar-scroll .quiz-item[data-id="${id}"]` :
                            `.sidebar-scroll .lesson-item[data-id="${id}"]`);
                    var desktopTarget = document.querySelector(selector);
                    if (desktopTarget) desktopTarget.click();
                    closeDrawer();
                });
            }
        })();
    </script>
@endsection
