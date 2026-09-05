@extends('layouts.app')

@section('title', 'Tình trạng lưu trữ')

@push('styles')
    @vite('resources/css/pages/system-operations.css')
@endpush

@section('content')
    @php
        $submissionDisk = strtoupper((string) $summary['submission_disk']);
        $r2Ready = (bool) $summary['r2_ready'];
        $r2IsActive = $summary['submission_disk'] === 'r2';
        $configurationItems = [
            ['ready' => filled($summary['r2_bucket']), 'label' => 'Bucket'],
            ['ready' => filled($summary['r2_endpoint']), 'label' => 'Endpoint'],
            ['ready' => $summary['r2_key'] !== 'Chưa cấu hình', 'label' => 'Access key'],
            ['ready' => $summary['r2_secret_configured'], 'label' => 'Secret key'],
        ];
        $configuredCount = collect($configurationItems)->where('ready', true)->count();
    @endphp

    <div class="lms-page system-operations-page storage-health-page">
        <section class="system-hero storage-hero" aria-label="Tổng quan tình trạng lưu trữ">
            <div class="system-hero__accent" aria-hidden="true"></div>
            <x-ui.page-header title="Tình trạng lưu trữ">
                <x-slot:meta>
                    <span><i class="fa-solid fa-shield-halved"></i> Theo dõi nơi lưu bài nộp và học liệu</span>
                    <span><i class="fa-solid fa-hard-drive"></i> Disk đang dùng: {{ $submissionDisk }}</span>
                </x-slot:meta>
                <x-slot:actions>
                    <form method="POST" action="{{ route('system.storage.test') }}">
                        @csrf
                        <input type="hidden" name="disk" value="public">
                        <button type="submit" class="system-button system-button--neutral">
                            <i class="fa-solid fa-folder-open"></i> Kiểm tra nội bộ
                        </button>
                    </form>
                    <form method="POST" action="{{ route('system.storage.test') }}">
                        @csrf
                        <input type="hidden" name="disk" value="r2">
                        <button type="submit" class="system-button system-button--cloud">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Kiểm tra R2
                        </button>
                    </form>
                </x-slot:actions>
            </x-ui.page-header>

            <div class="system-stats">
                <article class="system-stat stat-blue">
                    <span><i class="fa-solid fa-box-archive"></i></span>
                    <div><strong>{{ $submissionDisk }}</strong><small>Vùng lưu bài nộp hiện tại</small></div>
                </article>
                <article class="system-stat {{ $r2Ready ? 'stat-green' : 'stat-red' }}">
                    <span><i class="fa-solid {{ $r2Ready ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i></span>
                    <div><strong>{{ $r2Ready ? 'Sẵn sàng' : 'Thiếu cấu hình' }}</strong><small>Trạng thái Cloudflare R2</small></div>
                </article>
                <article class="system-stat stat-cyan">
                    <span><i class="fa-solid fa-list-check"></i></span>
                    <div><strong>{{ $configuredCount }}/{{ count($configurationItems) }}</strong><small>Thành phần R2 đã cấu hình</small></div>
                </article>
                <article class="system-stat {{ $r2IsActive ? 'stat-violet' : 'stat-slate' }}">
                    <span><i class="fa-solid fa-route"></i></span>
                    <div><strong>{{ $r2IsActive ? 'Đang dùng R2' : 'Đang dùng nội bộ' }}</strong><small>Đích lưu file tải lên mới</small></div>
                </article>
            </div>
        </section>

        @if ($lastResult)
            <section class="storage-check-result {{ $lastResult['ok'] ? 'is-success' : 'is-failed' }}" role="status"
                aria-label="Kết quả kiểm tra lưu trữ gần nhất">
                <span class="storage-check-result__icon">
                    <i class="fa-solid {{ $lastResult['ok'] ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
                </span>
                <div class="storage-check-result__body">
                    <span class="system-eyebrow">KẾT QUẢ KIỂM TRA GẦN NHẤT</span>
                    <div class="storage-check-result__heading">
                        <strong>{{ strtoupper((string) $lastResult['disk']) }}</strong>
                        <time>{{ $lastResult['checked_at'] }}</time>
                    </div>
                    <p>{{ $lastResult['message'] }}</p>
                    <code>{{ $lastResult['path'] }}</code>
                </div>
            </section>
        @endif

        <div class="storage-health-layout">
            <section class="system-list-card storage-config-card" aria-labelledby="storage-config-title">
                <header class="system-section-header">
                    <div>
                        <span class="system-section-icon icon-cyan"><i class="fa-solid fa-cloud"></i></span>
                        <div>
                            <h2 id="storage-config-title">Cấu hình Cloudflare R2</h2>
                            <p>Thông tin nhạy cảm được che trước khi hiển thị.</p>
                        </div>
                    </div>
                    <span class="system-status {{ $r2Ready ? 'status-success' : 'status-failed' }}">
                        {{ $r2Ready ? 'Đủ cấu hình' : 'Cần bổ sung' }}
                    </span>
                </header>

                <dl class="storage-config-list">
                    <div>
                        <dt><span><i class="fa-solid fa-database"></i></span> Kho lưu trữ</dt>
                        <dd>{{ $summary['r2_bucket'] ?: 'Chưa cấu hình' }}</dd>
                    </div>
                    <div>
                        <dt><span><i class="fa-solid fa-earth-asia"></i></span> Vùng máy chủ</dt>
                        <dd>{{ $summary['r2_region'] ?: 'Tự động' }}</dd>
                    </div>
                    <div>
                        <dt><span><i class="fa-solid fa-key"></i></span> Khóa truy cập</dt>
                        <dd><code>{{ $summary['r2_key'] }}</code></dd>
                    </div>
                    <div>
                        <dt><span><i class="fa-solid fa-lock"></i></span> Khóa bí mật</dt>
                        <dd>
                            <span class="storage-config-state {{ $summary['r2_secret_configured'] ? 'is-ready' : 'is-missing' }}">
                                <i class="fa-solid {{ $summary['r2_secret_configured'] ? 'fa-check' : 'fa-xmark' }}"></i>
                                {{ $summary['r2_secret_configured'] ? 'Đã cấu hình' : 'Chưa cấu hình' }}
                            </span>
                        </dd>
                    </div>
                    <div class="storage-config-endpoint">
                        <dt><span><i class="fa-solid fa-link"></i></span> Điểm kết nối</dt>
                        <dd title="{{ $summary['r2_endpoint'] }}">{{ $summary['r2_endpoint'] ?: 'Chưa cấu hình' }}</dd>
                    </div>
                </dl>
            </section>

            <aside class="system-list-card storage-test-card" aria-labelledby="storage-test-title">
                <header class="system-section-header">
                    <div>
                        <span class="system-section-icon icon-violet"><i class="fa-solid fa-stethoscope"></i></span>
                        <div>
                            <h2 id="storage-test-title">Kiểm tra kết nối</h2>
                            <p>Tạo rồi tự động xóa một file thử nghiệm.</p>
                        </div>
                    </div>
                </header>

                <div class="storage-test-targets">
                    <article>
                        <span class="storage-target-icon is-local"><i class="fa-solid fa-folder-tree"></i></span>
                        <div><strong>Lưu trữ nội bộ</strong><small>Xác minh quyền ghi, đọc và xóa file trên máy chủ.</small></div>
                        <form method="POST" action="{{ route('system.storage.test') }}">
                            @csrf
                            <input type="hidden" name="disk" value="public">
                            <button class="system-button system-button--small system-button--neutral" type="submit">
                                <i class="fa-solid fa-play"></i> Chạy thử
                            </button>
                        </form>
                    </article>
                    <article>
                        <span class="storage-target-icon is-cloud"><i class="fa-solid fa-cloud"></i></span>
                        <div><strong>Cloudflare R2</strong><small>Xác minh thông tin xác thực, bucket và endpoint.</small></div>
                        <form method="POST" action="{{ route('system.storage.test') }}">
                            @csrf
                            <input type="hidden" name="disk" value="r2">
                            <button class="system-button system-button--small system-button--cloud" type="submit">
                                <i class="fa-solid fa-play"></i> Chạy thử
                            </button>
                        </form>
                    </article>
                </div>

                <div class="storage-security-note">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>File kiểm tra không chứa dữ liệu người dùng và được xóa ngay sau khi xác minh.</span>
                </div>
            </aside>
        </div>

        <section class="storage-flow-card" aria-labelledby="storage-flow-title">
            <header>
                <span><i class="fa-solid fa-file-shield"></i></span>
                <div><span class="system-eyebrow">LUỒNG LƯU FILE</span><h2 id="storage-flow-title">File bài nộp mới được xử lý thế nào?</h2></div>
            </header>
            <div class="storage-flow-steps">
                <div><b>1</b><span><strong>Nhận file</strong><small>Kiểm tra định dạng và dung lượng tải lên.</small></span></div>
                <i class="fa-solid fa-arrow-right"></i>
                <div><b>2</b><span><strong>Lưu vào {{ $submissionDisk }}</strong><small>Dùng disk được cấu hình cho bài nộp.</small></span></div>
                <i class="fa-solid fa-arrow-right"></i>
                <div><b>3</b><span><strong>Xác minh</strong><small>Kiểm tra file tồn tại trước khi ghi nhận hoàn tất.</small></span></div>
            </div>
        </section>
    </div>
@endsection
