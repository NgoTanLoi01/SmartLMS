@extends('layouts.app')

@section('content')
    @push('styles')
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap');

            :root {
                --surface: #FFFFFF;
                --surface-2: #F9FAFB;
                --surface-3: #F3F4F6;
                --border: #E5E7EB;
                --border-md: #D1D5DB;
                --text-1: #111827;
                --text-2: #374151;
                --text-3: #6B7280;
                --text-4: #9CA3AF;

                --blue-50: #EFF6FF;
                --blue-100: #DBEAFE;
                --blue-500: #3B82F6;
                --blue-600: #2563EB;

                --amber-50: #FFFBEB;
                --amber-100: #FEF3C7;
                --amber-500: #F59E0B;
                --amber-600: #D97706;

                --green-50: #F0FDF4;
                --green-100: #DCFCE7;
                --green-500: #22C55E;
                --green-600: #16A34A;
                --green-700: #15803D;

                --red-50: #FEF2F2;
                --red-500: #EF4444;

                --violet-50: #F5F3FF;
                --violet-500: #8B5CF6;

                --r-sm: 8px;
                --r-md: 12px;
                --r-lg: 16px;
            }

            *,
            *::before,
            *::after {
                box-sizing: border-box;
            }

            body {
                font-family: 'Be Vietnam Pro', sans-serif;
                background: var(--surface-3);
            }

            /* ── PAGE WRAPPER ── */
            .att-page {
                padding: 1.25rem;
                height: calc(100vh - 64px);
                display: flex;
                flex-direction: column;
                gap: 0;
            }

            /* ── TOOLBAR CARD ── */
            .att-toolbar {
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: var(--r-lg) var(--r-lg) 0 0;
                border-bottom: none;
                padding: 0.9rem 1.25rem;
                display: flex;
                align-items: center;
                gap: 1rem;
                flex-wrap: wrap;
                flex-shrink: 0;
            }

            /* Title block */
            .att-title-block {
                flex: 1 1 100%;
                min-width: 0;
            }

            .att-title-block h5 {
                font-size: 0.9rem;
                font-weight: 800;
                color: var(--text-1);
                margin: 0 0 0.1rem;
                display: flex;
                align-items: center;
                gap: 0.45rem;
                white-space: nowrap;
            }

            .att-title-block h5 i {
                color: var(--blue-500);
                font-size: 0.85rem;
            }

            .att-title-block small {
                font-size: 0.72rem;
                color: var(--text-4);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                display: block;
                max-width: 320px;
            }

            /* Toolbar right */
            .att-actions {
                display: grid;
                align-items: center;
                gap: 0.5rem;
                flex: 1 1 100%;
                grid-template-columns: auto minmax(0, 1fr);
                min-width: 0;
                width: 100%;
            }

            .att-primary-actions {
                align-items:center;
                display:flex;
                flex-wrap:nowrap;
                gap:.5rem;
                min-width:0;
            }

            /* Search */
            .att-search {
                display: flex;
                align-items: center;
                gap: 0;
                position: relative;
            }

            .att-search i {
                position: absolute;
                left: 0.65rem;
                font-size: 0.72rem;
                color: var(--text-4);
                pointer-events: none;
            }

            .att-search input {
                padding: 0.45rem 0.75rem 0.45rem 1.9rem;
                font-family: 'Be Vietnam Pro', sans-serif;
                font-size: 0.78rem;
                background: var(--surface-2);
                border: 1.5px solid var(--border);
                border-radius: var(--r-sm);
                color: var(--text-1);
                outline: none;
                width: 180px;
                transition: border-color 0.15s, background 0.15s;
            }

            .att-search input:focus {
                border-color: var(--blue-500);
                background: var(--surface);
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }

            /* Divider */
            .att-divider {
                width: 1px;
                height: 26px;
                background: var(--border);
                flex-shrink: 0;
            }

            /* Add column form */
            .add-col-form {
                display: flex;
                align-items: center;
                flex: 1 1 650px;
                flex-wrap: nowrap;
                gap: 0.4rem;
                min-width: 0;
            }

            .add-col-form input,
            .add-col-form select {
                padding: 0.45rem 0.7rem;
                font-family: 'Be Vietnam Pro', sans-serif;
                font-size: 0.78rem;
                background: var(--surface-2);
                border: 1.5px solid var(--border);
                border-radius: var(--r-sm);
                color: var(--text-1);
                outline: none;
                transition: border-color 0.15s;
            }

            .add-col-form input {
                width: 120px;
            }

            .add-col-form select {
                width: 115px;
                appearance: none;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239CA3AF' stroke-width='2.5'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 0.55rem center;
                padding-right: 1.75rem;
            }

            .add-col-form input[name="name"] { flex: 1 1 150px; max-width: 220px; width: auto; }
            .add-col-form input[name="attendance_date"] { flex: 0 1 155px; width: 155px; }
            .add-col-form select[name="type"] { flex: 0 1 135px; width: 135px; }
            .add-col-form select[name="schedule_id"] { flex: 1 1 180px; max-width: 245px; width: auto; }
            .add-col-form .chip-btn { flex: 0 0 auto; }

            .add-col-form input:focus,
            .add-col-form select:focus {
                border-color: var(--blue-500);
                background: var(--surface);
            }

            /* Chip buttons */
            .chip-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                font-family: 'Be Vietnam Pro', sans-serif;
                font-size: 0.75rem;
                font-weight: 700;
                border-radius: var(--r-sm);
                padding: 0.45rem 0.85rem;
                border: 1.5px solid;
                cursor: pointer;
                transition: all 0.15s;
                white-space: nowrap;
                text-decoration: none;
            }

            .chip-btn i {
                font-size: 0.7rem;
            }

            .chip-green {
                color: var(--green-700);
                background: var(--green-50);
                border-color: var(--green-100);
            }

            .chip-green:hover {
                background: var(--green-100);
                color: var(--green-700);
            }

            .chip-blue {
                color: var(--blue-600);
                background: var(--blue-50);
                border-color: var(--blue-100);
            }

            .chip-blue:hover {
                background: var(--blue-100);
            }

            /* ── TABLE WRAPPER ── */
            .att-table-wrap {
                flex: 1;
                overflow: auto;
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: 0;
                position: relative;
            }

            .att-table-wrap::-webkit-scrollbar {
                width: 6px;
                height: 6px;
            }

            .att-table-wrap::-webkit-scrollbar-track {
                background: var(--surface-3);
            }

            .att-table-wrap::-webkit-scrollbar-thumb {
                background: var(--border-md);
                border-radius: 99px;
            }

            /* ── TABLE ── */
            .att-table {
                border-collapse: separate !important;
                border-spacing: 0 !important;
                width: max-content;
                min-width: 100%;
            }

            /* All cells */
            .att-table th,
            .att-table td {
                border-right: 1px solid var(--border) !important;
                border-bottom: 1px solid var(--border) !important;
                white-space: nowrap;
                padding: 0;
            }

            .att-table thead th {
                border-top: 1px solid var(--border) !important;
            }

            .att-table tr th:first-child,
            .att-table tr td:first-child {
                border-left: 1px solid var(--border) !important;
            }

            /* ── STICKY STT ── */
            .col-stt {
                position: sticky !important;
                left: 0 !important;
                width: 46px !important;
                min-width: 46px !important;
                max-width: 46px !important;
                z-index: 4 !important;
                background: var(--surface) !important;
                box-shadow: 2px 0 0 var(--border);
                text-align: center !important;
            }

            thead .col-stt {
                z-index: 8 !important;
                background: var(--surface-2) !important;
            }

            /* ── STICKY NAME ── */
            .col-name {
                position: sticky !important;
                left: 46px !important;
                width: 196px !important;
                min-width: 196px !important;
                max-width: 196px !important;
                z-index: 4 !important;
                background: var(--surface) !important;
                box-shadow: 3px 0 8px rgba(0, 0, 0, 0.07);
                border-right: 2px solid var(--border-md) !important;
                overflow: visible !important;
                text-overflow: clip !important;
            }

            thead .col-name {
                z-index: 8 !important;
                background: var(--surface-2) !important;
            }

            /* ── THEAD ── */
            .att-table thead th {
                position: sticky;
                top: 0;
                z-index: 3;
                background: var(--surface-2);
                font-size: 0.68rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                color: var(--text-3);
                padding: 0.6rem 0.6rem;
                text-align: center;
            }

            .att-table thead .col-name {
                text-align: left;
                padding-left: 0.85rem;
            }

            /* Column type colors in header */
            .col-attendance-h {
                background: var(--blue-50) !important;
                color: var(--blue-600) !important;
            }

            .col-grade-h {
                background: var(--amber-50) !important;
                color: var(--amber-600) !important;
            }

            .col-note-h {
                background: var(--surface-2) !important;
            }

            /* ── TBODY ROWS ── */
            .att-table tbody td {
                font-size: 0.8rem;
                color: var(--text-2);
                background: var(--surface);
                transition: background 0.1s;
            }

            .student-row:hover td {
                background: #F8FAFF !important;
            }

            .student-row:hover .col-stt,
            .student-row:hover .col-name {
                background: #F8FAFF !important;
            }

            /* STT cell */
            .stt-cell {
                font-size: 0.72rem;
                color: var(--text-4);
                font-family: 'DM Mono', monospace;
                text-align: center;
                padding: 0.55rem 0.4rem;
            }

            /* Name cell */
            .name-cell {
                font-weight: 700;
                color: var(--text-1);
                font-size: 0.82rem;
                padding: 0.55rem 0.85rem;
            }

            /* Data cells */
            .col-attendance-cell {
                background: #FAFCFF;
            }

            .col-grade-cell {
                background: #FFFDF7;
            }

            /* ── INPUTS ── */
            .att-table input[type="text"] {
                width: 100%;
                height: 100%;
                border: none;
                background: transparent;
                font-family: 'DM Mono', monospace;
                font-size: 0.8rem;
                color: var(--text-1);
                padding: 0.55rem 0.4rem;
                text-align: center;
                outline: none;
                transition: background 0.12s;
            }

            .col-note-cell input {
                text-align: left;
                padding: 0.55rem 0.65rem;
                font-family: 'Be Vietnam Pro', sans-serif;
                font-size: 0.78rem;
            }

            .att-table input:focus {
                background: #FEFCE8 !important;
                box-shadow: inset 0 0 0 2px #FCD34D;
            }

            /* ── COLUMN HEADER CELL ── */
            .col-header-inner {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.3rem;
                padding: 0.5rem 1.5rem 0.5rem 0.5rem;
            }

            .editable-name {
                cursor: text;
                display: inline-block;
                border-bottom: 1px dashed transparent;
                padding: 1px 3px;
                border-radius: 4px;
                transition: border-color 0.15s, background 0.15s;
            }

            .editable-name:hover {
                border-bottom-color: var(--border-md);
            }

            .editable-name:focus {
                outline: none;
                background: var(--surface);
                border-bottom-color: var(--blue-500);
                border-radius: 4px 4px 0 0;
            }

            .btn-delete-col {
                position: absolute;
                right: 4px;
                top: 50%;
                transform: translateY(-50%);
                font-size: 0.65rem;
                color: var(--text-4);
                cursor: pointer;
                opacity: 0;
                transition: opacity 0.15s, color 0.15s;
                padding: 2px 3px;
                border-radius: 4px;
            }

            .att-table th:hover .btn-delete-col {
                opacity: 1;
            }

            .btn-delete-col:hover {
                color: var(--red-500) !important;
                background: var(--red-50);
            }

            /* ── FOOTER BAR ── */
            .att-footer {
                background: var(--surface);
                border: 1px solid var(--border);
                border-top: none;
                border-radius: 0 0 var(--r-lg) var(--r-lg);
                padding: 0.75rem 1.25rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                flex-shrink: 0;
            }

            .att-hint {
                font-size: 0.75rem;
                color: var(--text-4);
                display: flex;
                align-items: center;
                gap: 0.4rem;
            }

            .att-hint i {
                color: var(--blue-500);
            }

            /* Save button */
            .btn-save {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                font-family: 'Be Vietnam Pro', sans-serif;
                font-size: 0.875rem;
                font-weight: 800;
                background: var(--green-600);
                color: #fff;
                border: none;
                border-radius: var(--r-sm);
                padding: 0.65rem 2rem;
                cursor: pointer;
                transition: all 0.15s;
                letter-spacing: 0.02em;
                box-shadow: 0 4px 10px rgba(22, 163, 74, 0.3);
            }

            .btn-save:hover {
                background: var(--green-700);
                box-shadow: 0 6px 14px rgba(22, 163, 74, 0.38);
                transform: translateY(-1px);
            }

            .btn-save:active {
                transform: translateY(0);
            }

            /* ── SAVE FLASH ── */
            .save-flash {
                position: fixed;
                bottom: 1.5rem;
                right: 1.5rem;
                background: var(--green-600);
                color: #fff;
                border-radius: var(--r-md);
                padding: 0.65rem 1.25rem;
                font-size: 0.82rem;
                font-weight: 700;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                box-shadow: 0 8px 20px rgba(22, 163, 74, 0.35);
                opacity: 0;
                transform: translateY(12px);
                transition: all 0.25s;
                pointer-events: none;
                z-index: 9999;
            }

            .save-flash.show {
                opacity: 1;
                transform: translateY(0);
            }

            /* Empty state */
            .att-empty {
                text-align: center;
                padding: 4rem 1rem;
            }

            .att-empty i {
                font-size: 2.5rem;
                color: var(--border-md);
                margin-bottom: 0.75rem;
            }

            .att-empty p {
                font-size: 0.85rem;
                color: var(--text-4);
                margin: 0;
            }

            .attendance-control {
                align-items: center;
                display: flex;
                gap: 5px;
                justify-content: center;
                min-width: 118px;
                padding: 4px;
            }

            .attendance-status-btn {
                align-items: center;
                border: 1px solid transparent;
                border-radius: 999px;
                cursor: pointer;
                display: inline-flex;
                font-family: 'Be Vietnam Pro', sans-serif;
                font-size: 10px;
                font-weight: 800;
                gap: 4px;
                justify-content: center;
                min-height: 30px;
                min-width: 78px;
                padding: 5px 8px;
                transition: transform .15s, box-shadow .15s;
            }

            .attendance-status-btn:not(:disabled):hover { box-shadow: 0 5px 12px rgba(15, 23, 42, .12); transform: translateY(-1px); }
            .attendance-status-btn.status-present { background:#dcfce7; border-color:#bbf7d0; color:#166534; }
            .attendance-status-btn.status-absent { background:#fee2e2; border-color:#fecaca; color:#b91c1c; }
            .attendance-status-btn.status-late { background:#fef3c7; border-color:#fde68a; color:#92400e; }
            .attendance-status-btn.status-excused { background:#dbeafe; border-color:#bfdbfe; color:#1d4ed8; }

            .attendance-note-btn {
                align-items:center;
                background:#fff;
                border:1px solid var(--border);
                border-radius:9px;
                color:var(--text-4);
                display:flex;
                height:28px;
                justify-content:center;
                width:28px;
            }
            .attendance-note-btn.has-note { background:#f5f3ff; border-color:#ddd6fe; color:#7c3aed; }
            .attendance-note-btn:disabled { opacity:.75; }
            .attendance-column-meta { color:var(--text-4); display:block; font-size:9px; font-weight:600; margin-top:3px; white-space:nowrap; }
            .attendance-legend { align-items:center; display:flex; flex-wrap:wrap; gap:6px; }
            .attendance-legend span { border-radius:999px; font-size:10px; font-weight:800; padding:4px 8px; }
            .attendance-only-field[hidden] { display:none !important; }

            @media (max-width: 1599.98px) {
                .att-actions { grid-template-columns:1fr; }
                .att-primary-actions { justify-content:flex-start; }
                .att-search { flex:1 1 220px; max-width:320px; }
                .att-search input { width:100%; }
                .add-col-form { width:100%; }
            }

            @media (max-width: 1099.98px) {
                .add-col-form { flex-wrap:wrap; }
            }

            @media (max-width: 767.98px) {
                .att-page {
                    height: auto;
                    min-height: calc(100dvh - 64px);
                    padding: 0.75rem;
                }

                .att-toolbar {
                    align-items: stretch;
                    flex-direction: column;
                    gap: 0.75rem;
                    padding: 0.85rem;
                    border-radius: var(--r-md) var(--r-md) 0 0;
                }

                .att-title-block h5 {
                    white-space: normal;
                    line-height: 1.25;
                }

                .att-title-block small {
                    max-width: none;
                    white-space: normal;
                    overflow: visible;
                    text-overflow: clip;
                    line-height: 1.35;
                }

                .att-actions {
                    width: 100%;
                    display: grid;
                    grid-template-columns: 1fr;
                    gap: 0.6rem;
                }

                .att-primary-actions {
                    display:grid;
                    grid-template-columns:1fr 1fr;
                    width:100%;
                }

                .att-primary-actions .att-search,
                .att-primary-actions #markAllPresentBtn {
                    grid-column:1 / -1;
                    max-width:none;
                }

                .att-search,
                .att-search input,
                .chip-btn {
                    width: 100%;
                }

                .chip-btn {
                    justify-content: center;
                }

                .att-divider {
                    display: none;
                }

                .add-col-form {
                    width: 100%;
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 0.5rem;
                }

                .add-col-form input[name="name"],
                .add-col-form select[name="schedule_id"],
                .add-col-form .chip-btn {
                    grid-column: 1 / -1;
                }

                .add-col-form input,
                .add-col-form select {
                    width: 100%;
                    min-height: 38px;
                }

                .add-col-form input[name="name"],
                .add-col-form select[name="schedule_id"] {
                    max-width: none;
                }

                .att-table-wrap {
                    max-height: calc(100dvh - 250px);
                    min-height: 320px;
                    -webkit-overflow-scrolling: touch;
                }

                .col-stt {
                    width: 38px !important;
                    min-width: 38px !important;
                    max-width: 38px !important;
                }

                .col-name {
                    left: 38px !important;
                    width: 120px !important;
                    min-width: 120px !important;
                    max-width: 120px !important;
                }

                .att-table thead .col-name {
                    padding-left: 0.65rem;
                }

                .stt-cell {
                    padding: 0.5rem 0.25rem;
                }

                .name-cell {
                    font-size: .74rem;
                    padding: 0.45rem 0.5rem;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                .att-table input[type="text"] {
                    min-width: 72px;
                    padding: 0.55rem 0.35rem;
                }

                .col-note-cell input {
                    min-width: 120px;
                }

                .attendance-control {
                    gap: 3px;
                    min-width: 98px;
                    padding: 3px;
                }

                .attendance-status-btn {
                    font-size: 9px;
                    gap: 3px;
                    min-height: 28px;
                    min-width: 66px;
                    padding: 4px 6px;
                }

                .attendance-status-btn i { font-size: 9px; }
                .attendance-note-btn { border-radius: 7px; height: 26px; width: 26px; }
                .attendance-column-meta { font-size: 8px; }
                .col-header-inner { min-width: 96px; padding: .4rem 1rem .4rem .3rem; }

                .btn-delete-col {
                    opacity: 1;
                }

                .att-footer {
                    align-items: stretch;
                    flex-direction: column;
                    padding: 0.85rem;
                    border-radius: 0 0 var(--r-md) var(--r-md);
                }

                .att-hint {
                    line-height: 1.35;
                }

                .btn-save {
                    justify-content: center;
                    width: 100%;
                }

                .save-flash {
                    right: 0.75rem;
                    bottom: 0.75rem;
                    left: 0.75rem;
                    justify-content: center;
                }
            }

            @media (min-width: 576px) and (max-width: 767.98px) {
                .att-actions {
                    grid-template-columns: 1fr;
                    align-items: center;
                }

                .att-primary-actions,
                .att-search {
                    grid-column: 1 / -1;
                }

                .add-col-form {
                    grid-column: 1 / -1;
                    grid-template-columns: 1fr 1fr;
                    align-items: center;
                }
            }
        </style>
    @endpush

    <div class="att-page">

        {{-- ── TOOLBAR ── --}}
        <div class="att-toolbar">
            <div class="att-title-block">
                <h5><i class="fas fa-clipboard-check"></i> Điểm danh & Điểm số</h5>
                <small>{{ $course->title }}{{ $isStudentView ? ' · Dữ liệu của bạn' : '' }}</small>
            </div>

            <div class="att-actions">
                @unless ($isStudentView)
                <div class="att-primary-actions">
                    <div class="att-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="filterName" placeholder="Tìm tên học sinh...">
                    </div>
                    <a href="{{ route('attendance.export', $course->id) }}" class="chip-btn chip-green"
                        data-no-page-transition data-file-download>
                        <i class="fas fa-file-excel"></i> Xuất Excel
                    </a>
                    <button type="button" class="chip-btn chip-green" id="markAllPresentBtn">
                        <i class="fas fa-user-check"></i> Buổi mới nhất: tất cả có mặt
                    </button>
                </div>

                {{-- Add column --}}
                <form action="{{ route('attendance.addColumn', $course->id) }}" method="POST" class="add-col-form">
                    @csrf
                    <input type="text" name="name" placeholder="Tên cột (có thể để trống)">
                    <select name="type" id="newColumnType">
                        <option value="attendance">Điểm danh</option>
                        <option value="grade">Điểm số</option>
                        <option value="note">Ghi chú</option>
                    </select>
                    <input type="date" name="attendance_date" value="{{ now()->format('Y-m-d') }}"
                        class="attendance-only-field" aria-label="Ngày điểm danh">
                    <select name="schedule_id" class="attendance-only-field" aria-label="Liên kết lịch học">
                        <option value="">Không liên kết lịch</option>
                        @foreach ($schedules as $schedule)
                            <option value="{{ $schedule->id }}" data-date="{{ $schedule->schedule_date }}">
                                {{ \Carbon\Carbon::parse($schedule->schedule_date)->format('d/m/Y') }} ·
                                {{ substr($schedule->start_time, 0, 5) }} · {{ $schedule->class_name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="chip-btn chip-blue">
                        <i class="fas fa-plus"></i> Thêm cột
                    </button>
                </form>

                @endunless
            </div>
        </div>

        {{-- ── TABLE ── --}}
        <form action="{{ route('attendance.save', $course->id) }}" method="POST" id="att-form">
            @csrf
            <div class="att-table-wrap">
                <table class="att-table">
                    <thead>
                        <tr>
                            <th class="col-stt">STT</th>
                            <th class="col-name">Họ và Tên</th>
                            @foreach ($columns as $col)
                                @php
                                    $typeClass = match ($col->type) {
                                        'attendance' => 'col-attendance-h',
                                        'grade' => 'col-grade-h',
                                        default => 'col-note-h',
                                    };
                                @endphp
                                <th class="{{ $typeClass }}">
                                    <div class="col-header-inner">
                                        <span class="editable-name" @unless ($isStudentView) contenteditable="true"
                                            data-col-id="{{ $col->id }}" onblur="updateColumnName(this)" @endunless>{{ $col->name }}</span>
                                        @if ($col->type === 'attendance' && $col->schedule)
                                            <small class="attendance-column-meta">
                                                {{ substr($col->schedule->start_time, 0, 5) }}
                                            </small>
                                        @endif
                                        @unless ($isStudentView)
                                        <i class="fas fa-times btn-delete-col"
                                            onclick="deleteColumn({{ $col->id }}, '{{ addslashes($col->name) }}')"></i>
                                        @endunless
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody id="studentList">
                        @forelse ($students as $index => $student)
                            <tr class="student-row">
                                <td class="col-stt">
                                    <div class="stt-cell">{{ $index + 1 }}</div>
                                </td>
                                <td class="col-name">
                                    <div class="name-cell">{{ $student->name }}</div>
                                </td>
                                @foreach ($columns as $col)
                                    @php
                                        $cellClass = match ($col->type) {
                                            'attendance' => 'col-attendance-cell',
                                            'grade' => 'col-grade-cell',
                                            default => 'col-note-cell',
                                        };
                                        $ph = match ($col->type) {
                                            'attendance' => '—',
                                            'grade' => '0',
                                            default => '',
                                        };
                                    @endphp
                                    <td class="{{ $cellClass }}" style="padding:0;">
                                        @if ($col->type === 'attendance')
                                            @php
                                                $status = $attendanceData[$student->id][$col->id] ?? 'present';
                                                $statusLabels = ['present' => 'Có mặt', 'absent' => 'Vắng', 'late' => 'Đi muộn', 'excused' => 'Có phép'];
                                                $statusIcons = ['present' => 'check', 'absent' => 'xmark', 'late' => 'clock', 'excused' => 'file-circle-check'];
                                                $note = $attendanceNotes[$student->id][$col->id] ?? '';
                                                $noteId = "attendance-note-{$col->id}-{$student->id}";
                                            @endphp
                                            <div class="attendance-control">
                                                <input type="hidden" class="attendance-value"
                                                    data-column-id="{{ $col->id }}"
                                                    name="data[{{ $col->id }}][{{ $student->id }}]" value="{{ $status }}">
                                                <button type="button" class="attendance-status-btn status-{{ $status }}"
                                                    data-status="{{ $status }}" @disabled($isStudentView)>
                                                    <i class="fas fa-{{ $statusIcons[$status] ?? 'check' }}"></i>
                                                    <span>{{ $statusLabels[$status] ?? 'Có mặt' }}</span>
                                                </button>
                                                <input type="hidden" id="{{ $noteId }}"
                                                    name="notes[{{ $col->id }}][{{ $student->id }}]" value="{{ $note }}">
                                                <button type="button" class="attendance-note-btn {{ $note ? 'has-note' : '' }}"
                                                    data-note-input="{{ $noteId }}" title="{{ $note ?: 'Thêm ghi chú' }}" @disabled($isStudentView)>
                                                    <i class="fas fa-note-sticky"></i>
                                                </button>
                                            </div>
                                        @else
                                            <input type="text" name="data[{{ $col->id }}][{{ $student->id }}]"
                                                value="{{ $attendanceData[$student->id][$col->id] ?? '' }}"
                                                placeholder="{{ $ph }}" @if ($isStudentView) readonly aria-label="{{ $col->name }}" @endif>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) + 2 }}">
                                    <div class="att-empty">
                                        <i class="fas fa-user-slash d-block"></i>
                                        <p>Chưa có học sinh nào trong khóa học này</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ── FOOTER ── --}}
            <div class="att-footer">
                <div class="att-hint">
                    <i class="fas fa-{{ $isStudentView ? 'circle-info' : 'lightbulb' }}"></i>
                    {{ $isStudentView ? 'Dữ liệu do giáo viên cập nhật và chỉ bạn có thể xem dòng này.' : 'Click vào tên cột để đổi tên. Hover vào cột để xóa.' }}
                </div>
                @unless ($isStudentView)
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Lưu bảng điểm
                </button>
                @endunless
            </div>
        </form>

    </div>

    {{-- Hidden delete form --}}
    @unless ($isStudentView)
    <form id="delete-column-form" method="POST" style="display:none;">
        @csrf @method('DELETE')
    </form>

    {{-- Save flash --}}
    <div class="save-flash" id="saveFlash"><i class="fas fa-check-circle"></i> Đã lưu thành công!</div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        // ── Update column name on blur ──
        function updateColumnName(el) {
            const colId = el.getAttribute('data-col-id');
            const newName = el.innerText.trim();
            if (!newName) return;

            axios.post(`/attendance/column/${colId}/update`, {
                    name: newName
                })
                .then(() => {
                    el.style.color = 'var(--green-600)';
                    setTimeout(() => el.style.color = '', 900);
                })
                .catch(() => {
                    alert('Không thể cập nhật tên cột');
                    location.reload();
                });
        }

        // ── Delete column ──
        function deleteColumn(id, name) {
            if (!confirm(`Xóa cột "${name}" và toàn bộ dữ liệu bên dưới?`)) return;
            const form = document.getElementById('delete-column-form');
            form.action = `/attendance/column/${id}`;
            form.submit();
        }

        // ── Filter students ──
        document.getElementById('filterName').addEventListener('input', function() {
            const val = this.value.toLowerCase();
            document.querySelectorAll('.student-row').forEach(row => {
                const name = row.querySelector('.col-name').innerText.toLowerCase();
                row.style.display = name.includes(val) ? '' : 'none';
            });
        });

        // ── Select all on focus ──
        const attendanceStates = {
            present: { label: 'Có mặt', icon: 'fa-check' },
            absent: { label: 'Vắng', icon: 'fa-xmark' },
            late: { label: 'Đi muộn', icon: 'fa-clock' },
            excused: { label: 'Có phép', icon: 'fa-file-circle-check' },
        };
        const attendanceOrder = ['present', 'absent', 'late', 'excused'];

        function setAttendanceStatus(button, status) {
            const input = button.closest('.attendance-control').querySelector('.attendance-value');
            const state = attendanceStates[status] || attendanceStates.present;
            input.value = status;
            button.dataset.status = status;
            button.className = `attendance-status-btn status-${status}`;
            button.querySelector('i').className = `fas ${state.icon}`;
            button.querySelector('span').textContent = state.label;
        }

        document.querySelectorAll('.attendance-status-btn:not(:disabled)').forEach(button => {
            button.addEventListener('click', function() {
                const currentIndex = attendanceOrder.indexOf(this.dataset.status);
                setAttendanceStatus(this, attendanceOrder[(currentIndex + 1) % attendanceOrder.length]);
            });
        });

        document.querySelectorAll('.attendance-note-btn:not(:disabled)').forEach(button => {
            button.addEventListener('click', function() {
                const noteInput = document.getElementById(this.dataset.noteInput);
                const note = window.prompt('Ghi chú riêng cho học sinh trong buổi này:', noteInput.value);
                if (note === null) return;
                noteInput.value = note.trim();
                this.classList.toggle('has-note', noteInput.value !== '');
                this.title = noteInput.value || 'Thêm ghi chú';
            });
        });

        document.getElementById('markAllPresentBtn')?.addEventListener('click', function() {
            const inputs = [...document.querySelectorAll('.attendance-value')];
            if (!inputs.length) {
                alert('Chưa có buổi điểm danh nào.');
                return;
            }
            const latestColumnId = inputs[inputs.length - 1].dataset.columnId;
            inputs.filter(input => input.dataset.columnId === latestColumnId).forEach(input => {
                setAttendanceStatus(input.closest('.attendance-control').querySelector('.attendance-status-btn'), 'present');
            });
        });

        const columnType = document.getElementById('newColumnType');
        const attendanceFields = document.querySelectorAll('.attendance-only-field');
        const toggleAttendanceFields = () => attendanceFields.forEach(field => {
            const hidden = columnType.value !== 'attendance';
            field.hidden = hidden;
            field.disabled = hidden;
        });
        columnType?.addEventListener('change', toggleAttendanceFields);
        toggleAttendanceFields();

        const scheduleSelect = document.querySelector('select[name="schedule_id"]');
        scheduleSelect?.addEventListener('change', function() {
            const date = this.selectedOptions[0]?.dataset.date;
            if (date) document.querySelector('input[name="attendance_date"]').value = date;
        });

        document.querySelectorAll('.att-table input[type="text"]').forEach(inp => {
            inp.addEventListener('focus', () => inp.select());

            // Tab/Enter navigation
            inp.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const inputs = [...document.querySelectorAll('.att-table input[type="text"]')];
                    const idx = inputs.indexOf(this);
                    if (idx >= 0 && idx < inputs.length - 1) inputs[idx + 1].focus();
                }
            });
        });

        // ── Save flash ──
        document.getElementById('att-form').addEventListener('submit', function() {
            const flash = document.getElementById('saveFlash');
            flash.classList.add('show');
            setTimeout(() => flash.classList.remove('show'), 2200);
        });
    </script>
    @endunless
@endsection
