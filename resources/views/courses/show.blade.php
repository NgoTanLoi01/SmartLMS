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

        .tool-btn i {
            font-size: 11px;
        }

        .tool-btn.blue:hover {
            background: #fff;
        }

        .tool-btn.amber:hover {
            background: var(--amber-400);
            color: #fff;
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

        #btn-open-sidebar {
            position: fixed;
            z-index: 1050;
            background: var(--blue-600);
            color: #fff;
            border: none;
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

        .desktop-sidebar-wrap {
            display: none;
        }

        @media (min-width: 768px) {
            .desktop-sidebar-wrap {
                display: block;
                position: sticky;
            }
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

        .sidebar-head-count {
            font-size: 11px;
            font-weight: 700;
        }

        .accordion-button {
            padding: 0;
            font-weight: 700;
            font-size: 14px;
            background: #fff;
            box-shadow: none !important;
        }

        .accordion-button:hover {
            background: var(--gray-50) !important;
        }

        .accordion-button:focus {
            box-shadow: none !important;
        }

        .module-header-wrapper {
            display: flex;
            align-items: center;
            position: relative;
            transition: background .15s;
        }

        .module-title-block {
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

        .lesson-item-wrapper {
            background: #fff;
            transition: all .15s;
            cursor: pointer;
        }

        .assignment-item-wrapper {
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
            border-bottom-color: #bbf7d0;
        }

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
            border-bottom-color: #bbf7d0;
        }

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

        .content-card {
            overflow: hidden;
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

        #lesson-content-area {
            padding: 20px 18px;
        }

        .lesson-divider {
            border: none;
            border-top: 2px solid var(--gray-100);
            margin-bottom: 20px;
        }

        .lesson-ai-toolbar {
            align-items: center;
            background: #fff;
            border: 1px solid #e7eaf2;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(20, 30, 60, .05);
            display: none;
            gap: 10px;
            justify-content: space-between;
            margin: 0 18px 12px;
            padding: 10px 12px;
        }

        @media (min-width: 768px) {
            .lesson-ai-toolbar {
                margin: 0 36px 14px;
            }
        }

        .lesson-ai-toolbar.active {
            display: flex;
        }

        .lesson-ai-toolbar__intro {
            align-items: center;
            display: flex;
            gap: 10px;
            min-width: 190px;
        }

        .lesson-ai-icon {
            align-items: center;
            background: #eff6ff;
            border-radius: 12px;
            color: var(--blue-600);
            display: inline-flex;
            flex: 0 0 34px;
            height: 34px;
            justify-content: center;
            width: 34px;
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
            gap: 7px;
            justify-content: flex-end;
        }

        .lesson-ai-btn {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            color: #334155;
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            gap: 5px;
            min-height: 32px;
            padding: 7px 11px;
        }

        .lesson-ai-btn:hover {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }

        @media (max-width: 767.98px) {
            .lesson-ai-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .lesson-ai-actions {
                justify-content: flex-start;
            }
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

        .preview-student-mode .toolbar,
        .preview-student-mode .teacher-quick-actions,
        .preview-student-mode .course-dashboard-grid,
        .preview-student-mode .action-buttons {
            display: none !important;
        }

        .preview-student-mode .teacher-preview-banner {
            display: flex;
        }

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

        .course-ref-header>* {
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
            align-items: center;
            background: transparent;
            border: 0;
            box-shadow: none;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
            margin-top: 0;
            min-width: 0;
            padding: 0;
        }

        .course-ref-header .tool-btn {
            border-radius: 999px;
            flex: 0 1 auto;
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
                grid-template-columns: minmax(0, 1fr) minmax(330px, 390px);
            }

            .course-ref-grid>[class*="col-"] {
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
            gap: 14px;
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
            gap: 8px;
            padding: 14px;
        }

        .sidebar-head-row {
            align-items: center;
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }

        .sidebar-head-title {
            color: #202634;
            font-size: 16px;
            font-weight: 900;
            letter-spacing: 0;
            text-transform: none;
        }

        .sidebar-head-count {
            background: #eaf1ff;
            color: #2f6fed;
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
            padding: 14px;
        }

        .course-side-card__title {
            align-items: center;
            color: #202634;
            display: flex;
            font-size: 16px;
            font-weight: 900;
            gap: 8px;
            margin: 0 0 10px;
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
            gap: 10px;
            padding: 10px 0;
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
            font-size: 13px;
            font-weight: 850;
            line-height: 1.35;
        }

        .course-todo-meta {
            color: #6b7386;
            font-size: 12px;
            line-height: 1.45;
            margin-top: 2px;
        }

        .course-info-row {
            align-items: center;
            border-bottom: 1px solid #e7eaf2;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            padding: 9px 0;
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
            font-size: 12.5px;
            font-weight: 700;
        }

        .course-info-value {
            color: #202634;
            font-size: 12.5px;
            font-weight: 900;
            text-align: right;
        }

        .sidebar-inner-card .accordion-button {
            padding-right: 14px;
        }

        .sidebar-inner-card .accordion-button::after {
            margin-left: 12px;
            margin-right: 4px;
            flex-shrink: 0;
        }

        .course-sidebar-column .module-title-block {
            padding: 12px 14px;
        }

        .course-sidebar-column .module-title-text {
            font-size: 13px;
            line-height: 1.32;
        }

        .course-sidebar-column .module-meta {
            font-size: 11px;
        }

        .course-sidebar-column .lesson-item,
        .course-sidebar-column .assignment-item,
        .course-sidebar-column .quiz-item {
            gap: 8px !important;
            padding-bottom: 8px !important;
            padding-top: 8px !important;
        }

        .course-sidebar-column .lesson-item>div:first-child,
        .course-sidebar-column .assignment-item>div:first-child,
        .course-sidebar-column .quiz-item>div:first-child {
            height: 26px !important;
            width: 26px !important;
        }

        .course-sidebar-column .lesson-name-text {
            font-size: 12.8px;
            line-height: 1.34;
        }

        .course-sidebar-column .sidebar-status-row {
            gap: 4px;
            margin-top: 4px;
        }

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
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 6px;
        }

        .sidebar-status-pill {
            align-items: center;
            border: 1px solid transparent;
            border-radius: var(--radius-full);
            display: inline-flex;
            font-size: 10.5px;
            font-weight: 850;
            gap: 3px;
            letter-spacing: 0;
            line-height: 1;
            min-height: 22px;
            padding: 5px 8px;
            white-space: nowrap;
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
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 18px;
            border-bottom: 1px solid #e7eaf2;
            background: #fff;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        #btn-open-sidebar {
            border-radius: 18px;
            bottom: 18px;
            left: 18px;
            box-shadow: 0 12px 28px rgba(47, 111, 237, .32);
            height: 54px;
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
            background: #fff;
            border: 1px solid #e7eaf2;
            border-radius: 14px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .04);
            display: block;
            margin: 0 0 18px;
            padding: 10px 12px;
        }

        .attachment-box__head {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .attachment-box__head h6 {
            align-items: center;
            color: #202634;
            display: flex;
            font-size: 13px;
            font-weight: 900;
            gap: 9px;
            margin: 0;
        }

        .attachment-box__head i {
            color: #2f6fed;
        }

        .attachment-item {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 12px;
            display: flex;
            gap: 10px;
            margin-bottom: 0;
            padding: 9px 10px;
        }

        .attachment-icon {
            align-items: center;
            border-radius: 9px;
            display: flex;
            flex-shrink: 0;
            font-size: 15px;
            height: 36px;
            justify-content: center;
            width: 36px;
        }

        .file-doc {
            background: #eaf1ff;
            color: #2f6fed;
        }

        .btn-primary-action {
            background: #2f6fed;
            border: 0;
            border-radius: 9px;
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
            border-radius: 9px;
            color: #202634;
            font-weight: 800;
        }

        .btn-secondary-action:hover {
            background: #f4f6fb;
            color: #202634;
        }

        .lesson-body {
            color: #202634;
            font-size: 17px;
            line-height: 1.86;
            overflow-wrap: anywhere;
            word-break: normal;
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

        .lesson-body h2,
        .lesson-body h3,
        .lesson-body h4 {
            color: #111827;
            font-weight: 800;
            letter-spacing: 0;
            line-height: 1.35;
            margin: 22px 0 10px;
        }

        .lesson-body h2 {
            font-size: 1.48rem;
        }

        .lesson-body h3 {
            font-size: 1.28rem;
        }

        .lesson-body h4 {
            font-size: 1.08rem;
        }

        .lesson-body p,
        .lesson-body ul,
        .lesson-body ol {
            margin-bottom: 14px;
        }

        .lesson-body table {
            background: #fff;
            border-collapse: separate;
            border-radius: 14px;
            border-spacing: 0;
            box-shadow: 0 0 0 1px #e2e8f0;
            margin: 18px 0;
            overflow: hidden;
            width: 100%;
        }

        .lesson-body table th,
        .lesson-body table td {
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            padding: 12px 14px;
            vertical-align: top;
        }

        .lesson-body table th {
            background: #f8fafc;
            color: #111827;
            font-weight: 800;
        }

        .lesson-body table tr:last-child td {
            border-bottom: 0;
        }

        .lesson-body table th:last-child,
        .lesson-body table td:last-child {
            border-right: 0;
        }

        .lesson-body blockquote {
            background: #f8fafc;
            border-left: 4px solid #3b82f6;
            border-radius: 0 14px 14px 0;
            color: #334155;
            margin: 18px 0;
            padding: 14px 16px;
        }

        .lesson-body a {
            color: #2563eb;
            font-weight: 800;
            text-decoration: none;
            word-break: break-word;
        }

        .lesson-body a:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        .lesson-resource-link {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            display: flex;
            gap: 12px;
            margin: 14px 0;
            padding: 12px 14px;
        }

        .lesson-resource-link__icon {
            align-items: center;
            background: #dbeafe;
            border-radius: 12px;
            color: #2563eb;
            display: inline-flex;
            flex: 0 0 38px;
            height: 38px;
            justify-content: center;
            width: 38px;
        }

        .lesson-resource-link__content {
            min-width: 0;
        }

        .lesson-resource-link__content strong {
            color: #111827;
            display: block;
            font-size: 15px;
            font-weight: 900;
            line-height: 1.35;
        }

        .lesson-resource-link__content a {
            color: #64748b;
            display: block;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.35;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .lesson-material-box {
            background: #fff;
            border: 1px solid #e7eaf2;
            border-radius: 14px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .04);
            margin: 0 0 18px;
            padding: 12px;
        }

        .lesson-material-head {
            align-items: center;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .lesson-material-head h6 {
            align-items: center;
            color: #202634;
            display: flex;
            font-size: 13px;
            font-weight: 900;
            gap: 8px;
            margin: 0;
        }

        .lesson-material-head i {
            color: #2f6fed;
        }

        .lesson-material-list {
            display: grid;
            gap: 9px;
        }

        .lesson-material-card {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 12px;
            display: flex;
            gap: 10px;
            padding: 10px;
            text-decoration: none;
        }

        .lesson-material-card:hover {
            background: #eef4ff;
            border-color: #d7e6ff;
            text-decoration: none;
        }

        .lesson-material-icon {
            align-items: center;
            background: #eaf1ff;
            border-radius: 10px;
            color: #2f6fed;
            display: inline-flex;
            flex: 0 0 36px;
            height: 36px;
            justify-content: center;
            width: 36px;
        }

        .lesson-material-title {
            color: #111827;
            display: block;
            font-size: 14px;
            font-weight: 900;
            line-height: 1.35;
        }

        .lesson-material-meta {
            color: #64748b;
            display: block;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.35;
            margin-top: 2px;
        }

        .lesson-inline-code {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            color: #334155;
            display: block;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 13px;
            margin: 12px 0;
            overflow-x: auto;
            padding: 11px 12px;
            white-space: pre-wrap;
        }

        .lesson-lead-text {
            color: #334155;
            font-size: 1.02rem;
            font-weight: 700;
            line-height: 1.7;
        }

        .lesson-callout,
        .lesson-self-check {
            border: 1px solid #dbeafe;
            border-radius: 18px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .05);
            margin: 18px 0;
            padding: 16px 18px;
        }

        .lesson-callout strong,
        .lesson-self-check h4 {
            align-items: center;
            color: #111827;
            display: flex;
            font-size: 15px;
            font-weight: 900;
            gap: 8px;
            margin: 0 0 8px;
        }

        .lesson-callout strong::before,
        .lesson-self-check h4::before {
            align-items: center;
            border-radius: 999px;
            display: inline-flex;
            flex: 0 0 26px;
            font-family: "Font Awesome 5 Free";
            font-size: 12px;
            font-weight: 900;
            height: 26px;
            justify-content: center;
            width: 26px;
        }

        .lesson-callout p:last-child,
        .lesson-callout ol:last-child,
        .lesson-self-check ol:last-child {
            margin-bottom: 0;
        }

        .lesson-callout--note {
            background: linear-gradient(135deg, #eff6ff 0%, #f8fbff 100%);
            border-color: #bfdbfe;
        }

        .lesson-callout--note strong::before {
            background: #dbeafe;
            color: #2563eb;
            content: "\f02e";
        }

        .lesson-callout--example {
            background: linear-gradient(135deg, #ecfdf5 0%, #f8fffb 100%);
            border-color: #bbf7d0;
        }

        .lesson-callout--example strong::before {
            background: #dcfce7;
            color: #16a34a;
            content: "\f075";
        }

        .lesson-callout--warning {
            background: linear-gradient(135deg, #fff7ed 0%, #fffaf5 100%);
            border-color: #fed7aa;
        }

        .lesson-callout--warning strong::before {
            background: #ffedd5;
            color: #ea580c;
            content: "\f071";
        }

        .lesson-callout--practice {
            background: linear-gradient(135deg, #f5f3ff 0%, #fbfaff 100%);
            border-color: #ddd6fe;
        }

        .lesson-callout--practice strong::before {
            background: #ede9fe;
            color: #7c3aed;
            content: "\f0ae";
        }

        .lesson-checklist {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            list-style: none;
            margin: 18px 0;
            padding: 16px 18px;
        }

        .lesson-checklist li {
            margin: 10px 0;
            padding-left: 34px;
            position: relative;
        }

        .lesson-checklist li::before {
            align-items: center;
            background: #dcfce7;
            border-radius: 999px;
            color: #16a34a;
            content: "\f00c";
            display: inline-flex;
            font-family: "Font Awesome 5 Free";
            font-size: 11px;
            font-weight: 900;
            height: 22px;
            justify-content: center;
            left: 0;
            position: absolute;
            top: 2px;
            width: 22px;
        }

        .lesson-self-check {
            background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 100%);
            border-color: #c7d2fe;
        }

        .lesson-self-check h4::before {
            background: #e0e7ff;
            color: #4f46e5;
            content: "\f059";
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
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

        .teacher-mode-panel {
            background: #ffffff;
            border: 1px solid #e5ebf5;
            border-radius: 18px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .08);
            margin-bottom: 20px;
            overflow: hidden;
            padding: 20px 22px 22px;
            position: relative;
        }

        .teacher-mode-panel::before {
            background: linear-gradient(90deg, #2563eb 0 25%, #b7842d 25% 50%, #7c3aed 50% 75%, #0f766e 75% 100%);
            content: '';
            height: 4px;
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
        }

        .teacher-mode-row {
            align-items: flex-start;
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            justify-content: space-between;
        }

        .teacher-mode-title {
            color: #111827;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 16.5px;
            font-weight: 700;
            margin: 0 0 4px;
        }

        .teacher-mode-title i {
            color: #2563eb;
        }

        .teacher-mode-subtitle {
            color: #64748b;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 500;
        }

        .teacher-mode-toggle {
            background: #f1f5f9;
            border-radius: 999px;
            display: inline-flex;
            gap: 2px;
            padding: 4px;
        }

        .teacher-mode-btn {
            background: transparent;
            border: 0;
            border-radius: 999px;
            color: #64748b;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 700;
            padding: 8px 14px;
            transition: background .15s ease, color .15s ease, box-shadow .15s ease;
            white-space: nowrap;
        }

        .teacher-mode-btn.active {
            background: #2563eb;
            box-shadow: 0 4px 10px rgba(37, 99, 235, .28);
            color: #fff;
        }

        .teacher-mode-btn:not(.active):hover {
            color: #2563eb;
        }

        .teacher-preview-banner {
            align-items: center;
            background: #fef6e7;
            border: 1px solid #f3dfae;
            border-radius: 12px;
            color: #8a611f;
            display: none;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 600;
            gap: 10px;
            margin-top: 14px;
            padding: 10px 14px;
        }

        .teacher-preview-banner i {
            color: #b7842d;
        }

        .teacher-mode-panel.is-preview .teacher-preview-banner {
            display: flex;
        }

        .teacher-quick-actions {
            background: #f8fbff;
            border: 1px solid #e4efff;
            border-radius: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
            padding: 10px;
        }

        .tool-btn {
            align-items: center;
            border: 1px solid transparent;
            border-radius: 12px;
            display: inline-flex;
            font-family: 'Inter', sans-serif;
            font-size: 13.5px;
            font-weight: 700;
            gap: 8px;
            padding: 10px 14px;
            transition: background .15s ease, color .15s ease, border-color .15s ease, transform .15s ease;
            line-height: 1;
            white-space: nowrap;
            text-decoration: none;
            cursor: pointer;
        }

        .tool-btn:hover {
            transform: translateY(-1px);
        }

        .tool-btn.blue {
            background: #e8f0fe;
            border-color: #c7d9fb;
            color: #2563eb;
        }

        .tool-btn.blue:hover {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }

        .tool-btn.amber {
            background: #f6e8ce;
            border-color: #e7cf9c;
            color: #8a611f;
        }

        .tool-btn.amber:hover {
            background: #b7842d;
            border-color: #b7842d;
            color: #fff;
        }

        .tool-btn.purple {
            background: #efe7fe;
            border-color: #d9c8fb;
            color: #6d28d9;
        }

        .tool-btn.purple:hover {
            background: #7c3aed;
            border-color: #7c3aed;
            color: #fff;
        }

        .tool-btn.teal {
            background: #e1f2ef;
            border-color: #bfe3dd;
            color: #0f766e;
        }

        .tool-btn.teal:hover {
            background: #0f766e;
            border-color: #0f766e;
            color: #fff;
        }

        @media (max-width: 575.98px) {
            .teacher-mode-row {
                flex-direction: column;
            }

            .teacher-mode-toggle,
            .tool-btn {
                width: 100%;
                justify-content: center;
            }

            .teacher-mode-toggle {
                display: flex;
            }
        }

        .presentation-controls {
            align-items: center;
            background: rgba(15, 23, 42, .92);
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 999px;
            box-shadow: 0 14px 38px rgba(15, 23, 42, .3);
            display: none;
            gap: 6px;
            padding: 6px;
            position: fixed;
            right: 22px;
            top: 18px;
            z-index: 12000;
        }

        .presentation-controls button {
            align-items: center;
            background: rgba(255, 255, 255, .1);
            border: 0;
            border-radius: 999px;
            color: #fff;
            display: inline-flex;
            font-size: 13px;
            font-weight: 800;
            gap: 5px;
            min-height: 38px;
            padding: 0 13px;
        }

        .presentation-controls button:hover { background: rgba(255, 255, 255, .2); }
        .presentation-controls .presentation-exit-btn { background: #dc2626; }
        .presentation-controls .presentation-exit-btn:hover { background: #b91c1c; }

        body.course-presentation-mode { background: #fff; overflow-y: auto; }
        body.course-presentation-mode .navbar,
        body.course-presentation-mode #sidebar,
        body.course-presentation-mode #sidebarToggle,
        body.course-presentation-mode #mobile-sidebar-overlay,
        body.course-presentation-mode #mobile-sidebar-drawer,
        body.course-presentation-mode #btn-open-sidebar,
        body.course-presentation-mode #course-page-wrapper > .course-ref-header,
        body.course-presentation-mode #teacher-mode-panel,
        body.course-presentation-mode .course-dashboard-grid,
        body.course-presentation-mode .course-sidebar-column,
        body.course-presentation-mode .teacher-quick-actions,
        body.course-presentation-mode .action-buttons,
        body.course-presentation-mode .lesson-ai-toolbar,
        body.course-presentation-mode .toolbar,
        body.course-presentation-mode #chatbot-container,
        body.course-presentation-mode .cb-toggler,
        body.course-presentation-mode .cb-window {
            display: none !important;
        }

        body.course-presentation-mode .main-content {
            margin: 0 !important;
            min-height: 100vh;
            padding: 0 !important;
        }

        body.course-presentation-mode #course-page-wrapper {
            margin: 0;
            max-width: none;
            padding: 0;
            width: 100%;
        }

        body.course-presentation-mode .course-ref-grid {
            display: block;
            margin: 0;
        }

        body.course-presentation-mode #course-content-column {
            max-width: none;
            padding: 0;
            width: 100%;
        }

        body.course-presentation-mode .content-card {
            border: 0;
            border-radius: 0;
            box-shadow: none;
            min-height: 100vh;
        }

        body.course-presentation-mode #lesson-content-area {
            margin: 0 auto;
            max-width: 1500px;
            padding: clamp(70px, 8vw, 120px) clamp(36px, 8vw, 140px) 80px;
        }

        body.course-presentation-mode .lesson-header-title {
            font-size: calc(2.15rem * var(--presentation-font-scale, 1));
            line-height: 1.25;
        }

        body.course-presentation-mode .lesson-body {
            font-size: calc(1.55rem * var(--presentation-font-scale, 1));
            line-height: 1.9;
        }

        body.course-presentation-mode .lesson-body h2 { font-size: calc(2rem * var(--presentation-font-scale, 1)); }
        body.course-presentation-mode .lesson-body h3 { font-size: calc(1.75rem * var(--presentation-font-scale, 1)); }
        body.course-presentation-mode .lesson-body h4 { font-size: calc(1.5rem * var(--presentation-font-scale, 1)); }
        body.course-presentation-mode .presentation-controls { display: flex; }
        body.course-presentation-mode .footer-nav { border-top: 1px solid #e2e8f0; padding: 18px clamp(24px, 6vw, 90px); }
        body.course-presentation-mode .btn-footer-nav { font-size: 16px; min-height: 48px; padding: 0 22px; }

        @media (max-width: 767.98px) {
            .presentation-controls { border-radius: 16px; left: 10px; right: 10px; top: 10px; }
            .presentation-controls button { flex: 1; justify-content: center; }
            body.course-presentation-mode #lesson-content-area { padding: 76px 22px 50px; }
            body.course-presentation-mode .lesson-body { font-size: calc(1.2rem * var(--presentation-font-scale, 1)); }
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
            $courseQuizCount = $course->quizzes
                ->filter(fn($quiz) => !str_contains(mb_strtolower($quiz->title), 'thi'))
                ->count();
            $courseExamCount = $course->quizzes
                ->filter(fn($quiz) => str_contains(mb_strtolower($quiz->title), 'thi'))
                ->count();
            $isCourseManager = auth()->id() === $course->teacher_id || auth()->user()->role === 'admin';
            $allLessons = $course->modules->flatMap(fn($module) => $module->lessons);
            $allAssignments = $allLessons->flatMap(fn($lesson) => $lesson->assignments);
            $nextLesson =
                auth()->user()->role === 'student'
                    ? $allLessons->first(fn($lesson) => !in_array($lesson->id, $completedLessonIds ?? []))
                    : $allLessons->first();
            $nextAssignment =
                auth()->user()->role === 'student'
                    ? $allAssignments->first(fn($assignment) => !isset($userSubmissions[$assignment->id]))
                    : $allAssignments->first();
            $finalExam = $course->quizzes->first(fn($quiz) => str_contains(mb_strtolower($quiz->title), 'thi'));
            $regularQuizzes = $course->quizzes->filter(fn($quiz) => !str_contains(mb_strtolower($quiz->title), 'thi'));
            $nextQuiz =
                auth()->user()->role === 'student'
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
                        <a href="{{ route('courses.materials.index', $course->id) }}" class="tool-btn blue">
                            <i class="fas fa-folder-open"></i> Kho học liệu
                        </a>
                        <button type="button" class="tool-btn purple" id="start-presentation-btn">
                            <i class="fas fa-display"></i> Trình chiếu
                        </button>
                    </div>
                @else
                    <div class="toolbar">
                        <a href="{{ route('attendance.show', $course->id) }}" class="tool-btn teal">
                            <i class="fas fa-user-check"></i> Điểm danh & điểm số
                        </a>
                        <a href="{{ route('courses.materials.index', $course->id) }}" class="tool-btn blue">
                            <i class="fas fa-folder-open"></i> Kho học liệu
                        </a>
                    </div>
                @endif
            </div>
        </div>

        @if ($isCourseManager)
            <div class="teacher-mode-panel" id="teacher-mode-panel">
                <div class="teacher-mode-row">
                    <div>
                        <h6 class="teacher-mode-title"><i class="fas fa-layer-group me-2"></i>Chế độ giáo viên</h6>
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
                    <a href="{{ route('courses.materials.index', $course->id) }}" class="tool-btn blue">
                        <i class="fas fa-folder-open"></i> Kho học liệu
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
                    <div class="course-dashboard-label"><i class="fas fa-graduation-cap me-1"></i> Hoàn thành bài học
                    </div>
                    <div class="course-dashboard-value">{{ $courseDashboard['lesson_completion_rate'] }}%</div>
                    <div class="course-dashboard-sub">Tỷ lệ toàn khóa</div>
                </div>
                <div class="course-dashboard-card">
                    <div class="course-dashboard-label"><i class="fas fa-file-signature me-1 text-warning"></i> Nộp bài
                        tập
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
            <div class="col-md-4 col-xl-3 d-none d-md-block order-md-2 course-sidebar-column">
                <div class="desktop-sidebar-wrap course-sidebar-stack">
                    <div class="sidebar-inner-card course-outline-card">
                        <div class="sidebar-head">
                            <div class="sidebar-head-row">
                                <h6 class="sidebar-head-title">
                                    <i class="fas fa-list-ul text-primary"></i>  Nội dung khóa học
                                </h6>
                                @if (auth()->user()->role === 'student')
                                    <span class="sidebar-head-count">{{ $progress }}%</span>
                                @endif
                            </div>
                            @if (auth()->user()->role === 'student')
                                <div class="course-sidebar-progress">
                                    <span id="sidebar-progress-text">Đã học {{ $completedCount }}/{{ $totalLessons }} bài
                                        · Tiến độ {{ $progress }}%</span>
                                </div>
                                <div class="course-sidebar-progress-track" aria-hidden="true">
                                    <span id="sidebar-progress-bar" class="course-sidebar-progress-fill"
                                        style="width: {{ $progress }}%;"></span>
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
                                        <span
                                            class="course-todo-title">{{ auth()->user()->role === 'student' ? 'Nộp bài tập' : 'Bài tập trong khóa' }}</span>
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
                                        <span
                                            class="course-todo-title">{{ auth()->user()->role === 'student' ? 'Làm kiểm tra' : 'Kiểm tra trong khóa' }}</span>
                                        <span class="course-todo-meta">{{ $nextQuiz->title }} ·
                                            {{ $nextQuiz->time_limit }} phút</span>
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
            <div class="col-12 col-md-8 col-xl-9 order-md-1" id="course-content-column">
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

                        <div id="lesson-material-container" class="lesson-material-box d-none">
                            <div class="lesson-material-head">
                                <h6>
                                    <i class="fas fa-folder-open"></i>
                                    Học liệu liên quan
                                </h6>
                                <span id="lesson-material-count" class="badge bg-light text-dark border">0 mục</span>
                            </div>
                            <div id="lesson-material-list" class="lesson-material-list"></div>
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
                        <div class="lesson-ai-toolbar__intro">
                            <span class="lesson-ai-icon">
                                <i class="fas fa-robot"></i>
                            </span>
                            <div>
                                <h6 class="lesson-ai-title">AI trợ giảng</h6>
                                <div class="lesson-ai-subtitle">Hỗ trợ theo bài đang mở.</div>
                            </div>
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

    @if ($isCourseManager)
        <div class="presentation-controls" id="presentation-controls" aria-label="Điều khiển trình chiếu">
            <button type="button" id="presentation-font-down" title="Giảm cỡ chữ" aria-label="Giảm cỡ chữ">
                <i class="fas fa-minus"></i><span>A</span>
            </button>
            <button type="button" id="presentation-font-up" title="Tăng cỡ chữ" aria-label="Tăng cỡ chữ">
                <span>A</span><i class="fas fa-plus"></i>
            </button>
            <button type="button" id="exit-presentation-btn" class="presentation-exit-btn">
                <i class="fas fa-compress"></i> Thoát trình chiếu
            </button>
        </div>
    @endif

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

        (function() {
            var startButton = document.getElementById('start-presentation-btn');
            var exitButton = document.getElementById('exit-presentation-btn');
            var fontUpButton = document.getElementById('presentation-font-up');
            var fontDownButton = document.getElementById('presentation-font-down');
            var fontScale = 1;

            if (!startButton) return;

            function updateFontScale(delta) {
                fontScale = Math.max(.75, Math.min(1.5, fontScale + delta));
                document.body.style.setProperty('--presentation-font-scale', fontScale.toFixed(2));
            }

            function startPresentation() {
                document.body.classList.add('course-presentation-mode');
                document.body.style.setProperty('--presentation-font-scale', fontScale.toFixed(2));
                window.scrollTo({ top: 0, behavior: 'smooth' });

                if (!document.fullscreenElement && document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen().catch(function() {
                        // Chế độ trình chiếu vẫn hoạt động khi trình duyệt từ chối fullscreen.
                    });
                }
            }

            function exitPresentation(leaveFullscreen) {
                document.body.classList.remove('course-presentation-mode');
                document.body.style.removeProperty('--presentation-font-scale');

                if (leaveFullscreen !== false && document.fullscreenElement && document.exitFullscreen) {
                    document.exitFullscreen().catch(function() {});
                }
            }

            startButton.addEventListener('click', startPresentation);
            if (exitButton) exitButton.addEventListener('click', function() { exitPresentation(true); });
            if (fontUpButton) fontUpButton.addEventListener('click', function() { updateFontScale(.1); });
            if (fontDownButton) fontDownButton.addEventListener('click', function() { updateFontScale(-.1); });

            document.addEventListener('keydown', function(event) {
                if (!document.body.classList.contains('course-presentation-mode')) return;
                if (event.key === 'Escape') exitPresentation(false);
                if (event.key === '+' || event.key === '=') updateFontScale(.1);
                if (event.key === '-') updateFontScale(-.1);
            });

            document.addEventListener('fullscreenchange', function() {
                if (!document.fullscreenElement && document.body.classList.contains('course-presentation-mode')) {
                    exitPresentation(false);
                }
            });
        })();
    </script>
@endsection
