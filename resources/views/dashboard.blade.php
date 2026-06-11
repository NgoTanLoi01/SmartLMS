@extends('layouts.app')

@section('title', 'Bảng điều khiển')

@push('styles')
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        /* =========================================
                               CORE VARIABLES
                            ========================================= */
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

        /* =========================================
                               GREETING BANNER
            ========================================= */
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
                               STAT CARDS
            ========================================= */
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

        /* =========================================
                               PANEL / CARD
            ========================================= */
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

        /* =========================================
                               TABLE
                            ========================================= */
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

        /* =========================================
                               BADGE
                            ========================================= */
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

        /* =========================================
                               FEED ITEMS
                            ========================================= */
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

        /* =========================================
                               TODO ITEMS
                            ========================================= */
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

        /* =========================================
                               SCORE HERO
                            ========================================= */
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

        /* =========================================
                               BUTTONS
                            ========================================= */
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

        /* =========================================
                               COMPACT CARD
                            ========================================= */
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

        /* =========================================
                               PROGRESS BAR
                            ========================================= */
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

        /* =========================================
                               EMPTY STATE
                            ========================================= */
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

        /* =========================================
                               SECTION HEADING
                            ========================================= */
        .section-heading {
            font-size: .7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--text-muted);
            margin: 0 0 .75rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .section-heading::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* =========================================
                               CHART
                            ========================================= */
        .chart-wrap {
            padding: 1.25rem;
        }

        /* =========================================
                               USER TABLE AVATAR
                            ========================================= */
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

        /* =========================================
                               ICON DOT COLORS
                            ========================================= */
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

        /* =========================================
                               RESPONSIVE
                            ========================================= */
        @media (max-width: 767.98px) {
            .dashboard-wrap {
                padding: .75rem !important;
            }

            .greeting-banner {
                min-height: 150px;
                border-radius: var(--radius-lg);
                margin-bottom: 1rem !important;
            }

            .greeting-banner__content {
                padding: 1.25rem 1.35rem;
            }

            .greeting-title {
                font-size: 1.4rem;
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

            .score-hero {
                padding: 1.5rem;
                min-height: 200px;
            }
        }

        @media (max-width: 576px) {
            .greeting-title {
                font-size: 1.2rem;
            }
        }

        /* =========================================
                               ANIMATIONS
                            ========================================= */
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
    </style>
@endpush

@section('content')
    <div class="dashboard-wrap container-fluid py-4">

        {{-- GREETING BANNER --}}
        <div class="greeting-banner mb-4 anim-1">
            <div class="greeting-banner__bg"></div>
            <div class="greeting-banner__noise"></div>
            <div class="greeting-banner__grid"></div>
            <div class="greeting-banner__orbs">
                <div class="orb orb-1"></div>
                <div class="orb orb-2"></div>
                <div class="orb orb-3"></div>
            </div>
            <img src="{{ asset('grettings-pattern.png') }}" alt="" class="greeting-banner__pattern"
                style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.05;mix-blend-mode:overlay;pointer-events:none;">
            <div class="row w-100 align-items-center g-0">
                <div class="col-sm-7">
                    <div class="greeting-banner__content">
                        <div class="greeting-pill">
                            <span class="dot"></span>
                            Đang hoạt động
                        </div>
                        <h3 class="greeting-title">Xin chào, {{ auth()->user()->name }}! 👋</h3>
                        <p class="greeting-date">
                            <i class="far fa-calendar-alt"></i>
                            {{ \Carbon\Carbon::now()->translatedFormat('l, d/m/Y') }}
                        </p>
                    </div>
                </div>
                <div class="col-sm-5 d-none d-sm-flex greeting-banner__img-wrap pe-3">
                    <img src="{{ asset('gretting-img.png') }}" class="greeting-banner__img" alt="Greeting">
                </div>
            </div>
        </div>

        @php $role = auth()->user()->role; @endphp

        {{-- QUICK ACTIONS --}}
        <div class="quick-actions anim-2">
            @if ($role === 'admin')
                <a href="{{ route('users.index') }}" class="quick-action"><i class="fas fa-users-cog"></i> Quản lý người
                    dùng</a>
                <a href="{{ route('classes.index') }}" class="quick-action"><i class="fas fa-school"></i> Quản lý lớp</a>
                <a href="{{ route('courses.index') }}" class="quick-action"><i class="fas fa-book-open"></i> Quản lý khóa
                    học</a>
                <a href="{{ route('documents.upload') }}" class="quick-action"><i class="fas fa-robot"></i> Huấn luyện
                    AI</a>
            @elseif ($role === 'teacher')
                <a href="{{ route('classes.index') }}" class="quick-action"><i class="fas fa-school"></i> Lớp của tôi</a>
                <a href="{{ route('courses.index') }}" class="quick-action"><i class="fas fa-book-open"></i> Khóa học của
                    tôi</a>
                <a href="{{ route('assignments.index') }}" class="quick-action"><i class="fas fa-clipboard-check"></i> Chấm
                    bài</a>
                <a href="{{ route('schedules.index') }}" class="quick-action"><i class="fas fa-calendar-alt"></i> Lịch
                    dạy</a>
            @else
                <a href="{{ route('courses.index') }}" class="quick-action"><i class="fas fa-book-open"></i> Vào học</a>
                <a href="{{ route('assignments.index') }}" class="quick-action"><i class="fas fa-paper-plane"></i> Bài
                    tập</a>
            @endif
        </div>

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
            <div class="row g-3 mb-4 anim-3">
                <div class="col-md-4">
                    <div class="stat-card stat-card--red stat-card--stripe">
                        <div class="stat-card__icon"><i class="fas fa-hourglass-half"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Bài chờ chấm</div>
                            <div class="stat-card__value" style="color:var(--danger)">{{ $data['pending_grades'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card stat-card--blue stat-card--stripe">
                        <div class="stat-card__icon"><i class="fas fa-book-open"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Khóa học phụ trách</div>
                            <div class="stat-card__value" style="color:var(--brand)">{{ $data['total_courses'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card stat-card--green stat-card--stripe">
                        <div class="stat-card__icon"><i class="fas fa-user-graduate"></i></div>
                        <div class="stat-card__body">
                            <div class="stat-card__label">Tổng học sinh</div>
                            <div class="stat-card__value" style="color:var(--success)">{{ $data['total_students'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
                                <span class="icon-dot idot--red"><i class="fas fa-user-clock"></i></span>
                                Học sinh cần chú ý
                            </h6>
                            <span class="bdg bdg--danger">Ưu tiên</span>
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

            <div class="row g-3 mb-4 anim-5">
                <div class="col-md-8">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--red"><i class="fas fa-inbox"></i></span>
                                Bài vừa nộp — chờ chấm
                            </h6>
                        </div>
                        <div style="max-height:340px;overflow-y:auto;">
                            @forelse ($data['recent_submissions'] as $sub)
                                <div class="feed-item">
                                    <div style="display:flex;gap:.85rem;align-items:flex-start;flex:1;min-width:0">
                                        <span
                                            class="feed-item__avatar">{{ mb_strtoupper(mb_substr($sub->student_name, 0, 1)) }}</span>
                                        <div style="min-width:0">
                                            <div class="feed-item__name">{{ $sub->student_name }}</div>
                                            <div class="feed-item__meta">{{ $sub->assignment_title ?? 'N/A' }}</div>
                                            <div class="mt-1">
                                                <span class="bdg bdg--primary"><i class="fas fa-book"></i>
                                                    {{ $sub->course_title ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="text-align:right;flex-shrink:0">
                                        <div class="feed-item__time">
                                            {{ \Carbon\Carbon::parse($sub->created_at)->diffForHumans() }}</div>
                                        <a href="{{ route('courses.show', $sub->course_id ?? 0) }}"
                                            class="btn-xs btn-xs--danger">
                                            <i class="fas fa-pen"></i> Chấm ngay
                                        </a>
                                    </div>
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

            <div class="row g-3">
                <div class="col-12">
                    <div class="panel">
                        <div class="panel__header">
                            <h6 class="panel__title">
                                <span class="icon-dot idot--blue"><i class="fas fa-calendar-alt"></i></span>
                                Lịch dạy tuần này
                            </h6>
                            <span class="bdg bdg--primary">Tuần này</span>
                        </div>
                        <div class="table-responsive">
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
                            <span class="bdg bdg--success">Tuần này</span>
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
