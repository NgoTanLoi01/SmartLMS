@extends('layouts.app')

@section('title', 'Kiểm tra lưu trữ')

@section('content')
    <style>
        .storage-page {
            max-width: 1100px;
            margin: 0 auto;
        }

        .storage-header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .storage-title {
            margin: 0 0 4px;
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
        }

        .storage-subtitle {
            margin: 0;
            color: #64748b;
            font-size: 13.5px;
        }

        .storage-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .storage-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
        }

        .storage-label {
            color: #64748b;
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .storage-value {
            color: #0f172a;
            font-size: 18px;
            font-weight: 800;
            word-break: break-word;
        }

        .storage-muted {
            color: #64748b;
            font-size: 13px;
            margin-top: 6px;
            word-break: break-word;
        }

        .storage-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 13px;
            font-weight: 700;
        }

        .storage-badge.ok {
            color: #047857;
            background: #ecfdf5;
        }

        .storage-badge.warn {
            color: #b45309;
            background: #fffbeb;
        }

        .storage-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .storage-btn {
            border: 0;
            border-radius: 10px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 700;
            background: #2563eb;
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .storage-btn.secondary {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
        }

        .storage-result {
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 18px;
            border: 1px solid;
            background: #fff;
        }

        .storage-result.ok {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }

        .storage-result.fail {
            border-color: #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }

        @media (max-width: 767.98px) {
            .storage-grid {
                grid-template-columns: 1fr;
            }

            .storage-btn {
                justify-content: center;
                width: 100%;
            }
        }
    </style>

    <div class="storage-page">
        <div class="storage-header">
            <div>
                <h1 class="storage-title">Kiểm tra lưu trữ</h1>
                <p class="storage-subtitle">Theo dõi cấu hình Cloudflare R2 và kiểm tra upload file bài nộp.</p>
            </div>

            <div class="storage-actions">
                <form method="POST" action="{{ route('system.storage.test') }}">
                    @csrf
                    <input type="hidden" name="disk" value="r2">
                    <button type="submit" class="storage-btn">
                        <i class="fas fa-cloud-upload-alt"></i> Test R2
                    </button>
                </form>
                <form method="POST" action="{{ route('system.storage.test') }}">
                    @csrf
                    <input type="hidden" name="disk" value="public">
                    <button type="submit" class="storage-btn secondary">
                        <i class="fas fa-folder-open"></i> Test local
                    </button>
                </form>
            </div>
        </div>

        @if ($lastResult)
            <div class="storage-result {{ $lastResult['ok'] ? 'ok' : 'fail' }}">
                <div class="fw-bold mb-1">
                    {{ strtoupper($lastResult['disk']) }} · {{ $lastResult['checked_at'] }}
                </div>
                <div>{{ $lastResult['message'] }}</div>
                <div class="small mt-1">File test: {{ $lastResult['path'] }}</div>
            </div>
        @endif

        <div class="storage-grid">
            <div class="storage-card">
                <div class="storage-label">Disk bài nộp hiện tại</div>
                <div class="storage-value">{{ $summary['submission_disk'] }}</div>
                <div class="storage-muted">
                    File học sinh nộp mới sẽ lưu vào disk này.
                </div>
            </div>

            <div class="storage-card">
                <div class="storage-label">Trạng thái R2</div>
                <span class="storage-badge {{ $summary['r2_ready'] ? 'ok' : 'warn' }}">
                    <i class="fas {{ $summary['r2_ready'] ? 'fa-check-circle' : 'fa-exclamation-triangle' }}"></i>
                    {{ $summary['r2_ready'] ? 'Đã cấu hình đủ' : 'Thiếu cấu hình' }}
                </span>
                <div class="storage-muted">
                    Secret key: {{ $summary['r2_secret_configured'] ? 'Đã cấu hình' : 'Chưa cấu hình' }}
                </div>
            </div>

            <div class="storage-card">
                <div class="storage-label">Access key</div>
                <div class="storage-value">{{ $summary['r2_key'] }}</div>
                <div class="storage-muted">Không hiển thị secret key trên giao diện.</div>
            </div>
        </div>

        <div class="storage-grid">
            <div class="storage-card">
                <div class="storage-label">Bucket</div>
                <div class="storage-value">{{ $summary['r2_bucket'] ?: 'Chưa cấu hình' }}</div>
            </div>

            <div class="storage-card">
                <div class="storage-label">Region</div>
                <div class="storage-value">{{ $summary['r2_region'] ?: 'auto' }}</div>
            </div>

            <div class="storage-card">
                <div class="storage-label">Endpoint</div>
                <div class="storage-muted">{{ $summary['r2_endpoint'] ?: 'Chưa cấu hình' }}</div>
            </div>
        </div>
    </div>
@endsection
