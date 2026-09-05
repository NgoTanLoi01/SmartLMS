@extends('layouts.app')

@section('title', 'Lịch sử câu hỏi')

@push('styles')
    @vite('resources/css/pages/question-bank.css')
@endpush

@section('content')
    @php
        $difficultyLabels = ['easy' => 'Dễ', 'medium' => 'Trung bình', 'hard' => 'Khó'];
        $changeLabels = [
            'created' => 'Khởi tạo',
            'baseline' => 'Dữ liệu gốc',
            'updated' => 'Cập nhật nội dung',
            'bulk_updated' => 'Cập nhật phân loại',
            'archived' => 'Lưu trữ',
            'restored' => 'Khôi phục',
        ];
        $changeIcons = [
            'created' => 'fa-circle-plus',
            'baseline' => 'fa-database',
            'updated' => 'fa-pen',
            'bulk_updated' => 'fa-tags',
            'archived' => 'fa-box-archive',
            'restored' => 'fa-rotate-left',
        ];
        $fieldLabels = [
            'question_text' => 'Nội dung',
            'question_type' => 'Hình thức',
            'difficulty' => 'Độ khó',
            'tags' => 'Tags',
            'status' => 'Trạng thái',
            'options' => 'Đáp án',
            'answer_config' => 'Cấu hình chấm',
            'course_id' => 'Khóa học',
            'question_bank_id' => 'Ngân hàng',
            'quiz_passage_id' => 'Ngữ liệu',
        ];
    @endphp

    <div class="lms-page question-version-page">
        <x-ui.page-header title="Lịch sử phiên bản"
            :breadcrumbs="[
                ['label' => 'Ngân hàng câu hỏi', 'url' => route('questions.index')],
                ['label' => 'Câu #' . $question->id],
                ['label' => 'Lịch sử'],
            ]">
            <x-slot:meta>
                <span><i class="fa-solid fa-code-branch"></i> {{ $versions->count() }} phiên bản</span>
                <span><i class="fa-solid fa-shield-halved"></i> Nhật ký chỉ đọc</span>
            </x-slot:meta>
            <x-slot:actions>
                <x-ui.button :href="route('questions.index', ['question_bank_id' => $question->question_bank_id])"
                    tone="outline" icon="fa-arrow-left">Về ngân hàng</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <section class="version-page-intro">
            <div class="version-page-intro__icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div>
                <span class="section-eyebrow">NHẬT KÝ THAY ĐỔI</span>
                <h2>Theo dõi toàn bộ vòng đời câu hỏi</h2>
                <p>Mỗi lần sửa nội dung, đáp án, phân loại hoặc trạng thái đều tạo một bản chụp riêng để đối chiếu.</p>
            </div>
            <div class="version-page-intro__latest">
                <span>Phiên bản hiện tại</span>
                <strong>v{{ $question->current_version ?: 1 }}</strong>
            </div>
        </section>

        <div class="question-version-layout">
            <aside class="current-question-panel">
                <div class="current-question-panel__top">
                    <span class="current-badge"><i class="fa-solid fa-circle-check"></i> Đang hiện hành</span>
                    <span class="question-id-label">#{{ $question->id }}</span>
                </div>
                <h2>{{ $question->question_text }}</h2>
                <div class="current-question-classification">
                    <span class="type-badge type-{{ $question->question_type }}">{{ $question->typeLabel() }}</span>
                    <span class="diff-badge diff-{{ $question->difficulty }}">{{ $difficultyLabels[$question->difficulty] ?? $question->difficulty }}</span>
                </div>

                @if (!empty($question->tags))
                    <div class="current-question-tags">
                        <span class="panel-label">Tags</span>
                        <div class="question-tags">
                            @foreach ($question->tags as $tag)<span><i class="fa-solid fa-tag"></i>{{ $tag }}</span>@endforeach
                        </div>
                    </div>
                @endif

                <dl class="current-question-details">
                    <div><dt><i class="fa-solid fa-layer-group"></i> Ngân hàng</dt><dd>{{ $question->questionBank?->name ?? 'Chưa gắn ngân hàng' }}</dd></div>
                    <div><dt><i class="fa-solid fa-graduation-cap"></i> Khóa học</dt><dd>{{ $question->course?->title ?? 'Dùng chung qua ngân hàng' }}</dd></div>
                    <div><dt><i class="fa-solid fa-list-check"></i> Phương án</dt><dd>{{ $question->options->count() ?: 'Theo cấu hình chấm' }}</dd></div>
                    <div><dt><i class="fa-solid fa-clock"></i> Cập nhật</dt><dd>{{ $question->updated_at?->format('d/m/Y H:i') }}</dd></div>
                </dl>

                <div class="version-readonly-note"><i class="fa-solid fa-circle-info"></i><span>Lịch sử được giữ để đối chiếu và không thể chỉnh sửa trực tiếp.</span></div>
            </aside>

            <main class="version-history-panel">
                <header class="version-history-heading">
                    <div><span class="section-eyebrow">DÒNG THỜI GIAN</span><h2>Các phiên bản đã ghi nhận</h2></div>
                    <span>{{ $versions->count() }} mốc thay đổi</span>
                </header>

                <section class="question-version-timeline" aria-label="Danh sách phiên bản">
                    @forelse ($versions as $version)
                        @php
                            $snapshot = $version->snapshot ?? [];
                            $previousVersion = $versions->get($loop->index + 1);
                            $previousSnapshot = $previousVersion?->snapshot ?? [];
                            $changedFields = $previousVersion
                                ? collect($fieldLabels)->filter(fn($label, $field) => ($snapshot[$field] ?? null) !== ($previousSnapshot[$field] ?? null))
                                : collect(['created' => 'Khởi tạo dữ liệu']);
                            $tone = match ($version->change_type) {
                                'archived' => 'danger',
                                'restored', 'created' => 'success',
                                'bulk_updated' => 'violet',
                                default => 'blue',
                            };
                            $isLatest = $loop->first;
                            $status = $snapshot['status'] ?? 'published';
                        @endphp
                        <article @class(['question-version-card', "version-tone-{$tone}", 'is-latest' => $isLatest])>
                            <div class="version-marker"><i class="fa-solid {{ $changeIcons[$version->change_type] ?? 'fa-code-branch' }}"></i></div>
                            <div class="version-card-content">
                                <header class="version-card-header">
                                    <div class="version-card-identity">
                                        <div class="version-number-line">
                                            <span class="version-number">v{{ $version->version_number }}</span>
                                            <span class="version-kind">{{ $changeLabels[$version->change_type] ?? $version->change_type }}</span>
                                            @if ($isLatest)<span class="latest-version-badge"><i class="fa-solid fa-bolt"></i> Mới nhất</span>@endif
                                        </div>
                                        <h3>{{ $version->change_summary ?: 'Ghi nhận thay đổi câu hỏi' }}</h3>
                                    </div>
                                    <div class="version-time">
                                        <strong>{{ $version->created_at?->format('H:i') }}</strong>
                                        <span>{{ $version->created_at?->format('d/m/Y') }}</span>
                                    </div>
                                </header>

                                <div class="version-author-line">
                                    <span class="version-author-avatar"><i class="fa-solid fa-user"></i></span>
                                    <div><strong>{{ $version->changedBy?->name ?? 'Hệ thống' }}</strong><small>{{ $version->changedBy ? 'Người thực hiện' : 'Dữ liệu được khởi tạo tự động' }}</small></div>
                                </div>

                                <div class="version-change-summary">
                                    <span class="change-summary-label">Thay đổi</span>
                                    <div>
                                        @forelse ($changedFields as $field => $label)
                                            <span><i class="fa-solid fa-circle"></i>{{ $label }}</span>
                                        @empty
                                            <span class="no-change"><i class="fa-solid fa-minus"></i> Không thay đổi dữ liệu</span>
                                        @endforelse
                                    </div>
                                </div>

                                @if ($previousVersion && in_array('Nội dung', $changedFields->values()->all(), true))
                                    <div class="version-text-comparison">
                                        <div><span>Trước</span><p>{{ $previousSnapshot['question_text'] ?? '—' }}</p></div>
                                        <i class="fa-solid fa-arrow-right"></i>
                                        <div><span>Sau</span><p>{{ $snapshot['question_text'] ?? '—' }}</p></div>
                                    </div>
                                @endif

                                <details class="version-snapshot" @if($isLatest) open @endif>
                                    <summary>
                                        <span><i class="fa-solid fa-table-list"></i> Xem dữ liệu phiên bản</span>
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </summary>
                                    <div class="version-snapshot-body">
                                        <div class="version-snapshot-question">{{ $snapshot['question_text'] ?? 'Không có nội dung' }}</div>
                                        <div class="version-snapshot-meta">
                                            <span><i class="fa-solid fa-gauge"></i>{{ $difficultyLabels[$snapshot['difficulty'] ?? ''] ?? ($snapshot['difficulty'] ?? '—') }}</span>
                                            <span class="snapshot-status snapshot-status-{{ $status }}"><i class="fa-solid {{ $status === 'archived' ? 'fa-box-archive' : 'fa-circle-check' }}"></i>{{ $status === 'archived' ? 'Đã lưu trữ' : 'Đang sử dụng' }}</span>
                                            @foreach ($snapshot['tags'] ?? [] as $tag)<span><i class="fa-solid fa-tag"></i>{{ $tag }}</span>@endforeach
                                        </div>
                                        @if (!empty($snapshot['options']))
                                            <div class="version-answer-block">
                                                <span class="panel-label">Phương án trả lời</span>
                                                <ol class="version-options" type="A">
                                                    @foreach ($snapshot['options'] as $option)
                                                        <li @class(['is-correct' => !empty($option['is_correct'])])>
                                                            <span>{{ $option['option_text'] ?? '' }}</span>
                                                            @if (!empty($option['is_correct']))<em><i class="fa-solid fa-check"></i> Đúng</em>@endif
                                                        </li>
                                                    @endforeach
                                                </ol>
                                            </div>
                                        @elseif (!empty($snapshot['answer_config']))
                                            <div class="version-config-block"><span class="panel-label">Cấu hình chấm điểm</span><pre>{{ json_encode($snapshot['answer_config'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></div>
                                        @endif
                                    </div>
                                </details>
                            </div>
                        </article>
                    @empty
                        <x-ui.empty-state title="Chưa có lịch sử" description="Câu hỏi chưa phát sinh phiên bản." icon="fa-clock-rotate-left" />
                    @endforelse
                </section>
            </main>
        </div>
    </div>
@endsection
