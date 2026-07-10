@extends('layouts.app')

@section('title', 'Bảng điều khiển')

@push('styles')
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --brand: #4f7fff;
            --brand-dark: #2952e3;
            --brand-light: #eef3ff;
            --brand-glow: rgba(79, 127, 255, 0.18);
            --accent: #8b5cf6;
            --accent-light: #f3efff;
            --success: #10b981;
            --success-light: #ecfdf5;
            --warning: #f59e0b;
            --warning-light: #fffbeb;
            --danger: #f43f5e;
            --danger-light: #fff1f4;
            --info: #06b6d4;
            --info-light: #ecfeff;

            --surface: #ffffff;
            --surface-2: #f7f9fc;
            --surface-3: #eef2f9;
            --border: rgba(0, 0, 0, .07);
            --border-strong: rgba(0, 0, 0, .12);
            --text: #0d1b2a;
            --text-2: #374151;
            --text-muted: #6b7280;
            --text-light: #9ca3af;

            --radius-xs: 6px;
            --radius-sm: 10px;
            --radius: 16px;
            --radius-lg: 22px;
            --radius-xl: 28px;

            --shadow-xs: 0 1px 2px rgba(0, 0, 0, .05);
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, .06), 0 0 1px rgba(0, 0, 0, .04);
            --shadow: 0 4px 20px rgba(0, 0, 0, .07), 0 1px 4px rgba(0, 0, 0, .04);
            --shadow-md: 0 8px 32px rgba(0, 0, 0, .09), 0 2px 8px rgba(0, 0, 0, .05);
            --shadow-lg: 0 16px 48px rgba(0, 0, 0, .11), 0 4px 16px rgba(0, 0, 0, .06);
            --shadow-brand: 0 8px 32px rgba(79, 127, 255, .22);

            --font: 'Be Vietnam Pro', sans-serif;
            --font-mono: 'DM Mono', monospace;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: var(--font);
        }

        .greeting-banner {
            position: relative;
            background: var(--text);
            border-radius: var(--radius-xl);
            min-height: 200px;
            overflow: hidden;
            display: flex;
            align-items: stretch;
            margin-bottom: 1.75rem;
            box-shadow: 0 20px 60px rgba(13, 27, 42, .28);
        }

        .greeting-banner__bg {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 55% 90% at -5% 50%, rgba(79, 127, 255, .55) 0%, transparent 60%),
                radial-gradient(ellipse 50% 80% at 105% 60%, rgba(139, 92, 246, .45) 0%, transparent 55%),
                radial-gradient(ellipse 40% 50% at 50% 110%, rgba(6, 182, 212, .2) 0%, transparent 50%);
        }

        .greeting-banner__noise {
            position: absolute;
            inset: 0;
            opacity: .04;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 512 512' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        }

        .greeting-banner__grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, .03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .03) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        .greeting-banner__content {
            position: relative;
            z-index: 2;
            padding: 2.25rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .greeting-pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: rgba(255, 255, 255, .1);
            border: 1px solid rgba(255, 255, 255, .15);
            border-radius: 100px;
            padding: .28rem .75rem;
            font-size: .72rem;
            font-weight: 700;
            color: rgba(255, 255, 255, .75);
            letter-spacing: .04em;
            text-transform: uppercase;
            margin-bottom: .85rem;
            width: fit-content;
            backdrop-filter: blur(8px);
        }

        .greeting-pill .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #4ade80;
            box-shadow: 0 0 6px #4ade80;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: .6;
                transform: scale(.85);
            }
        }

        .greeting-title {
            font-size: 2rem;
            font-weight: 900;
            color: #fff;
            margin: 0 0 .4rem;
            letter-spacing: -.04em;
            line-height: 1.15;
        }

        .greeting-date {
            color: rgba(255, 255, 255, .5);
            font-size: .85rem;
            font-weight: 500;
            margin: 0;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .greeting-banner__img-wrap {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: center;
            align-items: flex-end;
            padding-bottom: 0;
        }

        .greeting-banner__img {
            max-height: 180px;
            width: auto;
            filter: drop-shadow(0 12px 32px rgba(0, 0, 0, .35));
            transform: translateY(4px);
        }

        /* Floating orbs */
        .greeting-banner__orbs {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 1;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(40px);
            opacity: .25;
        }

        .orb-1 {
            width: 220px;
            height: 220px;
            background: var(--brand);
            top: -60px;
            left: -40px;
        }

        .orb-2 {
            width: 180px;
            height: 180px;
            background: var(--accent);
            bottom: -60px;
            right: 20%;
        }

        .orb-3 {
            width: 120px;
            height: 120px;
            background: var(--info);
            top: 20%;
            right: 15%;
        }

        /* =========================================
                                       QUICK ACTIONS
                    ========================================= */
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
            margin-bottom: 1.75rem;
        }

        .quick-action {
            align-items: center;
            background: var(--surface);
            border: 1.5px solid var(--border-strong);
            border-radius: var(--radius-sm);
            color: var(--text-2);
            display: inline-flex;
            font-size: .82rem;
            font-weight: 700;
            gap: .5rem;
            height: 40px;
            padding: 0 1rem;
            text-decoration: none;
            transition: all .18s cubic-bezier(.4, 0, .2, 1);
            white-space: nowrap;
            box-shadow: var(--shadow-xs);
        }

        .quick-action i {
            font-size: .85rem;
            color: var(--brand);
            transition: transform .18s;
        }

        .quick-action:hover {
            background: var(--brand);
            border-color: var(--brand);
            color: #fff;
            box-shadow: var(--shadow-brand);
            transform: translateY(-1px);
        }

        .quick-action:hover i {
            color: #fff;
            transform: scale(1.1);
        }

        /* =========================================
                                       TEACHER FOCUS
                                    ========================================= */
        .teacher-priority-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: 1.5rem;
        }

        .teacher-priority-card {
            align-items: flex-start;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            color: inherit;
            display: flex;
            gap: .9rem;
            min-height: 128px;
            padding: 1.1rem;
            position: relative;
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .teacher-priority-card:hover {
            border-color: rgba(79, 127, 255, .28);
            box-shadow: var(--shadow-md);
            color: inherit;
            transform: translateY(-2px);
        }

        .teacher-priority-card__icon {
            align-items: center;
            border-radius: var(--radius-sm);
            display: flex;
            flex-shrink: 0;
            font-size: 1rem;
            height: 42px;
            justify-content: center;
            width: 42px;
        }

        .teacher-priority-card__label {
            color: var(--text-muted);
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .08em;
            margin-bottom: .3rem;
            text-transform: uppercase;
        }

        .teacher-priority-card__value {
            color: var(--text);
            font-size: 1.65rem;
            font-weight: 900;
            letter-spacing: -.04em;
            line-height: 1;
            margin-bottom: .35rem;
        }

        .teacher-priority-card__hint {
            color: var(--text-muted);
            font-size: .76rem;
            line-height: 1.4;
        }

        .teacher-priority-card__arrow {
            bottom: 1rem;
            color: var(--text-light);
            font-size: .75rem;
            position: absolute;
            right: 1rem;
        }

        .teacher-priority-card--danger .teacher-priority-card__icon {
            background: var(--danger-light);
            color: var(--danger);
        }

        .teacher-priority-card--blue .teacher-priority-card__icon {
            background: var(--brand-light);
            color: var(--brand);
        }

        .teacher-priority-card--amber .teacher-priority-card__icon {
            background: var(--warning-light);
            color: var(--warning);
        }

        .teacher-priority-card--green .teacher-priority-card__icon {
            background: var(--success-light);
            color: var(--success);
        }

        .teacher-action-strip {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding: .8rem;
        }

        .teacher-action-strip .quick-action {
            box-shadow: none;
            flex: 1 1 150px;
            justify-content: center;
            margin: 0;
        }

        .teacher-action-strip .quick-action--ai {
            background: linear-gradient(135deg, var(--brand-light), var(--accent-light));
            border-color: rgba(139, 92, 246, .25);
            color: #6d28d9;
        }

        .teacher-action-strip .quick-action--ai i {
            color: var(--accent);
        }

        .teacher-action-strip .quick-action--ai:hover {
            background: linear-gradient(135deg, var(--brand), var(--accent));
            border-color: transparent;
            color: #fff;
        }

        .teacher-action-strip .quick-action--ai:hover i {
            color: #fff;
        }

        .teacher-list-scroll {
            max-height: 360px;
            overflow-y: auto;
        }

        .teacher-schedule-list {
            display: none;
        }

        .teacher-schedule-card {
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.15rem;
        }

        .teacher-schedule-card:last-child {
            border-bottom: none;
        }

        .teacher-command-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: minmax(0, .92fr) minmax(0, 1.08fr);
            margin-bottom: 1.5rem;
        }

        .teacher-next-class {
            background: var(--text);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            color: #fff;
            min-height: 100%;
            overflow: hidden;
            padding: 1.25rem;
            position: relative;
        }

        .teacher-next-class::before {
            background:
                radial-gradient(ellipse 60% 80% at 0% 20%, rgba(79, 127, 255, .45), transparent 60%),
                radial-gradient(ellipse 50% 70% at 100% 90%, rgba(16, 185, 129, .28), transparent 55%);
            content: '';
            inset: 0;
            position: absolute;
        }

        .teacher-next-class>* {
            position: relative;
            z-index: 1;
        }

        .teacher-next-class__eyebrow,
        .teacher-ai-panel__eyebrow,
        .priority-submissions__eyebrow {
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .1em;
            margin-bottom: .45rem;
            text-transform: uppercase;
        }

        .teacher-next-class__eyebrow {
            color: rgba(255, 255, 255, .58);
        }

        .teacher-next-class__title {
            color: #fff;
            font-size: 1.25rem;
            font-weight: 900;
            letter-spacing: -.03em;
            line-height: 1.25;
            margin: 0 0 .55rem;
        }

        .teacher-next-class__meta {
            color: rgba(255, 255, 255, .68);
            display: flex;
            flex-wrap: wrap;
            font-size: .8rem;
            gap: .45rem;
            margin-bottom: 1rem;
        }

        .teacher-next-class__meta span {
            align-items: center;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 999px;
            display: inline-flex;
            gap: .35rem;
            padding: .32rem .7rem;
        }

        .teacher-ai-panel,
        .priority-submissions {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            height: 100%;
            overflow: hidden;
        }

        .teacher-ai-panel {
            padding: 1.15rem;
            background: linear-gradient(180deg, var(--surface) 0%, #fbfaff 100%);
            border-color: rgba(139, 92, 246, .16);
        }

        .teacher-ai-panel__eyebrow,
        .priority-submissions__eyebrow {
            color: var(--accent);
        }

        .teacher-ai-panel__title {
            color: var(--text);
            font-size: 1rem;
            font-weight: 900;
            letter-spacing: -.02em;
            margin: 0 0 .85rem;
        }

        .teacher-ai-suggestion {
            align-items: flex-start;
            border-top: 1px solid var(--border);
            display: flex;
            gap: .75rem;
            padding: .85rem 0;
        }

        .teacher-ai-suggestion:first-of-type {
            border-top: none;
            padding-top: 0;
        }

        .teacher-ai-suggestion__icon {
            align-items: center;
            border-radius: var(--radius-xs);
            display: inline-flex;
            flex-shrink: 0;
            height: 32px;
            justify-content: center;
            width: 32px;
        }

        .teacher-ai-suggestion__title {
            color: var(--text);
            font-size: .84rem;
            font-weight: 800;
            line-height: 1.3;
            margin-bottom: .18rem;
        }

        .teacher-ai-suggestion__body {
            color: var(--text-muted);
            font-size: .76rem;
            line-height: 1.45;
            margin-bottom: .45rem;
        }

        .teacher-ai-suggestion--danger .teacher-ai-suggestion__icon {
            background: var(--danger-light);
            color: var(--danger);
        }

        .teacher-ai-suggestion--warning .teacher-ai-suggestion__icon {
            background: var(--warning-light);
            color: var(--warning);
        }

        .teacher-ai-suggestion--primary .teacher-ai-suggestion__icon,
        .teacher-ai-suggestion--info .teacher-ai-suggestion__icon {
            background: linear-gradient(135deg, var(--brand-light), var(--accent-light));
            color: var(--accent);
        }

        .teacher-ai-suggestion--success .teacher-ai-suggestion__icon {
            background: var(--success-light);
            color: var(--success);
        }

        .teacher-ai-suggestion--muted .teacher-ai-suggestion__icon {
            background: var(--surface-3);
            color: var(--text-muted);
        }

        .priority-submission {
            align-items: flex-start;
            border-top: 1px solid var(--border);
            display: flex;
            gap: .85rem;
            justify-content: space-between;
            padding: 1rem 1.15rem;
        }

        .priority-submission:first-of-type {
            border-top: none;
        }

        .priority-submission__main {
            display: flex;
            gap: .75rem;
            min-width: 0;
        }

        .priority-submission__title {
            color: var(--text);
            font-size: .85rem;
            font-weight: 800;
            line-height: 1.3;
            margin-bottom: .2rem;
        }

        .priority-submission__meta {
            color: var(--text-muted);
            font-size: .75rem;
            line-height: 1.4;
        }

        .stat-card {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            padding: 1.35rem 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: transform .22s cubic-bezier(.4, 0, .2, 1), box-shadow .22s cubic-bezier(.4, 0, .2, 1);
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0) 60%, rgba(255, 255, 255, .5) 100%);
            pointer-events: none;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .stat-card__icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .stat-card__body {
            flex: 1;
            min-width: 0;
        }

        .stat-card__label {
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--text-muted);
            margin-bottom: .3rem;
        }

        .stat-card__value {
            font-size: 2rem;
            font-weight: 900;
            line-height: 1;
            color: var(--text);
            letter-spacing: -.04em;
            font-variant-numeric: tabular-nums;
        }

        .stat-card__trend {
            font-size: .72rem;
            font-weight: 600;
            margin-top: .35rem;
            display: flex;
            align-items: center;
            gap: .25rem;
            color: var(--text-muted);
        }

        /* variants */
        .stat-card--blue .stat-card__icon {
            background: var(--brand-light);
            color: var(--brand);
        }

        .stat-card--teal .stat-card__icon {
            background: var(--info-light);
            color: var(--info);
        }

        .stat-card--green .stat-card__icon {
            background: var(--success-light);
            color: var(--success);
        }

        .stat-card--amber .stat-card__icon {
            background: var(--warning-light);
            color: var(--warning);
        }

        .stat-card--red .stat-card__icon {
            background: var(--danger-light);
            color: var(--danger);
        }

        .stat-card--violet .stat-card__icon {
            background: var(--accent-light);
            color: var(--accent);
        }

        /* compact variant for dense teaching-overview rows */
        .stat-card--compact {
            padding: 1.05rem 1.25rem;
        }

        .stat-card--compact .stat-card__icon {
            width: 42px;
            height: 42px;
            font-size: 1.05rem;
        }

        .stat-card--compact .stat-card__value {
            font-size: 1.6rem;
        }

        /* accent stripe (teacher) */
        .stat-card--stripe {
            padding-left: 1.25rem;
        }

        .stat-card--stripe::before {
            content: '';
            position: absolute;
            left: 0;
            top: 16px;
            bottom: 16px;
            width: 3px;
            border-radius: 0 3px 3px 0;
        }

        .stat-card--stripe.stat-card--red::before {
            background: var(--danger);
        }

        .stat-card--stripe.stat-card--blue::before {
            background: var(--brand);
        }

        .stat-card--stripe.stat-card--green::before {
            background: var(--success);
        }

        .panel {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
            height: 100%;
        }

        .panel__header {
            padding: 1rem 1.35rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
        }

        .panel__title {
            font-size: .82rem;
            font-weight: 800;
            color: var(--text);
            margin: 0;
            display: flex;
            align-items: center;
            gap: .45rem;
            letter-spacing: -.01em;
        }

        .panel__title .icon-dot {
            width: 28px;
            height: 28px;
            border-radius: var(--radius-xs);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .8rem;
        }

        .tbl {
            width: 100%;
            border-collapse: collapse;
        }

        .tbl thead th {
            background: var(--surface-2);
            color: var(--text-muted);
            font-size: .68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            padding: .7rem 1.25rem;
            border-bottom: 1px solid var(--border);
        }

        .tbl tbody td {
            padding: .9rem 1.25rem;
            border-bottom: 1px solid var(--border);
            font-size: .84rem;
            color: var(--text-2);
            vertical-align: middle;
        }

        .tbl tbody tr:last-child td {
            border-bottom: none;
        }

        .tbl tbody tr {
            transition: background .12s;
        }

        .tbl tbody tr:hover td {
            background: var(--surface-2);
        }

        .tbl tbody tr.is-today td {
            background: linear-gradient(90deg, #eef3ff, #f7f9fc);
        }

        .tbl tbody tr.is-past td {
            opacity: .48;
        }

        .bdg {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .25em .7em;
            border-radius: 100px;
            font-size: .68rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: .02em;
        }

        .bdg--primary {
            background: var(--brand-light);
            color: var(--brand-dark);
        }

        .bdg--info {
            background: var(--info-light);
            color: #0e7490;
        }

        .bdg--success {
            background: var(--success-light);
            color: #065f46;
        }

        .bdg--warning {
            background: var(--warning-light);
            color: #92400e;
        }

        .bdg--danger {
            background: var(--danger-light);
            color: #be123c;
        }

        .bdg--muted {
            background: var(--surface-3);
            color: var(--text-muted);
            border: 1px solid var(--border);
        }

        .bdg--dark {
            background: var(--text);
            color: #f1f5f9;
        }

        .bdg--accent {
            background: var(--accent-light);
            color: #6d28d9;
        }

        .feed-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 1.35rem;
            border-bottom: 1px solid var(--border);
            transition: background .12s;
        }

        .feed-item:last-child {
            border-bottom: none;
        }

        .feed-item:hover {
            background: var(--surface-2);
        }

        .feed-item__avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--brand), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .8rem;
            font-weight: 800;
            color: white;
            flex-shrink: 0;
        }

        .feed-item__name {
            font-weight: 700;
            font-size: .85rem;
            color: var(--text);
            margin-bottom: .12rem;
        }

        .feed-item__meta {
            font-size: .76rem;
            color: var(--text-muted);
        }

        .feed-item__time {
            font-size: .72rem;
            color: var(--text-light);
            margin-bottom: .45rem;
        }

        .todo-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: .9rem 1.35rem;
            border-bottom: 1px solid var(--border);
            transition: background .12s;
        }

        .todo-item:last-child {
            border-bottom: none;
        }

        .todo-item:hover {
            background: var(--surface-2);
        }

        .todo-item--quiz {
            background: #fafbff;
        }

        .todo-item--quiz:hover {
            background: var(--brand-light);
        }

        .todo-item__label {
            font-weight: 700;
            font-size: .85rem;
            color: var(--text);
            margin-bottom: .1rem;
        }

        .todo-item__sub {
            font-size: .76rem;
            color: var(--text-muted);
        }

        .todo-item__deadline {
            font-size: .75rem;
            font-weight: 700;
            color: var(--danger);
            margin-bottom: .35rem;
        }

        .todo-item__time-limit {
            font-size: .75rem;
            font-weight: 700;
            color: var(--brand);
            margin-bottom: .35rem;
        }

        .score-hero {
            background: var(--text);
            border-radius: var(--radius);
            padding: 2rem 1.5rem;
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            box-shadow: var(--shadow-lg);
            border: none;
            position: relative;
            overflow: hidden;
        }

        .score-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 70% 70% at 20% 30%, rgba(79, 127, 255, .35) 0%, transparent 60%),
                radial-gradient(ellipse 60% 60% at 80% 80%, rgba(139, 92, 246, .3) 0%, transparent 55%);
        }

        .score-hero>* {
            position: relative;
            z-index: 1;
        }

        .score-hero__label {
            font-size: .68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: rgba(255, 255, 255, .45);
        }

        .score-hero__ring {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, .1);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            margin: .5rem 0;
        }

        .score-hero__ring::before {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            background: conic-gradient(from 0deg, var(--brand) 0%, var(--accent) 60%, transparent 60%);
            z-index: -1;
        }

        .score-hero__ring::after {
            content: '';
            position: absolute;
            inset: 4px;
            border-radius: 50%;
            background: rgba(13, 27, 42, .95);
        }

        .score-hero__value {
            font-size: 3rem;
            font-weight: 900;
            line-height: 1;
            color: #fff;
            letter-spacing: -.06em;
            position: relative;
            z-index: 1;
        }

        .score-hero__sub {
            font-size: .78rem;
            color: rgba(255, 255, 255, .4);
        }

        .btn-xs {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .38rem .85rem;
            border-radius: 100px;
            font-size: .73rem;
            font-weight: 700;
            cursor: pointer;
            border: 1.5px solid transparent;
            text-decoration: none;
            transition: all .15s cubic-bezier(.4, 0, .2, 1);
            white-space: nowrap;
            letter-spacing: .01em;
        }

        .btn-xs--danger {
            background: var(--danger-light);
            color: var(--danger);
            border-color: #fecdd3;
        }

        .btn-xs--danger:hover {
            background: var(--danger);
            color: #fff;
            border-color: var(--danger);
            box-shadow: 0 4px 12px rgba(244, 63, 94, .3);
        }

        .btn-xs--warning {
            background: var(--warning-light);
            color: #92400e;
            border-color: #fde68a;
        }

        .btn-xs--warning:hover {
            background: var(--warning);
            color: #fff;
            border-color: var(--warning);
        }

        .btn-xs--primary {
            background: var(--brand);
            color: #fff;
            border-color: var(--brand);
            box-shadow: 0 2px 8px rgba(79, 127, 255, .25);
        }

        .btn-xs--primary:hover {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
            box-shadow: 0 4px 16px rgba(79, 127, 255, .35);
            transform: translateY(-1px);
        }

        .btn-xs--ghost {
            background: transparent;
            color: var(--text-muted);
            border-color: var(--border-strong);
        }

        .btn-xs--ghost:hover {
            background: var(--surface-2);
            color: var(--text);
        }

        .compact-card {
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.35rem;
            transition: background .12s;
        }

        .compact-card:last-child {
            border-bottom: none;
        }

        .compact-card:hover {
            background: var(--surface-2);
        }

        .progress-line {
            background: var(--surface-3);
            border-radius: 999px;
            height: 6px;
            overflow: hidden;
        }

        .progress-line span {
            display: block;
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--brand), var(--accent));
            transition: width .4s ease;
        }

        .empty-state {
            text-align: center;
            padding: 2.5rem 2rem;
            color: var(--text-muted);
        }

        .empty-state .empty-icon {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-sm);
            background: var(--surface-2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto .75rem;
            font-size: 1.3rem;
            opacity: .5;
        }

        .empty-state p {
            font-size: .84rem;
            margin: 0;
            line-height: 1.5;
        }


        .section-heading {
            font-size: .7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--text-muted);
            margin: 1.75rem 0 .75rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .section-heading:first-child {
            margin-top: 0;
        }

        .section-heading::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .chart-wrap {
            padding: 1.25rem;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--brand), var(--accent));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .72rem;
            font-weight: 800;
            color: white;
            margin-right: .6rem;
            vertical-align: middle;
            flex-shrink: 0;
        }

        .idot--blue {
            background: var(--brand-light);
            color: var(--brand);
        }

        .idot--green {
            background: var(--success-light);
            color: var(--success);
        }

        .idot--amber {
            background: var(--warning-light);
            color: var(--warning);
        }

        .idot--red {
            background: var(--danger-light);
            color: var(--danger);
        }

        .idot--teal {
            background: var(--info-light);
            color: var(--info);
        }

        .idot--violet {
            background: var(--accent-light);
            color: var(--accent);
        }


        @media (max-width: 767.98px) {
            .dashboard-wrap {
                padding: .75rem !important;
            }

            .greeting-banner {
                min-height: 130px;
                border-radius: var(--radius-lg);
                margin-bottom: 1rem !important;
            }

            .greeting-banner__content {
                padding: 1.1rem 1.25rem;
            }

            .greeting-title {
                font-size: 1.3rem;
            }

            .greeting-banner__img-wrap {
                display: none;
            }

            .stat-card {
                padding: 1rem 1.1rem;
            }

            .stat-card__value {
                font-size: 1.65rem;
            }

            .panel__header {
                flex-wrap: wrap;
                gap: .5rem;
                padding: .85rem 1rem;
            }

            .tbl {
                min-width: 600px;
            }

            .tbl thead th,
            .tbl tbody td {
                padding: .7rem 1rem;
            }

            .feed-item,
            .todo-item {
                flex-direction: column;
                align-items: stretch;
                gap: .7rem;
                padding: .9rem 1rem;
            }

            .feed-item>div:last-child,
            .todo-item>div:last-child {
                text-align: left !important;
            }

            .btn-xs {
                justify-content: center;
                width: 100%;
            }

            .quick-actions {
                flex-direction: column;
                gap: .45rem;
            }

            .quick-action {
                justify-content: flex-start;
                width: 100%;
            }

            .teacher-priority-grid {
                grid-template-columns: 1fr;
                gap: .75rem;
                margin-bottom: 1rem;
            }

            .teacher-priority-card {
                min-height: auto;
                padding: 1rem;
            }

            .teacher-priority-card__value {
                font-size: 1.45rem;
            }

            .teacher-action-strip {
                align-items: stretch;
                flex-direction: column;
                gap: .5rem;
                margin-bottom: 1rem;
                padding: .65rem;
            }

            .teacher-action-strip .quick-action {
                flex: none;
                justify-content: flex-start;
            }

            .teacher-list-scroll {
                max-height: none;
                overflow: visible;
            }

            .teacher-schedule-table {
                display: none;
            }

            .teacher-schedule-list {
                display: block;
            }

            .teacher-command-grid {
                grid-template-columns: 1fr;
                gap: .75rem;
                margin-bottom: 1rem;
            }

            .teacher-next-class,
            .teacher-ai-panel {
                padding: 1rem;
            }

            .teacher-next-class__title {
                font-size: 1.05rem;
            }

            .priority-submission {
                flex-direction: column;
                gap: .7rem;
                padding: 1rem;
            }

            .priority-submission .btn-xs {
                width: 100%;
            }

            .score-hero {
                padding: 1.5rem;
                min-height: 200px;
            }
        }

        @media (min-width: 768px) and (max-width: 1199.98px) {
            .teacher-priority-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 576px) {
            .greeting-title {
                font-size: 1.15rem;
            }
        }


        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .anim-1 {
            animation: fadeUp .4s ease both;
        }

        .anim-2 {
            animation: fadeUp .4s .08s ease both;
        }

        .anim-3 {
            animation: fadeUp .4s .16s ease both;
        }

        .anim-4 {
            animation: fadeUp .4s .24s ease both;
        }

        .anim-5 {
            animation: fadeUp .4s .32s ease both;
        }

        /* Premium SaaS teacher dashboard redesign */
        body {
            background: linear-gradient(180deg, #f7faff 0%, #f6f8fc 48%, #f8fafc 100%);
        }

        .dashboard-wrap {
            max-width: 1440px;
            padding: 24px !important;
        }

        .lms-hero {
            background: linear-gradient(135deg, #eff6ff 0%, #eef2ff 45%, #f8fbff 100%);
            border: 1px solid rgba(37, 99, 235, .12);
            border-radius: 28px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .08);
            display: grid;
            grid-template-columns: minmax(0, 1.25fr) minmax(280px, .75fr);
            gap: 24px;
            margin-bottom: 24px;
            overflow: hidden;
            padding: 28px;
            position: relative;
        }

        .lms-hero::before {
            background:
                linear-gradient(115deg, transparent 0 54%, rgba(37, 99, 235, .08) 54.3% 54.8%, transparent 55.1%),
                linear-gradient(24deg, transparent 0 58%, rgba(14, 165, 233, .08) 58.3% 58.8%, transparent 59.1%),
                linear-gradient(90deg, rgba(37, 99, 235, .06) 1px, transparent 1px),
                linear-gradient(0deg, rgba(37, 99, 235, .05) 1px, transparent 1px);
            background-size: 320px 180px, 260px 160px, 34px 34px, 34px 34px;
            bottom: 0;
            content: '';
            height: 78%;
            left: 0;
            mask-image: linear-gradient(180deg, transparent 0%, #000 42%, #000 100%);
            opacity: .72;
            pointer-events: none;
            position: absolute;
            right: 0;
        }

        .lms-hero__content,
        .lms-hero__side {
            position: relative;
            z-index: 1;
        }

        .lms-hero__pill {
            align-items: center;
            background: #fff;
            border: 1px solid rgba(37, 99, 235, .14);
            border-radius: 999px;
            color: #2563eb;
            display: inline-flex;
            font-size: 12px;
            font-weight: 900;
            gap: 8px;
            letter-spacing: .08em;
            margin-bottom: 14px;
            padding: 8px 12px;
            text-transform: uppercase;
        }

        .lms-hero__pill span {
            background: #22c55e;
            border-radius: 999px;
            box-shadow: 0 0 0 5px rgba(34, 197, 94, .16);
            height: 8px;
            width: 8px;
        }

        .lms-hero__title {
            color: #0f172a;
            font-size: clamp(28px, 3vw, 42px);
            font-weight: 900;
            letter-spacing: -.04em;
            line-height: 1.08;
            margin: 0 0 10px;
        }

        .lms-hero__desc {
            color: #64748b;
            font-size: 15px;
            line-height: 1.7;
            margin: 0 0 18px;
            max-width: 720px;
        }

        .lms-hero__date {
            align-items: center;
            color: #475569;
            display: inline-flex;
            font-size: 13px;
            font-weight: 800;
            gap: 8px;
        }

        .lms-hero__side {
            align-items: end;
            display: grid;
            grid-template-columns: 1fr;
            justify-items: center;
        }

        .lms-hero__side img {
            max-height: 170px;
            object-fit: contain;
            width: min(240px, 100%);
            animation: heroCharacterFloat 5s ease-in-out infinite;
            position: relative;
            z-index: 2;
        }

        .hero-motion-icons {
            inset: 0;
            pointer-events: none;
            position: absolute;
            z-index: 1;
        }

        .hero-motion-icon {
            align-items: center;
            animation: heroIconFloat 4.8s ease-in-out infinite;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, .88);
            border: 1px solid rgba(37, 99, 235, .14);
            border-radius: 15px;
            box-shadow: 0 12px 26px rgba(37, 99, 235, .14);
            color: #2563eb;
            display: flex;
            font-size: 16px;
            height: 42px;
            justify-content: center;
            position: absolute;
            width: 42px;
        }

        .hero-motion-icon:nth-child(1) { left: 4%; top: 15%; }
        .hero-motion-icon:nth-child(2) { animation-delay: -.9s; right: 2%; top: 10%; color: #7c3aed; }
        .hero-motion-icon:nth-child(3) { animation-delay: -1.8s; bottom: 12%; left: 1%; color: #0f766e; }
        .hero-motion-icon:nth-child(4) { animation-delay: -2.7s; bottom: 18%; right: 0; color: #d97706; }

        .stat-card__icon,
        .teacher-priority-card__icon,
        .icon-dot {
            transition: transform .25s cubic-bezier(.22, 1, .36, 1);
        }

        .stat-card:hover .stat-card__icon,
        .teacher-priority-card:hover .teacher-priority-card__icon,
        .panel:hover .icon-dot {
            transform: translateY(-3px) rotate(-5deg) scale(1.08);
        }

        @keyframes heroCharacterFloat {
            0%, 100% { transform: translateY(2px); }
            50% { transform: translateY(-8px); }
        }

        @keyframes heroIconFloat {
            0%, 100% { transform: translate3d(0, 0, 0) rotate(-3deg); }
            50% { transform: translate3d(0, -10px, 0) rotate(4deg); }
        }

        @media (prefers-reduced-motion: reduce) {
            .lms-hero__side img,
            .hero-motion-icon {
                animation: none !important;
            }
        }

        .lms-hero-metrics {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 18px;
        }

        .lms-hero-metric {
            background: rgba(255, 255, 255, .88);
            border: 1px solid rgba(226, 232, 240, .9);
            border-radius: 18px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, .06);
            padding: 14px;
        }

        .lms-hero-metric__value {
            color: #0f172a;
            font-size: 22px;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 5px;
        }

        .lms-hero-metric__label {
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.35;
        }

        .section-heading {
            color: #0f172a;
            font-size: 18px;
            letter-spacing: -.02em;
            margin: 0 0 14px;
            text-transform: none;
        }

        .section-heading::after {
            background: linear-gradient(90deg, rgba(37, 99, 235, .18), transparent);
        }

        .teacher-priority-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .teacher-priority-card,
        .stat-card,
        .panel,
        .teacher-ai-panel,
        .priority-submissions {
            background: rgba(255, 255, 255, .96);
            border: 1px solid rgba(226, 232, 240, .95);
            border-radius: 22px;
            box-shadow: 0 16px 42px rgba(15, 23, 42, .06);
        }

        .teacher-priority-card {
            min-height: 142px;
            padding: 18px;
        }

        .teacher-priority-card__icon,
        .stat-card__icon,
        .panel__title .icon-dot,
        .teacher-ai-suggestion__icon {
            border-radius: 16px;
            height: 46px;
            width: 46px;
        }

        .teacher-priority-card__value,
        .stat-card__value {
            font-size: 28px;
        }

        .teacher-action-strip {
            background: transparent;
            border: 0;
            box-shadow: none;
            gap: 12px;
            padding: 0;
        }

        .quick-action {
            border-radius: 18px;
            min-height: 52px;
            padding: 0 18px;
        }

        .quick-action--ai {
            background: linear-gradient(135deg, #eef2ff, #eff6ff);
            border-color: rgba(99, 102, 241, .18);
            color: #4338ca;
        }

        .teacher-command-grid {
            grid-template-columns: minmax(0, .88fr) minmax(0, 1.12fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .teacher-next-class {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 55%, #0e7490 100%);
            border-radius: 24px;
            box-shadow: 0 22px 46px rgba(29, 78, 216, .22);
            padding: 24px;
        }

        .teacher-next-class::before {
            background:
                linear-gradient(135deg, rgba(15, 23, 42, .16), transparent 56%),
                linear-gradient(115deg, transparent 0 55%, rgba(255, 255, 255, .14) 55.2% 55.7%, transparent 56%),
                linear-gradient(24deg, transparent 0 62%, rgba(125, 211, 252, .16) 62.2% 62.7%, transparent 63%),
                linear-gradient(90deg, rgba(255, 255, 255, .06) 1px, transparent 1px),
                linear-gradient(0deg, rgba(255, 255, 255, .05) 1px, transparent 1px);
            background-size: auto, 280px 180px, 240px 150px, 32px 32px, 32px 32px;
            content: '';
            inset: 0;
            opacity: .9;
            pointer-events: none;
            position: absolute;
            z-index: 0;
        }

        .teacher-next-class::after {
            background:
                linear-gradient(38deg, transparent 0 42%, rgba(255, 255, 255, .18) 42.2% 42.7%, transparent 43%),
                linear-gradient(148deg, transparent 0 50%, rgba(147, 197, 253, .22) 50.2% 50.6%, transparent 51%);
            background-position: right bottom;
            background-size: 210px 130px, 250px 150px;
            bottom: 0;
            content: '';
            height: 58%;
            opacity: .7;
            pointer-events: none;
            position: absolute;
            right: 0;
            width: 70%;
            z-index: 0;
        }

        .teacher-ai-panel {
            padding: 20px;
        }

        .teacher-ai-panel__eyebrow,
        .teacher-next-class__eyebrow {
            color: #6366f1;
        }

        .teacher-next-class__eyebrow {
            color: rgba(255, 255, 255, .72);
        }

        .teacher-ai-suggestion {
            border-color: rgba(226, 232, 240, .8);
            padding: 14px 0;
        }

        .stat-card {
            padding: 20px;
        }

        .panel__header {
            background: #fff;
            padding: 18px 20px;
        }

        .compact-card,
        .priority-submission,
        .teacher-schedule-card,
        .feed-item,
        .todo-item {
            padding: 16px 20px;
        }

        .priority-submission {
            align-items: center;
        }

        .btn-xs--danger {
            background: #fff1f4;
            border-color: #fecdd3;
            color: #e11d48;
        }

        .tbl thead th {
            background: #f8fafc;
            color: #64748b;
        }

        .tbl tbody tr.is-today td {
            background: #eff6ff;
        }

        .score-hero {
            background: linear-gradient(145deg, #0f172a 0%, #1e3a8a 58%, #1d4ed8 100%);
            border-radius: 24px;
            box-shadow: 0 22px 48px rgba(30, 58, 138, .24);
        }

        .score-hero::before {
            background:
                linear-gradient(135deg, rgba(15, 23, 42, .2), transparent 58%),
                linear-gradient(112deg, transparent 0 52%, rgba(255, 255, 255, .12) 52.2% 52.7%, transparent 53%),
                linear-gradient(24deg, transparent 0 60%, rgba(165, 180, 252, .16) 60.2% 60.7%, transparent 61%),
                linear-gradient(90deg, rgba(255, 255, 255, .055) 1px, transparent 1px),
                linear-gradient(0deg, rgba(255, 255, 255, .045) 1px, transparent 1px);
            background-size: auto, 260px 170px, 220px 140px, 30px 30px, 30px 30px;
            opacity: .95;
        }

        .score-hero::after {
            background:
                linear-gradient(42deg, transparent 0 42%, rgba(255, 255, 255, .15) 42.2% 42.7%, transparent 43%),
                linear-gradient(148deg, transparent 0 50%, rgba(191, 219, 254, .18) 50.2% 50.6%, transparent 51%);
            background-position: right bottom;
            background-size: 190px 120px, 240px 150px;
            bottom: 0;
            content: '';
            height: 56%;
            opacity: .75;
            pointer-events: none;
            position: absolute;
            right: 0;
            width: 78%;
        }

        @media (max-width: 1199.98px) {
            .teacher-priority-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .teacher-command-grid,
            .lms-hero {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .dashboard-wrap {
                padding: 14px !important;
            }

            .lms-hero {
                border-radius: 22px;
                padding: 20px;
            }

            .lms-hero__side img {
                display: none;
            }

            .hero-motion-icons {
                display: none;
            }

            .lms-hero-metrics,
            .teacher-priority-grid {
                grid-template-columns: 1fr;
            }

            .teacher-action-strip {
                display: grid;
                grid-template-columns: 1fr;
            }

            .quick-action {
                width: 100%;
            }
        }
    </style>
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
                        Theo dõi lớp sắp dạy, bài cần chấm, học sinh cần chú ý và các gợi ý AI trong một bảng điều khiển gọn
                        gàng.
                    @elseif ($role === 'admin')
                        Quản lý tổng quan người dùng, lớp học, khóa học và các hoạt động vận hành quan trọng của hệ thống.
                    @else
                        Theo dõi khóa học, bài tập, quiz và tiến độ học tập của bạn trong một không gian rõ ràng.
                    @endif
                </p>
                <div class="lms-hero__date">
                    <i class="far fa-calendar-alt"></i>
                    {{ \Carbon\Carbon::now()->translatedFormat('l, d/m/Y') }}
                </div>
            </div>
            <div class="lms-hero__side">
                <div class="hero-motion-icons" aria-hidden="true">
                    <span class="hero-motion-icon"><i class="fas fa-book-open"></i></span>
                    <span class="hero-motion-icon"><i class="fas fa-lightbulb"></i></span>
                    <span class="hero-motion-icon"><i class="fas fa-graduation-cap"></i></span>
                    <span class="hero-motion-icon"><i class="fas fa-chart-line"></i></span>
                </div>
                <img src="{{ asset('gretting-img.png') }}" alt="">
            </div>
        </section>

        @if ($role !== 'teacher')
            {{-- QUICK ACTIONS --}}
            <div class="quick-actions anim-2">
                @if ($role === 'admin')
                    <a href="{{ route('users.index') }}" class="quick-action"><i class="fas fa-users-cog"></i> Quản lý người
                        dùng</a>
                    <a href="{{ route('classes.index') }}" class="quick-action"><i class="fas fa-school"></i> Quản lý
                        lớp</a>
                    <a href="{{ route('courses.index') }}" class="quick-action"><i class="fas fa-book-open"></i> Quản lý
                        khóa
                        học</a>
                    <a href="{{ route('documents.upload') }}" class="quick-action"><i class="fas fa-robot"></i> Huấn luyện
                        AI</a>
                @else
                    <a href="{{ route('courses.index') }}" class="quick-action"><i class="fas fa-book-open"></i> Vào học</a>
                    <a href="{{ route('assignments.index') }}" class="quick-action"><i class="fas fa-paper-plane"></i> Bài
                        tập</a>
                @endif
            </div>
        @endif

        {{-- ══════════════════════════════════════
             ADMIN VIEW
        ══════════════════════════════════════ --}}
        @if ($role === 'admin')

            <div class="row g-3 mb-4 anim-3">
                <div class="col-6 col-md-3">
                    <div class="stat-card stat-card--blue">
                        <div class="stat-card__icon"><i class="fas fa-users"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Học sinh</div>
                            <div class="stat-card__value">{{ $data['total_students'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card stat-card--teal">
                        <div class="stat-card__icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Giáo viên</div>
                            <div class="stat-card__value">{{ $data['total_teachers'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card stat-card--green">
                        <div class="stat-card__icon"><i class="fas fa-layer-group"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Khóa / Lớp</div>
                            <div class="stat-card__value">
                                {{ $data['total_courses'] }}<span
                                    style="font-size:1rem;font-weight:600;color:var(--text-muted)">/{{ $data['total_classes'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card stat-card--amber">
                        <div class="stat-card__icon"><i class="fas fa-hourglass-half"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Bài chờ chấm</div>
                            <div class="stat-card__value">{{ $data['pending_grades'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 anim-4">
                <div class="col-md-8">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--blue"><i class="fas fa-user-plus"></i></span>
                                Người dùng mới
                            </h6>
                            <a href="{{ route('users.index') }}" class="btn-xs btn-xs--ghost"><i
                                    class="fas fa-arrow-right"></i> Xem tất cả</a>
                        </div>
                        <div class="table-responsive">
                            <table class="tbl">
                                <thead>
                                    <tr>
                                        <th>Họ và tên</th>
                                        <th>Email</th>
                                        <th>Vai trò</th>
                                        <th>Ngày tham gia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($data['recent_users'] as $user)
                                        @php $r = $user->role; @endphp
                                        <tr>
                                            <td>
                                                <div style="display:flex;align-items:center">
                                                    <span
                                                        class="user-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                                    <span class="fw-bold">{{ $user->name }}</span>
                                                </div>
                                            </td>
                                            <td
                                                style="color:var(--text-muted);font-family:var(--font-mono);font-size:.78rem">
                                                {{ $user->email }}</td>
                                            <td>
                                                <span
                                                    class="bdg {{ $r === 'teacher' ? 'bdg--info' : ($r === 'admin' ? 'bdg--dark' : 'bdg--primary') }}">
                                                    {{ strtoupper($r) }}
                                                </span>
                                            </td>
                                            <td
                                                style="color:var(--text-muted);font-size:.78rem;font-family:var(--font-mono)">
                                                {{ \Carbon\Carbon::parse($user->created_at)->format('d/m/Y') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4">
                                                <div class="empty-state">
                                                    <div class="empty-icon"><i class="fas fa-users"></i></div>
                                                    <p>Chưa có người dùng mới.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--green"><i class="fas fa-chart-pie"></i></span>
                                Tỷ lệ người dùng
                            </h6>
                        </div>
                        <div class="chart-wrap d-flex justify-content-center align-items-center" style="min-height:260px">
                            <div id="adminChart"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1 anim-5">
                <div class="col-12 col-xl-4">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--amber"><i class="fas fa-calendar-day"></i></span>
                                Lịch học hôm nay
                            </h6>
                            <span class="bdg bdg--warning">{{ ($data['today_schedules'] ?? collect())->count() }}
                                ca</span>
                        </div>
                        @forelse ($data['today_schedules'] ?? [] as $slot)
                            <div class="compact-card">
                                <div class="fw-bold" style="font-size:.85rem">{{ $slot->course_title }}</div>
                                <div class="text-muted" style="font-size:.76rem;margin-top:.2rem">
                                    {{ $slot->class_name }} · <span
                                        style="font-family:var(--font-mono)">{{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }}
                                        – {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fas fa-calendar-check"></i></div>
                                <p>Hôm nay chưa có lịch học.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--blue"><i class="fas fa-school"></i></span>
                                Lớp nổi bật
                            </h6>
                            <a href="{{ route('classes.index') }}" class="btn-xs btn-xs--primary">Xem lớp</a>
                        </div>
                        @forelse ($data['class_overview'] ?? [] as $class)
                            <div class="compact-card">
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <div>
                                        <div class="fw-bold" style="font-size:.85rem">{{ $class->name }}</div>
                                        <div class="text-muted" style="font-size:.76rem;margin-top:.15rem">
                                            {{ $class->teacher->name ?? 'Chưa phân công' }}</div>
                                    </div>
                                    <span class="bdg bdg--primary">{{ $class->students_count }} HS</span>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fas fa-school"></i></div>
                                <p>Chưa có lớp học.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--green"><i class="fas fa-book-open"></i></span>
                                Khóa học mới
                            </h6>
                            <a href="{{ route('courses.index') }}" class="btn-xs btn-xs--primary">Xem khóa</a>
                        </div>
                        @forelse ($data['recent_courses'] ?? [] as $course)
                            <div class="compact-card">
                                <div class="fw-bold" style="font-size:.85rem">{{ $course->title }}</div>
                                <div class="text-muted" style="font-size:.76rem;margin-top:.15rem">
                                    {{ $course->teacher->name ?? 'Chưa rõ giáo viên' }}</div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fas fa-book"></i></div>
                                <p>Chưa có khóa học.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════
             TEACHER VIEW
        ══════════════════════════════════════ --}}
        @elseif ($role === 'teacher')
            @php
                $pendingGrades = $data['pending_grades'] ?? 0;
                $todaySchedules = $data['today_schedules_count'] ?? 0;
                $attentionCount = $data['attention_students_count'] ?? 0;
                $nextSchedule = $data['next_schedule'] ?? null;
                $prioritySubmissions = $data['priority_submissions'] ?? collect();
                $aiSuggestions = $data['teacher_ai_suggestions'] ?? [];
            @endphp

            {{-- 1. TODAY'S PRIORITY ACTIONS --}}
            <div class="section-heading anim-2">Việc cần làm hôm nay</div>
            <div class="teacher-priority-grid anim-2">
                <a href="{{ route('assignments.index') }}" class="teacher-priority-card teacher-priority-card--danger">
                    <span class="teacher-priority-card__icon"><i class="fas fa-pen"></i></span>
                    <span>
                        <span class="teacher-priority-card__label">Cần chấm</span>
                        <span class="teacher-priority-card__value">{{ $pendingGrades }}</span>
                        <span class="teacher-priority-card__hint">Bài nộp đang chờ giáo viên phản hồi.</span>
                    </span>
                    <i class="fas fa-arrow-right teacher-priority-card__arrow"></i>
                </a>
                <a href="{{ route('schedules.index') }}" class="teacher-priority-card teacher-priority-card--blue">
                    <span class="teacher-priority-card__icon"><i class="fas fa-calendar-day"></i></span>
                    <span>
                        <span class="teacher-priority-card__label">Lịch hôm nay</span>
                        <span class="teacher-priority-card__value">{{ $todaySchedules }}</span>
                        <span class="teacher-priority-card__hint">Ca dạy cần chuẩn bị trong ngày.</span>
                    </span>
                    <i class="fas fa-arrow-right teacher-priority-card__arrow"></i>
                </a>
                <a href="{{ route('classes.index') }}" class="teacher-priority-card teacher-priority-card--amber">
                    <span class="teacher-priority-card__icon"><i class="fas fa-user-clock"></i></span>
                    <span>
                        <span class="teacher-priority-card__label">Cần chú ý</span>
                        <span class="teacher-priority-card__value">{{ $attentionCount }}</span>
                        <span class="teacher-priority-card__hint">Học sinh nên được theo dõi sát hơn.</span>
                    </span>
                    <i class="fas fa-arrow-right teacher-priority-card__arrow"></i>
                </a>
                <a href="{{ route('courses.index') }}" class="teacher-priority-card teacher-priority-card--green">
                    <span class="teacher-priority-card__icon"><i class="fas fa-book-open"></i></span>
                    <span>
                        <span class="teacher-priority-card__label">Khóa học</span>
                        <span class="teacher-priority-card__value">{{ $data['total_courses'] }}</span>
                        <span class="teacher-priority-card__hint">Khóa học đang phụ trách và cần cập nhật.</span>
                    </span>
                    <i class="fas fa-arrow-right teacher-priority-card__arrow"></i>
                </a>
            </div>

            {{-- 2. QUICK ACTIONS --}}
            <div class="teacher-action-strip anim-2">
                <a href="{{ route('assignments.index') }}" class="quick-action"><i class="fas fa-plus-circle"></i> Tạo
                    bài tập</a>
                <a href="{{ route('quizzes.ai_generate') }}" class="quick-action quick-action--ai"><i
                        class="fas fa-magic"></i> AI tạo quiz</a>
                <a href="{{ route('courses.index') }}" class="quick-action quick-action--ai"><i
                        class="fas fa-robot"></i> AI soạn nội dung</a>
                <a href="{{ route('classes.index') }}" class="quick-action"><i class="fas fa-users"></i> Xem lớp</a>
            </div>

            <div class="teacher-command-grid anim-3">
                <div class="teacher-next-class">
                    <div class="teacher-next-class__eyebrow">Lớp sắp dạy</div>
                    @if ($nextSchedule)
                        @php
                            $nextStart = \Carbon\Carbon::parse(
                                $nextSchedule->schedule_date . ' ' . $nextSchedule->start_time,
                            );
                            $nextEnd = \Carbon\Carbon::parse(
                                $nextSchedule->schedule_date . ' ' . $nextSchedule->end_time,
                            );
                        @endphp
                        <h3 class="teacher-next-class__title">{{ $nextSchedule->course_title }}</h3>
                        <div class="teacher-next-class__meta">
                            <span><i class="fas fa-school"></i>{{ $nextSchedule->class_name }}</span>
                            <span><i class="far fa-calendar"></i>{{ $nextStart->format('d/m/Y') }}</span>
                            <span><i class="far fa-clock"></i>{{ $nextStart->format('H:i') }} -
                                {{ $nextEnd->format('H:i') }}</span>
                            <span><i class="fas fa-map-marker-alt"></i>{{ $nextSchedule->room ?? 'Online' }}</span>
                        </div>
                        <a href="{{ route('schedules.index') }}" class="btn-xs btn-xs--primary">
                            <i class="fas fa-calendar-alt"></i> Mở lịch dạy
                        </a>
                    @else
                        <h3 class="teacher-next-class__title">Chưa có ca dạy sắp tới</h3>
                        <div class="teacher-next-class__meta">
                            <span><i class="fas fa-check-circle"></i>Không có lịch cần chuẩn bị ngay</span>
                        </div>
                        <a href="{{ route('courses.index') }}" class="btn-xs btn-xs--primary">
                            <i class="fas fa-book-open"></i> Chuẩn bị khóa học
                        </a>
                    @endif
                </div>

                <div class="teacher-ai-panel">
                    <div class="teacher-ai-panel__eyebrow">AI gợi ý việc cần làm</div>
                    <h3 class="teacher-ai-panel__title">Ưu tiên dựa trên dữ liệu hiện tại</h3>
                    @forelse ($aiSuggestions as $suggestion)
                        <div class="teacher-ai-suggestion teacher-ai-suggestion--{{ $suggestion['type'] ?? 'primary' }}">
                            <span class="teacher-ai-suggestion__icon">
                                <i class="{{ $suggestion['icon'] ?? 'fas fa-lightbulb' }}"></i>
                            </span>
                            <div>
                                <div class="teacher-ai-suggestion__title">{{ $suggestion['title'] }}</div>
                                <div class="teacher-ai-suggestion__body">{{ $suggestion['body'] }}</div>
                                <a href="{{ $suggestion['action_url'] }}" class="btn-xs btn-xs--ghost">
                                    {{ $suggestion['action_label'] }} <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state" style="padding:1.25rem 1rem">
                            <div class="empty-icon"><i class="fas fa-lightbulb"></i></div>
                            <p>Chưa có gợi ý mới từ dữ liệu hiện tại.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- 3. TEACHING OVERVIEW STATISTICS --}}
            <div class="section-heading anim-3">Tổng quan giảng dạy</div>
            <div class="row g-3 mb-4 anim-3">
                <div class="col-12 col-md-4">
                    <div class="stat-card stat-card--compact stat-card--red stat-card--stripe">
                        <div class="stat-card__icon"><i class="fas fa-hourglass-half"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Bài chờ chấm</div>
                            <div class="stat-card__value" style="color:var(--danger)">{{ $data['pending_grades'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card stat-card--compact stat-card--blue stat-card--stripe">
                        <div class="stat-card__icon"><i class="fas fa-book-open"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Khóa học phụ trách</div>
                            <div class="stat-card__value" style="color:var(--brand)">{{ $data['total_courses'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card stat-card--compact stat-card--green stat-card--stripe">
                        <div class="stat-card__icon"><i class="fas fa-user-graduate"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Tổng học sinh</div>
                            <div class="stat-card__value" style="color:var(--success)">{{ $data['total_students'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. MANAGED CLASSES + STUDENTS NEEDING ATTENTION --}}
            <div class="section-heading anim-4">Lớp học &amp; học sinh cần theo dõi</div>
            <div class="row g-3 mb-4 anim-4">
                <div class="col-12 col-xl-7">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--blue"><i class="fas fa-school"></i></span>
                                Lớp phụ trách
                            </h6>
                            <a href="{{ route('classes.index') }}" class="btn-xs btn-xs--primary">Quản lý lớp</a>
                        </div>
                        <div class="row g-0">
                            @forelse ($data['teacher_classes'] ?? [] as $class)
                                <div class="col-12 col-lg-6">
                                    <div class="compact-card h-100">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <div>
                                                <div class="fw-bold" style="font-size:.85rem">{{ $class->name }}</div>
                                                <div class="text-muted" style="font-size:.76rem;margin-top:.15rem">
                                                    <span style="font-family:var(--font-mono)">{{ $class->code }}</span>
                                                    · {{ $class->courses->count() }} khóa học
                                                </div>
                                            </div>
                                            <span class="bdg bdg--primary">{{ $class->students_count }} HS</span>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            <a href="{{ route('classes.progress', $class->id) }}"
                                                class="btn-xs btn-xs--primary">
                                                <i class="fas fa-chart-line"></i> Tiến độ
                                            </a>
                                            <a href="{{ route('classes.students.index', $class->id) }}"
                                                class="btn-xs btn-xs--ghost">
                                                <i class="fas fa-user-graduate"></i> Học sinh
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fas fa-school"></i></div>
                                        <p>Thầy / Cô chưa được phân công lớp.</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-5">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--amber"><i class="fas fa-user-clock"></i></span>
                                Học sinh cần chú ý
                            </h6>
                            <span class="bdg bdg--warning">Theo dõi</span>
                        </div>
                        @forelse ($data['attention_students'] ?? [] as $student)
                            <div class="compact-card">
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <div style="display:flex;align-items:center;gap:.6rem">
                                        <span class="user-avatar"
                                            style="flex-shrink:0">{{ mb_strtoupper(mb_substr($student->name, 0, 1)) }}</span>
                                        <div>
                                            <div class="fw-bold" style="font-size:.85rem">{{ $student->name }}</div>
                                            <div class="text-muted" style="font-size:.75rem">{{ $student->class_name }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end" style="flex-shrink:0">
                                        <div
                                            class="bdg {{ $student->avg_grade !== null && $student->avg_grade < 5 ? 'bdg--danger' : 'bdg--muted' }} mb-1">
                                            TB {{ $student->avg_grade !== null ? round($student->avg_grade, 1) : 'N/A' }}
                                        </div>
                                        <a href="{{ route('classes.students.show', ['classId' => $student->class_id, 'studentId' => $student->id]) }}"
                                            class="btn-xs btn-xs--primary">
                                            Hồ sơ
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <div class="empty-icon" style="color:var(--success)"><i class="fas fa-check-circle"></i>
                                </div>
                                <p>Chưa có học sinh cần ưu tiên theo dõi.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- 5. ASSIGNMENTS REQUIRING GRADING --}}
            <div class="section-heading anim-5">Bài cần chấm</div>
            <div class="row g-3 mb-4 anim-5">
                <div class="col-md-8">
                    <div class="priority-submissions">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--red"><i class="fas fa-inbox"></i></span>
                                Bài cần chấm ưu tiên
                            </h6>
                            <span class="bdg bdg--danger">{{ $prioritySubmissions->count() }} bài</span>
                        </div>
                        <div class="teacher-list-scroll">
                            @forelse ($prioritySubmissions as $sub)
                                @php
                                    $dueDate = $sub->due_date ? \Carbon\Carbon::parse($sub->due_date) : null;
                                    $submittedAt = $sub->submitted_at
                                        ? \Carbon\Carbon::parse($sub->submitted_at)
                                        : \Carbon\Carbon::parse($sub->created_at);
                                    $isOverdue = $dueDate && $dueDate->isPast();
                                @endphp
                                <div class="priority-submission">
                                    <div class="priority-submission__main">
                                        <span
                                            class="feed-item__avatar">{{ mb_strtoupper(mb_substr($sub->student_name, 0, 1)) }}</span>
                                        <div class="min-w-0">
                                            <div class="priority-submission__title">{{ $sub->assignment_title ?? 'N/A' }}
                                            </div>
                                            <div class="priority-submission__meta">
                                                {{ $sub->student_name }} · {{ $sub->course_title ?? 'N/A' }}
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 mt-2">
                                                <span class="bdg {{ $isOverdue ? 'bdg--danger' : 'bdg--warning' }}">
                                                    {{ $isOverdue ? 'Quá hạn' : 'Đến hạn' }}
                                                    {{ $dueDate ? $dueDate->format('d/m H:i') : 'chưa rõ' }}
                                                </span>
                                                <span class="bdg bdg--muted">
                                                    Nộp {{ $submittedAt->diffForHumans() }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="{{ route('assignments.submissions.review', $sub->id) }}"
                                        class="btn-xs btn-xs--danger">
                                        <i class="fas fa-pen"></i> Chấm ngay
                                    </a>
                                </div>
                            @empty
                                <div class="empty-state">
                                    <div class="empty-icon" style="color:var(--success)"><i
                                            class="fas fa-check-circle"></i></div>
                                    <p>Tuyệt vời! Thầy / Cô đã chấm hết bài.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--blue"><i class="fas fa-chart-pie"></i></span>
                                Tiến độ chấm bài
                            </h6>
                        </div>
                        <div class="chart-wrap d-flex justify-content-center align-items-center" style="min-height:260px">
                            <div id="teacherChart"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 6. WEEKLY TEACHING SCHEDULE --}}
            <div class="section-heading">Lịch dạy tuần này</div>
            <div class="row g-3">
                <div class="col-12">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--blue"><i class="fas fa-calendar-alt"></i></span>
                                Lịch dạy tuần này
                            </h6>
                            <span class="bdg bdg--primary">Tuần này · {{ $data['dashboard_week_label'] ?? '' }}</span>
                        </div>
                        <div class="table-responsive teacher-schedule-table">
                            @php
                                $days = [
                                    'Monday' => 'Thứ Hai',
                                    'Tuesday' => 'Thứ Ba',
                                    'Wednesday' => 'Thứ Tư',
                                    'Thursday' => 'Thứ Năm',
                                    'Friday' => 'Thứ Sáu',
                                    'Saturday' => 'Thứ Bảy',
                                    'Sunday' => 'Chủ Nhật',
                                ];
                            @endphp

                            <table class="tbl">
                                <thead>
                                    <tr>
                                        <th>Ngày</th>
                                        <th>Giờ dạy</th>
                                        <th>Môn / Lớp</th>
                                        <th>Phòng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($data['week_schedule'] ?? [] as $slot)
                                        @php
                                            $d = \Carbon\Carbon::parse($slot->schedule_date);
                                            $today = $d->isToday();
                                            $past = $d->isPast() && !$today;
                                            $isExamSchedule = ($slot->note ?? null) === 'Thi kết thúc môn';
                                        @endphp

                                        <tr class="{{ $today ? 'is-today' : ($past ? 'is-past' : '') }}">
                                            <td>
                                                <div class="fw-bold" style="font-size:.85rem">
                                                    {{ $d->format('d/m/Y') }}
                                                </div>
                                                <div style="font-size:.75rem;color:var(--text-muted)">
                                                    {{ $days[$d->format('l')] ?? $d->format('l') }}
                                                </div>
                                            </td>

                                            <td>
                                                <span
                                                    style="font-size:.84rem;font-weight:700;color:{{ $isExamSchedule ? 'var(--danger)' : 'var(--brand)' }};font-family:var(--font-mono)">
                                                    {{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }}
                                                    –
                                                    {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}
                                                </span>
                                            </td>

                                            <td>
                                                <div class="fw-bold"
                                                    style="font-size:.85rem;color:{{ $isExamSchedule ? 'var(--danger)' : 'inherit' }}">
                                                    {{ $slot->course_title }}
                                                </div>
                                                @if ($isExamSchedule)
                                                    <span class="bdg bdg--danger mb-1">Thi kết thúc môn</span>
                                                @endif
                                                <div style="font-size:.75rem;color:var(--text-muted)">
                                                    Lớp: {{ $slot->class_name }}
                                                </div>
                                            </td>

                                            <td>
                                                @if ($today)
                                                    <span class="bdg bdg--success mb-1">Hôm nay</span><br>
                                                @elseif ($past)
                                                    <span class="bdg bdg--muted mb-1">Đã dạy</span><br>
                                                @endif

                                                <span class="bdg {{ $isExamSchedule ? 'bdg--danger' : 'bdg--muted' }}">
                                                    {{ $slot->room ?? 'Online' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4">
                                                <div class="empty-state">
                                                    <div class="empty-icon">
                                                        <i class="fas fa-calendar-times"></i>
                                                    </div>
                                                    <p>
                                                        <strong>Chưa có lịch dạy.</strong><br>
                                                        Lịch giảng dạy sẽ hiển thị tại đây.
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="teacher-schedule-list">
                            @forelse ($data['week_schedule'] ?? [] as $slot)
                                @php
                                    $d = \Carbon\Carbon::parse($slot->schedule_date);
                                    $today = $d->isToday();
                                    $past = $d->isPast() && !$today;
                                    $isExamSchedule = ($slot->note ?? null) === 'Thi kết thúc môn';
                                @endphp
                                <div class="teacher-schedule-card">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <div>
                                            <div class="fw-bold" style="font-size:.9rem">{{ $slot->course_title }}</div>
                                            <div class="text-muted" style="font-size:.76rem;margin-top:.15rem">
                                                {{ $d->format('d/m/Y') }} · {{ $d->translatedFormat('l') }}
                                            </div>
                                        </div>
                                        @if ($today)
                                            <span class="bdg bdg--success">Hôm nay</span>
                                        @elseif ($past)
                                            <span class="bdg bdg--muted">Đã dạy</span>
                                        @endif
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <span class="bdg {{ $isExamSchedule ? 'bdg--danger' : 'bdg--primary' }}">
                                            <i class="far fa-clock"></i>
                                            {{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }}
                                            –
                                            {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}
                                        </span>
                                        <span class="bdg bdg--muted">{{ $slot->class_name }}</span>
                                        <span class="bdg {{ $isExamSchedule ? 'bdg--danger' : 'bdg--muted' }}">
                                            {{ $slot->room ?? 'Online' }}
                                        </span>
                                    </div>
                                    @if ($isExamSchedule)
                                        <div class="mt-2">
                                            <span class="bdg bdg--danger">Thi kết thúc môn</span>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fas fa-calendar-times"></i>
                                    </div>
                                    <p>
                                        <strong>Chưa có lịch dạy.</strong><br>
                                        Lịch giảng dạy sẽ hiển thị tại đây.
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════
             STUDENT VIEW
        ══════════════════════════════════════ --}}
        @else
            <div class="row g-3 mb-4 anim-3">
                <div class="col-12 col-md-4">
                    <div class="stat-card stat-card--blue">
                        <div class="stat-card__icon"><i class="fas fa-book-open"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Khóa học đang học</div>
                            <div class="stat-card__value">{{ $data['total_courses'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card stat-card--amber">
                        <div class="stat-card__icon"><i class="fas fa-file-signature"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Bài tập còn thiếu</div>
                            <div class="stat-card__value">{{ $data['missing_assignments_count'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card stat-card--violet">
                        <div class="stat-card__icon"><i class="fas fa-clipboard-list"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Quiz chưa làm</div>
                            <div class="stat-card__value">{{ $data['pending_quizzes_count'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4 anim-4">
                <div class="col-md-4">
                    <div class="score-hero">
                        <div class="score-hero__label">Điểm Quiz trung bình</div>
                        <div class="score-hero__ring">
                            <div class="score-hero__value" style="position:relative;z-index:1">
                                {{ $data['average_score'] }}</div>
                        </div>
                        <div class="score-hero__sub">/ 10 điểm</div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--amber"><i class="fas fa-clock"></i></span>
                                Deadline & Bài kiểm tra
                            </h6>
                        </div>
                        <div style="max-height:300px;overflow-y:auto">
                            @php
                                $deadlines = $data['upcoming_deadlines'] ?? [];
                                $quizzes = $data['pending_quizzes'] ?? [];
                            @endphp

                            @foreach ($deadlines as $dl)
                                <div class="todo-item">
                                    <div>
                                        <span class="bdg bdg--warning mb-1">Bài tập</span>
                                        <div class="todo-item__label">{{ $dl->title }}</div>
                                        <div class="todo-item__sub"><i
                                                class="fas fa-book me-1"></i>{{ $dl->course_title ?? 'N/A' }}</div>
                                    </div>
                                    <div style="text-align:right;flex-shrink:0">
                                        <div class="todo-item__deadline">
                                            <i class="fas fa-hourglass-half me-1"></i>
                                            {{ \Carbon\Carbon::parse($dl->due_date)->format('H:i - d/m/Y') }}
                                        </div>
                                        <a href="{{ route('courses.show', $dl->course_id ?? 0) }}"
                                            class="btn-xs btn-xs--warning">Nộp bài</a>
                                    </div>
                                </div>
                            @endforeach

                            @foreach ($quizzes as $quiz)
                                <div class="todo-item todo-item--quiz">
                                    <div>
                                        <span class="bdg bdg--primary mb-1">Kiểm tra</span>
                                        <div class="todo-item__label" style="color:var(--brand)">{{ $quiz->title }}
                                        </div>
                                        <div class="todo-item__sub"><i
                                                class="fas fa-book me-1"></i>{{ $quiz->course_title ?? 'N/A' }}</div>
                                    </div>
                                    <div style="text-align:right;flex-shrink:0">
                                        <div class="todo-item__time-limit">
                                            <i class="fas fa-stopwatch me-1"></i>{{ $quiz->time_limit }} phút
                                        </div>
                                        <a href="{{ route('courses.show', $quiz->course_id ?? 0) }}"
                                            class="btn-xs btn-xs--primary">Làm ngay</a>
                                    </div>
                                </div>
                            @endforeach

                            @if (count($deadlines) === 0 && count($quizzes) === 0)
                                <div class="empty-state">
                                    <div class="empty-icon" style="color:var(--success)"><i
                                            class="fas fa-glass-cheers"></i></div>
                                    <p>Tuyệt vời! Bạn đã hoàn thành hết các nhiệm vụ.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4 anim-5">
                <div class="col-12">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--blue"><i class="fas fa-chart-line"></i></span>
                                Tiến độ khóa học của tôi
                            </h6>
                            <a href="{{ route('courses.index') }}" class="btn-xs btn-xs--primary">Vào học</a>
                        </div>
                        <div class="row g-0">
                            @forelse ($data['course_progress'] ?? [] as $course)
                                <div class="col-12 col-lg-6">
                                    <div class="compact-card">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <div>
                                                <div class="fw-bold" style="font-size:.85rem">{{ $course->title }}</div>
                                                <div class="text-muted" style="font-size:.75rem;margin-top:.15rem">
                                                    {{ $course->lesson_completed }}/{{ $course->lesson_total }} bài học
                                                    hoàn thành
                                                </div>
                                            </div>
                                            <span class="bdg bdg--primary">{{ $course->progress }}%</span>
                                        </div>
                                        <div class="progress-line mb-3">
                                            <span style="width: {{ $course->progress }}%"></span>
                                        </div>
                                        <a href="{{ route('courses.show', $course->id) }}"
                                            class="btn-xs btn-xs--primary">
                                            Tiếp tục học
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fas fa-book-open"></i></div>
                                        <p>Bạn chưa được gán khóa học.</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--green"><i class="fas fa-calendar-check"></i></span>
                                Lịch học tuần này
                            </h6>
                            <span class="bdg bdg--success">Tuần này · {{ $data['dashboard_week_label'] ?? '' }}</span>
                        </div>
                        <div class="table-responsive">
                            <table class="tbl">
                                <thead>
                                    <tr>
                                        <th>Ngày</th>
                                        <th>Giờ học</th>
                                        <th>Môn / Lớp</th>
                                        <th>Phòng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($data['week_schedule'] ?? [] as $slot)
                                        @php
                                            $d = \Carbon\Carbon::parse($slot->schedule_date);
                                            $today = $d->isToday();
                                            $past = $d->isPast() && !$today;
                                            $isExamSchedule = ($slot->note ?? null) === 'Thi kết thúc môn';
                                        @endphp
                                        <tr class="{{ $today ? 'is-today' : ($past ? 'is-past' : '') }}">
                                            <td>
                                                <div class="fw-bold" style="font-size:.85rem">{{ $d->format('d/m/Y') }}
                                                </div>
                                                <div style="font-size:.75rem;color:var(--text-muted)">
                                                    {{ $d->translatedFormat('l') }}</div>
                                            </td>
                                            <td>
                                                <span
                                                    style="font-size:.84rem;font-weight:700;color:{{ $isExamSchedule ? 'var(--danger)' : 'var(--brand)' }};font-family:var(--font-mono)">
                                                    {{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }} –
                                                    {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-bold"
                                                    style="font-size:.85rem;color:{{ $isExamSchedule ? 'var(--danger)' : 'inherit' }}">
                                                    {{ $slot->course_title }}
                                                </div>
                                                @if ($isExamSchedule)
                                                    <span class="bdg bdg--danger mb-1">Thi kết thúc môn</span>
                                                @endif
                                                <div style="font-size:.75rem;color:var(--text-muted)">Lớp:
                                                    {{ $slot->class_name }}</div>
                                            </td>
                                            <td>
                                                @if ($today)
                                                    <span class="bdg bdg--success mb-1">Hôm nay</span><br>
                                                @elseif ($past)
                                                    <span class="bdg bdg--muted mb-1">Đã học</span><br>
                                                @endif
                                                <span
                                                    class="bdg {{ $isExamSchedule ? 'bdg--danger' : 'bdg--muted' }}">{{ $slot->room ?? 'Online' }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4">
                                                <div class="empty-state">
                                                    <div class="empty-icon"><i class="fas fa-calendar-times"></i></div>
                                                    <p><strong>Chưa có lịch học.</strong><br>Lịch học sẽ hiển thị tại đây.
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--violet"><i class="fas fa-chart-bar"></i></span>
                                Điểm các bài kiểm tra gần đây
                            </h6>
                        </div>
                        <div class="chart-wrap">
                            @if (!empty($data['chart_quiz_data']))
                                <div id="studentChart"></div>
                            @else
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fas fa-chart-bar"></i></div>
                                    <p>Bạn chưa làm bài kiểm tra nào.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        @endif

    </div>

    @push('scripts')
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const base = {
                    chart: {
                        fontFamily: "'Plus Jakarta Sans', sans-serif",
                        toolbar: {
                            show: false
                        }
                    },
                    legend: {
                        position: 'bottom',
                        fontSize: '12px',
                        fontWeight: 600
                    },
                    dataLabels: {
                        enabled: true,
                        style: {
                            fontSize: '11px',
                            fontWeight: '700'
                        }
                    },
                    tooltip: {
                        theme: 'light'
                    },
                };

                @if (auth()->user()->role === 'admin')
                    new ApexCharts(document.querySelector("#adminChart"), {
                        ...base,
                        series: @json($data['chart_role_data']),
                        labels: @json($data['chart_role_labels']),
                        chart: {
                            ...base.chart,
                            type: 'donut',
                            height: 280
                        },
                        colors: ['#4f7fff', '#06b6d4', '#6b7280'],
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '68%',
                                    labels: {
                                        show: true,
                                        total: {
                                            show: true,
                                            label: 'Tổng',
                                            fontSize: '12px',
                                            fontWeight: '800'
                                        }
                                    }
                                }
                            }
                        },
                        stroke: {
                            width: 0
                        },
                    }).render();
                @elseif (auth()->user()->role === 'teacher')
                    new ApexCharts(document.querySelector("#teacherChart"), {
                        ...base,
                        series: @json($data['chart_submission_data']),
                        labels: @json($data['chart_submission_labels']),
                        chart: {
                            ...base.chart,
                            type: 'donut',
                            height: 280
                        },
                        colors: ['#10b981', '#f43f5e'],
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '68%'
                                }
                            }
                        },
                        stroke: {
                            width: 0
                        },
                    }).render();
                @else
                    @if (count($data['chart_quiz_data']) > 0)
                        new ApexCharts(document.querySelector("#studentChart"), {
                            ...base,
                            series: [{
                                name: 'Điểm số',
                                data: @json($data['chart_quiz_data'])
                            }],
                            chart: {
                                ...base.chart,
                                type: 'bar',
                                height: 300
                            },
                            xaxis: {
                                categories: @json($data['chart_quiz_labels']),
                                labels: {
                                    style: {
                                        fontSize: '12px',
                                        fontWeight: '600'
                                    }
                                }
                            },
                            yaxis: {
                                max: 10,
                                tickAmount: 5,
                                labels: {
                                    style: {
                                        fontSize: '12px'
                                    }
                                }
                            },
                            colors: ['#8b5cf6'],
                            fill: {
                                type: 'gradient',
                                gradient: {
                                    shade: 'light',
                                    type: 'vertical',
                                    shadeIntensity: .2,
                                    gradientToColors: ['#4f7fff'],
                                    opacityFrom: 1,
                                    opacityTo: .85
                                }
                            },
                            plotOptions: {
                                bar: {
                                    borderRadius: 8,
                                    columnWidth: '42%'
                                }
                            },
                            grid: {
                                borderColor: '#f0f0f0',
                                strokeDashArray: 4
                            },
                        }).render();
                    @endif
                @endif
            });
        </script>
    @endpush
@endsection
