@extends('layouts.app')

@section('title', 'Lịch giảng dạy')

@section('content')
    <style>
        /* ── Reset & Base ── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        .sch-page {
            font-family: 'Be Vietnam Pro', sans-serif;
        }

        /* ── Page Header ── */
        .sch-header {
            margin-bottom: 20px;
        }

        .sch-header h1 {
            font-size: 20px;
            font-weight: 600;
            color: #263A37;
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 3px;
        }

        .sch-header h1 i {
            color: #54726E;
            font-size: 19px;
        }

        .sch-header p {
            font-size: 13px;
            color: #61736F;
            margin: 0;
        }

        /* ── Panel ── */
        .sch-panel {
            background: #fff;
            border: 1px solid #D9DDD3;
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 12px;
        }

        .sch-panel-head {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            margin-bottom: 14px;
        }

        .sch-panel-icon {
            width: 30px;
            height: 30px;
            min-width: 30px;
            border-radius: 8px;
            background: #EEF5F2;
            color: #54726E;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            margin-top: 1px;
        }

        .sch-panel-title {
            font-size: 13.5px;
            font-weight: 600;
            color: #263A37;
            margin: 0 0 2px;
        }

        .sch-panel-sub {
            font-size: 12px;
            color: #61736F;
            margin: 0;
        }

        .sch-fields {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .sch-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .sch-field label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .055em;
            color: #7C8986;
        }

        .sch-ctrl {
            height: 34px;
            padding: 0 11px;
            border: 1px solid #D9DDD3;
            border-radius: 9px;
            font-size: 13px;
            font-family: 'Be Vietnam Pro', sans-serif;
            color: #263A37;
            background: #fff;
            transition: border-color .15s, box-shadow .15s;
            appearance: auto;
        }

        .sch-ctrl:focus {
            border-color: #54726E;
            box-shadow: 0 0 0 3px rgba(84, 114, 110, .1);
            outline: none;
        }

        .sch-ctrl:disabled {
            background: #F7F7F2;
            color: #7C8986;
            cursor: not-allowed;
        }

        input[type="file"].sch-ctrl {
            height: auto;
            padding: 5px 11px;
            font-size: 12.5px;
        }

        /* ── Buttons ── */
        .sch-btn {
            height: 34px;
            padding: 0 16px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 500;
            font-family: 'Be Vietnam Pro', sans-serif;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            transition: background .15s;
        }

        .sch-btn-primary {
            background: #54726E;
            color: #fff;
        }

        .sch-btn-primary:hover {
            background: #385652;
        }

        .sch-btn-ghost {
            background: #EFEDDE;
            color: #263A37;
        }

        .sch-btn-ghost:hover {
            background: #D9DDD3;
        }

        .sch-btn-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .sch-btn-danger:hover {
            background: #fee2e2;
            border-color: #fca5a5;
        }

        /* ── Calendar Card ── */
        .sch-cal-card {
            background: #fff;
            border: 1px solid #D9DDD3;
            border-radius: 14px;
            padding: 18px 20px 16px;
        }

        /* ── FullCalendar Overrides ── */
        #sch-calendar {
            font-family: 'Be Vietnam Pro', sans-serif;
        }

        .fc .fc-toolbar-title {
            font-size: 16px;
            font-weight: 600;
            color: #263A37;
        }

        .fc .fc-button {
            background: #fff !important;
            border: 1px solid #D9DDD3 !important;
            color: #263A37 !important;
            border-radius: 9px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            padding: 5px 13px !important;
            box-shadow: none !important;
            transition: background .15s, border-color .15s !important;
        }

        .fc .fc-button:hover {
            background: #EFEDDE !important;
            border-color: #BCC8BF !important;
            color: #263A37 !important;
        }

        .fc .fc-button-primary:not(:disabled).fc-button-active,
        .fc .fc-button-primary:not(:disabled):active {
            background: #54726E !important;
            border-color: #54726E !important;
            color: #fff !important;
        }

        .fc .fc-button-group .fc-button {
            border-radius: 0 !important;
        }

        .fc .fc-button-group .fc-button:first-child {
            border-radius: 9px 0 0 9px !important;
        }

        .fc .fc-button-group .fc-button:last-child {
            border-radius: 0 9px 9px 0 !important;
        }

        .fc .fc-col-header-cell-cushion {
            font-size: 12px;
            font-weight: 600;
            color: #61736F;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .fc .fc-timegrid-slot-label-cushion {
            font-size: 11.5px;
            color: #7C8986;
        }

        .fc .fc-event {
            border-radius: 6px !important;
            border: none !important;
            padding: 2px 6px !important;
            font-size: 12px !important;
            font-weight: 500 !important;
            cursor: grab;
            transition: opacity .15s, filter .15s, transform .15s;
            touch-action: manipulation;
        }

        .fc .fc-event:hover {
            filter: brightness(.95);
        }

        .fc .fc-event:active,
        .fc .fc-event.fc-event-dragging {
            cursor: grabbing;
        }

        .fc .fc-event.fc-event-dragging,
        .fc .fc-event.fc-event-resizing,
        .fc .fc-event.fc-event-mirror {
            opacity: .78;
            box-shadow: 0 8px 22px rgba(56, 86, 82, .25);
        }

        .fc .fc-timegrid-event .fc-event-resizer {
            height: 8px;
        }

        .is-saving-schedule .fc-event {
            pointer-events: none;
        }

        .is-saving-schedule .fc-event:not(.fc-event-dragging) {
            opacity: .62;
        }

        .fc .fc-daygrid-event {
            border-radius: 6px !important;
            font-size: 12px !important;
        }

        .fc .fc-highlight {
            background: #EEF5F2 !important;
        }

        .fc .fc-day-today {
            background: rgba(176, 218, 210, .22) !important;
        }

        .fc td,
        .fc th {
            border-color: #EFEDDE !important;
        }

        .fc .fc-scrollgrid {
            border-color: #D9DDD3 !important;
        }

        .fc .fc-today-button {
            font-weight: 600 !important;
        }

        .fc-direction-ltr .fc-toolbar>*> :not(:first-child) {
            margin-left: 6px;
        }

        .sch-calendar-guide {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 13px;
            padding: 9px 11px;
            border: 1px solid #DCE9E5;
            border-radius: 10px;
            color: #526864;
            background: #F3F8F6;
            font-size: 11.5px;
        }

        .sch-calendar-guide i {
            color: #54726E;
        }

        .sch-calendar-filters {
            display: grid;
            grid-template-columns: repeat(5, minmax(120px, 1fr)) auto;
            align-items: end;
            gap: 9px;
            margin-bottom: 12px;
            padding: 12px;
            border: 1px solid #D9DDD3;
            border-radius: 11px;
            background: #FAFAF7;
        }

        .sch-calendar-filters .sch-field {
            min-width: 0;
        }

        .sch-calendar-filters .sch-ctrl {
            width: 100%;
        }

        .sch-calendar-legend {
            display: flex;
            align-items: center;
            gap: 13px;
            margin: -2px 0 12px;
            color: #61736F;
            font-size: 11px;
        }

        .sch-calendar-legend span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .sch-calendar-legend i {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #54726E;
        }

        .sch-calendar-legend .is-exam i {
            background: #dc2626;
        }

        .sch-alert--undo {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .sch-undo-button {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            gap: 6px;
            padding: 6px 11px;
            border: 1px solid rgba(56, 86, 82, .28);
            border-radius: 8px;
            color: #385652;
            background: #fff;
            font: inherit;
            font-weight: 700;
        }

        .sch-undo-button:hover {
            border-color: #385652;
            background: #EFEDDE;
        }

        /* ── Modal ── */
        #scheduleModal .modal-dialog {
            max-width: 720px;
        }

        #scheduleModal .modal-content {
            overflow: hidden;
            border: 1px solid rgba(84, 114, 110, .2);
            border-radius: 20px;
            box-shadow: 0 24px 70px rgba(56, 86, 82, .2);
        }

        #scheduleModal .modal-header {
            align-items: flex-start;
            padding: 22px 26px 18px;
            border-bottom: 1px solid #D9DDD3;
            background: linear-gradient(135deg, #F8F7EF 0%, #fff 72%);
        }

        .sch-modal-heading {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .sch-modal-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            flex: 0 0 40px;
            border-radius: 12px;
            color: #54726E;
            background: #EEF5F2;
            font-size: 17px;
        }

        #scheduleModal .modal-title {
            margin: 0 0 3px;
            color: #263A37;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.35;
        }

        .sch-modal-subtitle {
            margin: 0;
            color: #61736F;
            font-size: 12px;
            line-height: 1.5;
        }

        #scheduleModal .btn-close {
            width: 32px;
            height: 32px;
            margin: 2px 0 0 auto;
            padding: 0;
            border-radius: 9px;
            background-size: 11px;
            opacity: .55;
        }

        #scheduleModal .btn-close:hover {
            background-color: #EFEDDE;
            opacity: .85;
        }

        #scheduleModal .modal-body {
            padding: 20px 26px 22px;
        }

        #scheduleModal .modal-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 15px 26px;
            border-top: 1px solid #D9DDD3;
            background: #F7F7F2;
        }

        .modal-lbl {
            display: flex;
            align-items: center;
            gap: 3px;
            margin-bottom: 7px;
            color: #61736F;
            font-size: 12.5px;
            font-weight: 600;
            line-height: 1.3;
        }

        .modal-required {
            color: #ef4444;
        }

        #scheduleModal .sch-ctrl {
            height: 42px;
            border-color: #D9DDD3;
            border-radius: 10px;
            padding: 0 12px;
            font-size: 13.5px;
            background-color: #fff;
        }

        #scheduleModal .sch-ctrl:hover:not(:disabled) {
            border-color: #BCC8BF;
        }

        #scheduleModal .sch-ctrl:focus {
            border-color: #54726E;
            box-shadow: 0 0 0 3px rgba(84, 114, 110, .11);
        }

        .sch-modal-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px 14px;
        }

        .modal-grp {
            min-width: 0;
        }

        .modal-grp--wide {
            grid-column: 1 / -1;
        }

        .sch-exam-option {
            display: flex;
            align-items: center;
            gap: 11px;
            grid-column: 1 / -1;
            min-height: 52px;
            margin-top: 1px;
            padding: 10px 12px;
            border: 1px solid #D9DDD3;
            border-radius: 11px;
            background: #F7F7F2;
            cursor: pointer;
            transition: border-color .15s, background-color .15s, box-shadow .15s;
        }

        .sch-exam-option:hover {
            border-color: #B0DAD2;
            background: #EEF5F2;
        }

        .sch-exam-option:has(input:checked) {
            border-color: #6E928D;
            background: #EEF5F2;
            box-shadow: 0 0 0 3px rgba(84, 114, 110, .07);
        }

        .sch-exam-option .form-check-input {
            width: 17px;
            height: 17px;
            flex: 0 0 17px;
            margin: 0;
        }

        .sch-exam-option__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            flex: 0 0 30px;
            border-radius: 8px;
            color: #705817;
            background: #FFF8DF;
        }

        .sch-exam-option__copy {
            display: flex;
            flex-direction: column;
            gap: 1px;
            min-width: 0;
        }

        .sch-exam-option__copy strong {
            color: #263A37;
            font-size: 13px;
            font-weight: 600;
        }

        .sch-exam-option__copy small {
            color: #7C8986;
            font-size: 11.5px;
        }

        .sch-recurrence {
            grid-column: 1 / -1;
            overflow: hidden;
            border: 1px solid #D9DDD3;
            border-radius: 13px;
            background: #F7F7F2;
        }

        .sch-recurrence-toggle {
            display: flex;
            align-items: center;
            gap: 11px;
            width: 100%;
            padding: 12px 14px;
            cursor: pointer;
        }

        .sch-recurrence-toggle .form-check-input {
            width: 34px;
            height: 18px;
            flex: 0 0 34px;
            margin: 0;
        }

        .sch-recurrence-toggle__copy {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .sch-recurrence-toggle__copy strong {
            color: #263A37;
            font-size: 13px;
            font-weight: 600;
        }

        .sch-recurrence-toggle__copy small {
            color: #7C8986;
            font-size: 11.5px;
        }

        .sch-recurrence-body {
            padding: 14px;
            border-top: 1px solid #D9DDD3;
            background: #fff;
        }

        .sch-recurrence-fields {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .sch-recurrence-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 13px;
        }

        .sch-skip-option {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #61736F;
            font-size: 12px;
            cursor: pointer;
        }

        .sch-preview {
            max-height: 210px;
            overflow: auto;
            margin-top: 13px;
            border: 1px solid #D9DDD3;
            border-radius: 10px;
        }

        .sch-preview-summary {
            position: sticky;
            top: 0;
            z-index: 1;
            padding: 9px 11px;
            border-bottom: 1px solid #D9DDD3;
            color: #61736F;
            background: #F7F7F2;
            font-size: 11.5px;
            font-weight: 600;
        }

        .sch-preview-item {
            display: grid;
            grid-template-columns: 34px minmax(135px, .8fr) minmax(0, 1.4fr);
            align-items: center;
            gap: 8px;
            padding: 8px 11px;
            border-bottom: 1px solid #EFEDDE;
            font-size: 11.5px;
        }

        .sch-preview-item:last-child {
            border-bottom: 0;
        }

        .sch-preview-position {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 27px;
            height: 27px;
            border-radius: 8px;
            color: #54726E;
            background: #EEF5F2;
            font-weight: 700;
        }

        .sch-preview-item--conflict .sch-preview-position {
            color: #705817;
            background: #FFF8DF;
        }

        .sch-preview-date {
            color: #263A37;
            font-weight: 600;
        }

        .sch-preview-status {
            color: #547565;
        }

        .sch-preview-item--conflict .sch-preview-status {
            color: #705817;
        }

        .sch-series-scope {
            grid-column: 1 / -1;
            padding: 13px 14px;
            border: 1px solid #B0DAD2;
            border-radius: 12px;
            background: #EEF5F2;
        }

        .sch-series-scope__title {
            margin: 0 0 8px;
            color: #385652;
            font-size: 12.5px;
            font-weight: 700;
        }

        .sch-series-scope__options {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
        }

        .sch-series-scope__options label {
            display: flex;
            align-items: center;
            gap: 7px;
            margin: 0;
            padding: 7px 11px;
            border: 1px solid #DCE9E5;
            border-radius: 9px;
            color: #61736F;
            background: #fff;
            font-size: 12px;
            cursor: pointer;
        }

        /* ── Bulk schedule adjustment ── */
        .sch-bulk-panel {
            background: linear-gradient(135deg, #fff 0%, #F1F7F5 100%);
            border-color: #B0DAD2;
        }

        .sch-bulk-panel__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .sch-bulk-panel .sch-panel-head {
            margin-bottom: 0;
        }

        .sch-bulk-history {
            display: grid;
            gap: 7px;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid #DCE9E5;
        }

        .sch-bulk-history__row {
            display: grid;
            grid-template-columns: minmax(150px, 1fr) minmax(170px, 1.4fr) auto auto;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border: 1px solid #E3E8E1;
            border-radius: 10px;
            background: rgba(255, 255, 255, .86);
            color: #61736F;
            font-size: 11.5px;
        }

        .sch-bulk-history__row strong {
            color: #263A37;
            font-size: 12px;
        }

        .sch-bulk-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            border-radius: 999px;
            color: #385652;
            background: #E2F1ED;
            font-weight: 600;
        }

        .sch-bulk-status--undone {
            color: #6C7062;
            background: #EFEDDE;
        }

        .sch-btn-small {
            height: 28px;
            padding: 0 10px;
            font-size: 11.5px;
        }

        #bulkScheduleModal .modal-dialog {
            max-width: 980px;
        }

        #bulkScheduleModal .modal-content {
            overflow: hidden;
            border: 1px solid rgba(84, 114, 110, .2);
            border-radius: 20px;
            box-shadow: 0 24px 70px rgba(56, 86, 82, .2);
        }

        #bulkScheduleModal .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #D9DDD3;
            background: linear-gradient(135deg, #F1F7F5 0%, #fff 72%);
        }

        #bulkScheduleModal .modal-body {
            padding: 20px 24px;
        }

        #bulkScheduleModal .modal-footer {
            padding: 14px 24px;
            border-top: 1px solid #D9DDD3;
            background: #F7F7F2;
        }

        #scheduleDragScopeModal .modal-dialog {
            max-width: 510px;
        }

        #scheduleDragScopeModal .modal-content {
            overflow: hidden;
            border: 1px solid rgba(84, 114, 110, .2);
            border-radius: 18px;
            box-shadow: 0 22px 64px rgba(56, 86, 82, .22);
        }

        #scheduleDragScopeModal .modal-header {
            padding: 19px 21px 15px;
            border-bottom: 1px solid #D9DDD3;
            background: linear-gradient(135deg, #F1F7F5, #fff);
        }

        #scheduleDragScopeModal .modal-body {
            padding: 18px 21px;
        }

        #scheduleDragScopeModal .modal-footer {
            gap: 8px;
            padding: 13px 21px;
            border-top: 1px solid #D9DDD3;
            background: #F7F7F2;
        }

        .sch-drag-summary {
            display: grid;
            gap: 9px;
            padding: 12px 13px;
            border: 1px solid #DCE9E5;
            border-radius: 11px;
            background: #F6FAF8;
            font-size: 12px;
        }

        .sch-drag-summary strong {
            color: #263A37;
        }

        .sch-drag-change {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 8px;
            color: #61736F;
        }

        .sch-drag-change span:last-child {
            color: #385652;
            font-weight: 700;
        }

        .sch-drag-warning {
            margin: 12px 0 0;
            color: #705817;
            font-size: 11.5px;
            line-height: 1.5;
        }

        .sch-bulk-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .sch-bulk-section {
            min-width: 0;
            padding: 14px;
            border: 1px solid #D9DDD3;
            border-radius: 13px;
            background: #fff;
        }

        .sch-bulk-section--wide {
            grid-column: 1 / -1;
        }

        .sch-bulk-section__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .sch-bulk-section__head strong {
            color: #263A37;
            font-size: 13px;
        }

        .sch-link-button {
            padding: 0;
            border: 0;
            color: #54726E;
            background: transparent;
            font-size: 11.5px;
            font-weight: 600;
        }

        .sch-check-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 7px;
            max-height: 160px;
            overflow: auto;
            padding-right: 3px;
        }

        .sch-check-option {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            min-width: 0;
            margin: 0;
            padding: 8px 9px;
            border: 1px solid #E3E8E1;
            border-radius: 9px;
            color: #455A56;
            background: #F9FAF7;
            font-size: 12px;
            cursor: pointer;
        }

        .sch-check-option:has(input:checked) {
            border-color: #98C8BF;
            background: #EEF5F2;
        }

        .sch-check-option input {
            flex: 0 0 auto;
            margin-top: 1px;
        }

        .sch-bulk-fields {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
        }

        .sch-bulk-fields .sch-field label {
            display: block;
        }

        .sch-bulk-fields .sch-ctrl {
            width: 100%;
        }

        .sch-bulk-preview {
            max-height: 330px;
            overflow: auto;
            margin-top: 16px;
            border: 1px solid #D9DDD3;
            border-radius: 12px;
        }

        .sch-bulk-summary {
            position: sticky;
            top: 0;
            z-index: 2;
            padding: 10px 12px;
            border-bottom: 1px solid #D9DDD3;
            color: #385652;
            background: #EEF5F2;
            font-size: 12px;
            font-weight: 700;
        }

        .sch-bulk-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
        }

        .sch-bulk-table th,
        .sch-bulk-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #EFEDDE;
            text-align: left;
            vertical-align: top;
        }

        .sch-bulk-table th {
            color: #61736F;
            background: #F9FAF7;
            font-weight: 600;
        }

        .sch-bulk-table tr.is-conflict td {
            background: #FFF8E5;
        }

        .sch-bulk-result-ok {
            color: #547565;
            font-weight: 600;
        }

        .sch-bulk-result-error {
            color: #9A6712;
            font-weight: 600;
        }

        .footer-r {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-left: auto;
        }

        /* ── Alert ── */
        .sch-alert {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 14px;
        }

        .sch-alert--success {
            background: #EDF5EF;
            border-color: #AEC6A6;
            color: #547565;
        }

        /* ── Responsive ── */
        @media (max-width: 767.98px) {
            .sch-panel {
                padding: 14px 14px;
            }

            .sch-cal-card {
                padding: 12px;
                border-radius: 12px;
            }

            .sch-calendar-guide {
                align-items: flex-start;
            }

            .sch-calendar-guide__desktop {
                display: none;
            }

            .sch-calendar-filters {
                grid-template-columns: 1fr;
            }

            .sch-calendar-legend {
                align-items: flex-start;
                flex-direction: column;
                gap: 5px;
            }

            .sch-fields {
                flex-direction: column;
                align-items: stretch;
            }

            .sch-btn {
                justify-content: center;
            }

            .fc .fc-toolbar {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .fc .fc-toolbar-chunk {
                display: flex;
                justify-content: center;
            }

            .fc .fc-toolbar-title {
                font-size: 14px;
                text-align: center;
            }

            .fc .fc-button {
                padding: 5px 9px !important;
                font-size: 12px !important;
            }

            #scheduleModal .modal-dialog {
                max-width: none;
                margin: 10px;
            }

            #scheduleModal .modal-content {
                min-height: auto;
                border-radius: 16px;
            }

            #scheduleModal .modal-header {
                padding: 18px;
            }

            #scheduleModal .modal-body {
                padding: 18px;
            }

            #scheduleModal .modal-footer {
                padding: 14px 18px;
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .sch-modal-grid {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .modal-grp--wide,
            .sch-exam-option,
            .sch-recurrence,
            .sch-series-scope {
                grid-column: auto;
            }

            .sch-recurrence-fields {
                grid-template-columns: 1fr;
            }

            .sch-recurrence-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .sch-bulk-panel__top {
                align-items: stretch;
                flex-direction: column;
            }

            .sch-bulk-history__row {
                grid-template-columns: 1fr;
            }

            #bulkScheduleModal .modal-dialog {
                margin: 10px;
            }

            #scheduleDragScopeModal .modal-dialog {
                margin: 10px;
            }

            #scheduleDragScopeModal .modal-footer {
                align-items: stretch;
                flex-direction: column;
            }

            #bulkScheduleModal .modal-header,
            #bulkScheduleModal .modal-body,
            #bulkScheduleModal .modal-footer {
                padding: 16px;
            }

            .sch-bulk-grid,
            .sch-check-grid,
            .sch-bulk-fields {
                grid-template-columns: 1fr;
            }

            .sch-bulk-section--wide {
                grid-column: auto;
            }

            .sch-bulk-table {
                min-width: 720px;
            }

            .sch-preview-item {
                grid-template-columns: 30px minmax(0, 1fr);
            }

            .sch-preview-status {
                grid-column: 2;
            }

            .footer-r {
                margin-left: 0;
                display: grid;
                grid-template-columns: 1fr 1fr;
                width: 100%;
            }

            .sch-btn-danger,
            .sch-btn-ghost,
            .sch-btn-primary {
                justify-content: center;
                width: 100%;
            }
        }
    </style>

    <div class="sch-page">

        {{-- Header --}}
        <div class="sch-header">
            <h1><i class="fa-solid fa-calendar-days"></i>Lịch giảng dạy</h1>
            <p>Nhấp vào ô trống để thêm, nhấp vào lịch để sửa hoặc xóa</p>
        </div>

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="sch-alert">{{ $errors->first() }}</div>
        @endif

        {{-- Bulk adjustment panel --}}
        <div class="sch-panel sch-bulk-panel">
            <div class="sch-bulk-panel__top">
                <div class="sch-panel-head">
                    <div class="sch-panel-icon"><i class="fa-solid fa-calendar-days"></i></div>
                    <div>
                        <p class="sch-panel-title">Điều chỉnh lịch hàng loạt</p>
                        <p class="sch-panel-sub">Chọn nhiều lớp hoặc khóa học, xem trước xung đột rồi dời toàn bộ lịch theo ngày hoặc tuần.</p>
                    </div>
                </div>
                <button type="button" class="sch-btn sch-btn-primary" data-bs-toggle="modal"
                    data-bs-target="#bulkScheduleModal">
                    <i class="fa-solid fa-arrows-left-right-to-line"></i> Điều chỉnh lịch
                </button>
            </div>

            @if ($recentAdjustments->isNotEmpty())
                <div class="sch-bulk-history" aria-label="Lịch sử điều chỉnh gần đây">
                    @foreach ($recentAdjustments as $adjustment)
                        <div class="sch-bulk-history__row" data-adjustment-id="{{ $adjustment->public_id }}">
                            <strong>{{ $adjustment->created_at->format('d/m/Y H:i') }}</strong>
                            <span>
                                {{ $adjustment->schedule_count }} buổi ·
                                {{ $adjustment->shift_days > 0 ? 'Dời tới' : 'Dời lùi' }}
                                {{ abs($adjustment->shift_days) }} ngày
                                @if ($adjustment->creator)
                                    · {{ $adjustment->creator->name }}
                                @endif
                            </span>
                            <span class="sch-bulk-status {{ $adjustment->status === 'undone' ? 'sch-bulk-status--undone' : '' }}">
                                <i class="fa-solid {{ $adjustment->status === 'undone' ? 'fa-rotate-left' : 'fa-circle-check' }}"></i>
                                {{ $adjustment->status === 'undone' ? 'Đã hoàn tác' : 'Đã áp dụng' }}
                            </span>
                            @if ($adjustment->status === 'applied')
                                <button type="button" class="sch-btn sch-btn-ghost sch-btn-small js-undo-adjustment"
                                    data-url="{{ route('schedules.bulk-adjustments.undo', $adjustment) }}">
                                    <i class="fa-solid fa-rotate-left"></i> Hoàn tác
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Copy panel --}}
        <div class="sch-panel">
            <div class="sch-panel-head">
                <div class="sch-panel-icon"><i class="fa-solid fa-copy"></i></div>
                <div>
                    <p class="sch-panel-title">Sao chép lịch theo ngày</p>
                    <p class="sch-panel-sub">Chọn ngày đã có lịch và dán sang một ngày khác</p>
                </div>
            </div>
            <form method="POST" action="{{ route('schedules.copyDay') }}" class="sch-fields">
                @csrf
                <div class="sch-field">
                    <label for="source_date">Ngày nguồn</label>
                    <input type="date" class="sch-ctrl" id="source_date" name="source_date"
                        value="{{ old('source_date') }}" required>
                </div>
                <div class="sch-field">
                    <label for="target_date">Ngày đích</label>
                    <input type="date" class="sch-ctrl" id="target_date" name="target_date"
                        value="{{ old('target_date') }}" required>
                </div>
                <button type="submit" class="sch-btn sch-btn-primary">
                    <i class="fa-solid fa-clone"></i> Sao chép
                </button>
            </form>
        </div>

        {{-- Import panel --}}
        <div class="sch-panel">
            <div class="sch-panel-head">
                <div class="sch-panel-icon"><i class="fa-solid fa-file-import"></i></div>
                <div>
                    <p class="sch-panel-title">Nhập lịch từ Excel</p>
                    <p class="sch-panel-sub">
                        Hỗ trợ cột: Lớp, Ngày, Giờ học, Tên môn học, Phòng học.
                        Nhiều lớp trong một ô cách nhau bằng dấu chấm phẩy.
                    </p>
                </div>
            </div>
            <form method="POST" action="{{ route('schedules.import') }}" enctype="multipart/form-data" class="sch-fields">
                @csrf
                <div class="sch-field">
                    <label for="import_class_id">Lớp mặc định</label>
                    <select class="sch-ctrl" id="import_class_id" name="import_class_id" style="min-width:140px;">
                        <option value="">Tự lấy từ cột Lớp...</option>
                        @foreach ($classes as $cls)
                            <option value="{{ $cls->id }}" @selected(old('import_class_id') == $cls->id)>
                                {{ $cls->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="sch-field">
                    <label for="default_course_id">Khóa mặc định</label>
                    <select class="sch-ctrl" id="default_course_id" name="default_course_id" style="min-width:160px;"
                        disabled>
                        <option value="">Tự khớp theo tên môn...</option>
                    </select>
                </div>
                <div class="sch-field">
                    <label for="schedule_file">File Excel</label>
                    <input type="file" class="sch-ctrl" id="schedule_file" name="file" accept=".xlsx,.xls,.csv"
                        required>
                </div>
                <button type="submit" class="sch-btn sch-btn-primary">
                    <i class="fa-solid fa-upload"></i> Nhập lịch
                </button>
            </form>
        </div>

        {{-- Calendar --}}
        <div id="scheduleFeedback" class="sch-alert d-none" role="alert"></div>
        <div class="sch-cal-card">
            <form class="sch-calendar-filters" id="calendarFilters" aria-label="Bộ lọc lịch giảng dạy">
                <div class="sch-field">
                    <label for="calendar_class_id">Lớp học</label>
                    <select class="sch-ctrl" id="calendar_class_id">
                        <option value="">Tất cả lớp</option>
                        @foreach ($classes as $cls)
                            <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sch-field">
                    <label for="calendar_course_id">Khóa học</label>
                    <select class="sch-ctrl" id="calendar_course_id">
                        <option value="">Tất cả khóa học</option>
                        @foreach ($bulkCourses as $course)
                            <option value="{{ $course->id }}">{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                @if (auth()->user()->isAdmin())
                    <div class="sch-field">
                        <label for="calendar_teacher_id">Giáo viên</label>
                        <select class="sch-ctrl" id="calendar_teacher_id">
                            <option value="">Tất cả giáo viên</option>
                            @foreach ($calendarTeachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="sch-field">
                    <label for="calendar_room">Phòng học</label>
                    <select class="sch-ctrl" id="calendar_room">
                        <option value="">Tất cả phòng</option>
                        @foreach ($calendarRooms as $room)
                            <option value="{{ $room }}">{{ $room }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sch-field">
                    <label for="calendar_kind">Loại lịch</label>
                    <select class="sch-ctrl" id="calendar_kind">
                        <option value="">Tất cả</option>
                        <option value="regular">Lịch học thường</option>
                        <option value="exam">Lịch thi/ghi chú</option>
                    </select>
                </div>
                <button class="sch-btn sch-btn-ghost" type="button" id="resetCalendarFilters">
                    <i class="fa-solid fa-rotate-left"></i> Đặt lại
                </button>
            </form>
            <div class="sch-calendar-guide">
                <i class="fa-solid fa-hand-pointer" aria-hidden="true"></i>
                <span>
                    Nhấp vào lịch để xem chi tiết.
                    <span class="sch-calendar-guide__desktop">Kéo sang vị trí khác để đổi ngày/giờ; kéo cạnh trên hoặc dưới để đổi thời lượng.</span>
                </span>
            </div>
            <div class="sch-calendar-legend" aria-label="Chú giải màu lịch">
                <span><i aria-hidden="true"></i>Mỗi cặp lớp–khóa học có một màu ổn định</span>
                <span class="is-exam"><i aria-hidden="true"></i>Đỏ: lịch thi hoặc có ghi chú</span>
            </div>
            <div id="sch-calendar"
                data-events-url="{{ route('schedules.index') }}"
                data-store-url="{{ route('schedules.store') }}"
                data-series-store-url="{{ route('schedules.series.store') }}"
                data-series-preview-url="{{ route('schedules.series.preview') }}"
                data-bulk-preview-url="{{ route('schedules.bulk-adjustments.preview') }}"
                data-bulk-store-url="{{ route('schedules.bulk-adjustments.store') }}"
                data-update-url-template="{{ route('schedules.update', '__ID__') }}"
                data-delete-url-template="{{ route('schedules.destroy', '__ID__') }}"
                data-courses-url-template="{{ url('/schedules/get-courses/__ID__') }}"
                data-old-import-course-id="{{ old('default_course_id') }}"></div>
        </div>

    </div>{{-- /sch-page --}}

    {{-- ── Modal ── --}}
    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="modalTitle" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <div class="sch-modal-heading">
                        <span class="sch-modal-icon" id="scheduleModalIcon" aria-hidden="true">
                            <i class="fa-solid fa-calendar-plus"></i>
                        </span>
                        <div>
                            <h5 class="modal-title" id="modalTitle">Thêm lịch học mới</h5>
                            <p class="sch-modal-subtitle" id="scheduleModalSubtitle">
                                Khai báo lớp, khóa học và khung giờ cho buổi học.
                            </p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="schedule_id">
                    <div id="scheduleModalError" class="sch-alert d-none" role="alert"></div>

                    <div class="sch-modal-grid">
                        <div class="modal-grp modal-grp--wide">
                            <label class="modal-lbl" for="class_id">
                                Lớp học <span class="modal-required">*</span>
                            </label>
                            <select class="sch-ctrl w-100" id="class_id" required>
                                <option value="">-- Chọn lớp học --</option>
                                @foreach ($classes as $cls)
                                    <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="modal-grp modal-grp--wide">
                            <label class="modal-lbl" for="course_id">
                                Khóa học <span class="modal-required">*</span>
                            </label>
                            <select class="sch-ctrl w-100" id="course_id" required disabled>
                                <option value="">Vui lòng chọn lớp trước...</option>
                            </select>
                        </div>

                        <div class="modal-grp">
                            <label class="modal-lbl" for="schedule_date">
                                Ngày học <span class="modal-required">*</span>
                            </label>
                            <input type="date" class="sch-ctrl w-100" id="schedule_date" required>
                        </div>
                        <div class="modal-grp">
                            <label class="modal-lbl" for="room">Phòng học</label>
                            <input type="text" class="sch-ctrl w-100" id="room"
                                placeholder="VD: Phòng 302, Online">
                        </div>

                        <div class="modal-grp">
                            <label class="modal-lbl" for="start_time">
                                Giờ bắt đầu <span class="modal-required">*</span>
                            </label>
                            <input type="time" class="sch-ctrl w-100" id="start_time" required>
                        </div>
                        <div class="modal-grp">
                            <label class="modal-lbl" for="end_time">
                                Giờ kết thúc <span class="modal-required">*</span>
                            </label>
                            <input type="time" class="sch-ctrl w-100" id="end_time" required>
                        </div>

                        <label class="sch-exam-option" for="note_exam">
                            <input class="form-check-input" type="checkbox" value="1" id="note_exam">
                            <span class="sch-exam-option__icon" aria-hidden="true">
                                <i class="fa-solid fa-graduation-cap"></i>
                            </span>
                            <span class="sch-exam-option__copy">
                                <strong>Đây là buổi thi kết thúc môn</strong>
                                <small>Lịch sẽ được làm nổi bật để học viên dễ nhận biết.</small>
                            </span>
                        </label>

                        <div class="sch-recurrence" id="recurrenceCreateSection">
                            <label class="sch-recurrence-toggle" for="repeat_enabled">
                                <input class="form-check-input" type="checkbox" role="switch" id="repeat_enabled">
                                <span class="sch-recurrence-toggle__copy">
                                    <strong>Lặp lại lịch học</strong>
                                    <small>Tạo lịch hàng tuần hoặc cách tuần và kiểm tra trùng trước khi lưu.</small>
                                </span>
                            </label>
                            <div class="sch-recurrence-body d-none" id="recurrenceBody">
                                <div class="sch-recurrence-fields">
                                    <div class="modal-grp">
                                        <label class="modal-lbl" for="repeat_interval">Tần suất</label>
                                        <select class="sch-ctrl w-100" id="repeat_interval">
                                            <option value="1">Hàng tuần</option>
                                            <option value="2">Cách tuần</option>
                                        </select>
                                    </div>
                                    <div class="modal-grp">
                                        <label class="modal-lbl" for="end_mode">Kết thúc theo</label>
                                        <select class="sch-ctrl w-100" id="end_mode">
                                            <option value="count">Số buổi</option>
                                            <option value="date">Ngày kết thúc</option>
                                        </select>
                                    </div>
                                    <div class="modal-grp" id="occurrenceCountGroup">
                                        <label class="modal-lbl" for="occurrence_count">Tổng số buổi</label>
                                        <input type="number" class="sch-ctrl w-100" id="occurrence_count" min="2"
                                            max="104" value="8">
                                    </div>
                                    <div class="modal-grp d-none" id="repeatUntilGroup">
                                        <label class="modal-lbl" for="repeat_until">Ngày kết thúc</label>
                                        <input type="date" class="sch-ctrl w-100" id="repeat_until">
                                    </div>
                                </div>
                                <div class="sch-recurrence-actions">
                                    <label class="sch-skip-option" for="skip_conflicts">
                                        <input class="form-check-input" type="checkbox" id="skip_conflicts">
                                        Bỏ qua các buổi bị trùng khi tạo
                                    </label>
                                    <button type="button" class="sch-btn sch-btn-ghost" id="btnPreviewSeries">
                                        <i class="fa-solid fa-list-check"></i> Xem trước chuỗi
                                    </button>
                                </div>
                                <div class="sch-preview d-none" id="seriesPreview" aria-live="polite">
                                    <div class="sch-preview-summary" id="seriesPreviewSummary"></div>
                                    <div id="seriesPreviewList"></div>
                                </div>
                            </div>
                        </div>

                        <div class="sch-series-scope d-none" id="seriesEditSection">
                            <p class="sch-series-scope__title">
                                <i class="fa-solid fa-link me-1"></i> Buổi học này thuộc một chuỗi lặp lại
                            </p>
                            <div class="sch-series-scope__options">
                                <label for="series_scope_occurrence">
                                    <input class="form-check-input" type="radio" name="series_scope"
                                        id="series_scope_occurrence" value="occurrence" checked>
                                    Chỉ buổi này
                                </label>
                                <label for="series_scope_all">
                                    <input class="form-check-input" type="radio" name="series_scope"
                                        id="series_scope_all" value="series">
                                    Cả chuỗi
                                </label>
                                <label for="series_scope_future">
                                    <input class="form-check-input" type="radio" name="series_scope"
                                        id="series_scope_future" value="future">
                                    Buổi này và các buổi sau
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="sch-btn sch-btn-danger d-none" id="btnDelete">
                        <i class="fa-solid fa-archive"></i> Lưu trữ lịch
                    </button>
                    <div class="footer-r">
                        <button type="button" class="sch-btn sch-btn-ghost" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="sch-btn sch-btn-primary" id="btnSave">
                            <i class="fa-solid fa-check"></i> Lưu lịch
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Bulk adjustment modal --}}
    <div class="modal fade" id="bulkScheduleModal" tabindex="-1" aria-labelledby="bulkScheduleModalTitle" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="sch-modal-heading">
                        <span class="sch-modal-icon" aria-hidden="true"><i class="fa-solid fa-calendar-check"></i></span>
                        <div>
                            <h5 class="modal-title" id="bulkScheduleModalTitle">Điều chỉnh lịch hàng loạt</h5>
                            <p class="sch-modal-subtitle">Hệ thống chỉ áp dụng khi toàn bộ buổi học không còn xung đột.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="modal-body">
                    <div id="bulkScheduleError" class="sch-alert d-none" role="alert"></div>
                    <form id="bulkScheduleForm">
                        <div class="sch-bulk-grid">
                            <section class="sch-bulk-section">
                                <div class="sch-bulk-section__head">
                                    <strong>1. Chọn lớp học</strong>
                                    <button type="button" class="sch-link-button js-toggle-bulk-options" data-target="bulkClassOptions">Chọn tất cả</button>
                                </div>
                                <div class="sch-check-grid" id="bulkClassOptions">
                                    @foreach ($classes as $cls)
                                        <label class="sch-check-option">
                                            <input type="checkbox" name="bulk_class_ids" value="{{ $cls->id }}">
                                            <span>{{ $cls->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </section>

                            <section class="sch-bulk-section">
                                <div class="sch-bulk-section__head">
                                    <strong>2. Lọc khóa học</strong>
                                    <button type="button" class="sch-link-button js-toggle-bulk-options" data-target="bulkCourseOptions">Chọn tất cả</button>
                                </div>
                                <div class="sch-check-grid" id="bulkCourseOptions">
                                    @foreach ($bulkCourses as $course)
                                        <label class="sch-check-option">
                                            <input type="checkbox" name="bulk_course_ids" value="{{ $course->id }}">
                                            <span>{{ $course->title }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <p class="sch-panel-sub mt-2">Không chọn khóa nào để lấy tất cả khóa thuộc các lớp đã chọn.</p>
                            </section>

                            <section class="sch-bulk-section sch-bulk-section--wide">
                                <div class="sch-bulk-section__head">
                                    <strong>3. Khoảng thời gian và độ dịch chuyển</strong>
                                </div>
                                <div class="sch-bulk-fields">
                                    <div class="sch-field">
                                        <label for="bulk_date_from">Từ ngày</label>
                                        <input class="sch-ctrl" type="date" id="bulk_date_from" value="{{ now()->toDateString() }}" required>
                                    </div>
                                    <div class="sch-field">
                                        <label for="bulk_date_to">Đến ngày</label>
                                        <input class="sch-ctrl" type="date" id="bulk_date_to" value="{{ now()->addMonth()->toDateString() }}" required>
                                    </div>
                                    <div class="sch-field">
                                        <label for="bulk_direction">Hướng dời</label>
                                        <select class="sch-ctrl" id="bulk_direction">
                                            <option value="forward">Dời tới</option>
                                            <option value="backward">Dời lùi</option>
                                        </select>
                                    </div>
                                    <div class="sch-field">
                                        <label for="bulk_shift_amount">Số lượng</label>
                                        <input class="sch-ctrl" type="number" id="bulk_shift_amount" value="1" min="1" max="365" required>
                                    </div>
                                    <div class="sch-field">
                                        <label for="bulk_shift_unit">Đơn vị</label>
                                        <select class="sch-ctrl" id="bulk_shift_unit">
                                            <option value="day">Ngày</option>
                                            <option value="week">Tuần</option>
                                        </select>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </form>

                    <div class="sch-bulk-preview d-none" id="bulkSchedulePreview" aria-live="polite">
                        <div class="sch-bulk-summary" id="bulkScheduleSummary"></div>
                        <div class="table-responsive">
                            <table class="sch-bulk-table">
                                <thead>
                                    <tr>
                                        <th>Lớp / khóa học</th>
                                        <th>Thời gian</th>
                                        <th>Ngày cũ</th>
                                        <th>Ngày mới</th>
                                        <th>Kết quả</th>
                                    </tr>
                                </thead>
                                <tbody id="bulkSchedulePreviewBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="sch-btn sch-btn-ghost" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="sch-btn sch-btn-ghost" id="btnPreviewBulkSchedule">
                        <i class="fa-solid fa-magnifying-glass"></i> Xem trước xung đột
                    </button>
                    <button type="button" class="sch-btn sch-btn-primary" id="btnApplyBulkSchedule" disabled>
                        <i class="fa-solid fa-check-double"></i> Áp dụng điều chỉnh
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Drag / resize scope modal --}}
    <div class="modal fade" id="scheduleDragScopeModal" tabindex="-1" aria-labelledby="scheduleDragScopeTitle" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="sch-modal-heading">
                        <span class="sch-modal-icon" aria-hidden="true"><i class="fa-solid fa-arrows-up-down-left-right"></i></span>
                        <div>
                            <h5 class="modal-title" id="scheduleDragScopeTitle">Áp dụng thay đổi lịch</h5>
                            <p class="sch-modal-subtitle">Buổi học này thuộc một chuỗi lịch lặp lại.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Hủy thay đổi"></button>
                </div>
                <div class="modal-body">
                    <div class="sch-drag-summary">
                        <strong id="scheduleDragEventTitle">Lịch học</strong>
                        <div class="sch-drag-change">
                            <span id="scheduleDragOldTime">—</span>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            <span id="scheduleDragNewTime">—</span>
                        </div>
                    </div>
                    <p class="sch-drag-warning">
                        Hệ thống sẽ kiểm tra lại trùng lớp, giáo viên và phòng trước khi lưu. Có thể áp dụng độ dịch chuyển cho riêng buổi này, từ buổi này trở đi hoặc toàn bộ chuỗi.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="sch-btn sch-btn-ghost" data-bs-dismiss="modal">Hủy thay đổi</button>
                    <button type="button" class="sch-btn sch-btn-ghost js-save-drag-scope" data-scope="occurrence">
                        Chỉ buổi này
                    </button>
                    <button type="button" class="sch-btn sch-btn-ghost js-save-drag-scope" data-scope="future">
                        Buổi này và các buổi sau
                    </button>
                    <button type="button" class="sch-btn sch-btn-primary js-save-drag-scope" data-scope="series">
                        <i class="fa-solid fa-link"></i> Cả chuỗi
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @vite('resources/js/pages/schedules.js')
@endpush
