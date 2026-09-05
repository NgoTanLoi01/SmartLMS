@extends('layouts.app')

@section('title', 'Xem trước import câu hỏi')

@push('styles')
    @vite('resources/css/pages/question-bank.css')
@endpush

@section('content')
    @php
        $duplicateCount = collect($rows)->filter(fn($row) => (int) data_get($row, 'duplicate.similarity', 0) >= 82)->count();
        $recommendedCount = count($rows) - $duplicateCount;
        $difficultyLabels = ['easy' => 'Dễ', 'medium' => 'Trung bình', 'hard' => 'Khó'];
    @endphp

    <div class="lms-page question-import-preview-page">
        <x-ui.page-header title="Kiểm tra dữ liệu trước khi nhập"
            :breadcrumbs="[
                ['label' => 'Ngân hàng câu hỏi', 'url' => route('questions.index')],
                ['label' => 'Xem trước import'],
            ]">
            <x-slot:meta>
                <span><i class="fa-solid fa-shield-halved"></i> Chưa có dữ liệu nào được ghi</span>
                <span><i class="fa-solid fa-clock"></i> Phiên xác nhận còn hiệu lực 30 phút</span>
            </x-slot:meta>
            <x-slot:actions>
                <x-ui.button :href="route('questions.index')" tone="outline" icon="fa-arrow-left">Hủy import</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <section class="import-review-hero" aria-label="Tổng quan dữ liệu import">
            <div class="import-review-hero__context">
                <span class="import-review-hero__icon"><i class="fa-solid fa-file-excel"></i></span>
                <div>
                    <span class="section-eyebrow">ĐÍCH ĐẾN DỮ LIỆU</span>
                    <h2>{{ $bank->name }}</h2>
                    <p><i class="fa-solid fa-graduation-cap"></i> {{ $course->title }}</p>
                </div>
            </div>
            <div class="import-review-metrics">
                <div class="review-metric review-metric--total"><span><i class="fa-solid fa-table-list"></i></span><div><strong>{{ count($rows) }}</strong><small>Dòng hợp lệ</small></div></div>
                <div class="review-metric review-metric--ready"><span><i class="fa-solid fa-circle-check"></i></span><div><strong>{{ $recommendedCount }}</strong><small>Nên nhập</small></div></div>
                <div class="review-metric review-metric--warning"><span><i class="fa-solid fa-clone"></i></span><div><strong>{{ $duplicateCount }}</strong><small>Cần so sánh</small></div></div>
            </div>
        </section>

        @if ($errors->any())
            <div class="alert alert-danger import-review-alert"><i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}</div>
        @endif

        <form action="{{ route('questions.importBank.confirm') }}" method="POST" class="import-preview-form">
            @csrf
            <input type="hidden" name="preview_token" value="{{ $token }}">

            <section class="import-review-card">
                <header class="import-review-toolbar">
                    <div>
                        <h2>Đối chiếu từng câu hỏi</h2>
                        <p>Câu có độ tương đồng từ 82% được đề xuất bỏ qua. Bạn vẫn có thể thay đổi quyết định.</p>
                    </div>
                    <div class="import-review-toolbar__actions">
                        <button type="button" class="review-action-button" data-import-action="recommended"><i class="fa-solid fa-wand-magic-sparkles"></i> Theo đề xuất</button>
                        <button type="button" class="review-action-button" data-import-action="all"><i class="fa-solid fa-check-double"></i> Chọn tất cả</button>
                        <button type="button" class="review-action-button" data-import-action="none"><i class="fa-solid fa-ban"></i> Bỏ chọn</button>
                    </div>
                </header>

                <div class="question-table-wrap">
                    <table class="question-table import-preview-table">
                        <thead>
                            <tr>
                                <th class="import-row-number">Dòng</th>
                                <th class="import-question-column">Nội dung và đáp án</th>
                                <th>Phân loại</th>
                                <th>Kiểm tra tương đồng</th>
                                <th class="import-action-column">Quyết định</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $index => $row)
                                @php
                                    $duplicate = $row['duplicate'] ?? null;
                                    $similarity = (int) ($duplicate['similarity'] ?? 0);
                                    $shouldSkip = $similarity >= 82;
                                @endphp
                                <tr @class(['duplicate-row' => $shouldSkip]) data-import-row data-duplicate="{{ $shouldSkip ? '1' : '0' }}">
                                    <td data-label="Dòng" class="import-row-number"><span class="excel-row-number">{{ $row['row_number'] }}</span></td>
                                    <td data-label="Nội dung và đáp án">
                                        <div class="import-question-heading">
                                            <span class="type-badge type-single_choice">Một đáp án</span>
                                            @if ($shouldSkip)<span class="review-needed-badge"><i class="fa-solid fa-eye"></i> Cần xem lại</span>@endif
                                        </div>
                                        <div class="q-text">{{ $row['question_text'] }}</div>
                                        <ol class="preview-options" type="A">
                                            @foreach ($row['options'] as $optionIndex => $option)
                                                <li @class(['is-correct' => $optionIndex === ord($row['correct_letter']) - ord('A')])>
                                                    <span>{{ $option }}</span>
                                                    @if ($optionIndex === ord($row['correct_letter']) - ord('A'))<i class="fa-solid fa-check" title="Đáp án đúng"></i>@endif
                                                </li>
                                            @endforeach
                                        </ol>
                                    </td>
                                    <td data-label="Phân loại">
                                        <span class="diff-badge diff-{{ $row['difficulty'] }}">{{ $difficultyLabels[$row['difficulty']] }}</span>
                                        @if ($row['tags'] !== [])
                                            <div class="question-tags">
                                                @foreach ($row['tags'] as $tag)<span><i class="fa-solid fa-tag"></i>{{ $tag }}</span>@endforeach
                                            </div>
                                        @else
                                            <span class="no-tags-label">Chưa có tag</span>
                                        @endif
                                    </td>
                                    <td data-label="Kiểm tra tương đồng">
                                        @if ($shouldSkip)
                                            <div class="duplicate-result">
                                                <div class="duplicate-result__score"><strong>{{ $similarity }}%</strong><span>tương đồng</span></div>
                                                <div class="duplicate-result__bar"><span style="width: {{ $similarity }}%"></span></div>
                                            </div>
                                            <div class="duplicate-reference">
                                                @if (isset($duplicate['question_id']))
                                                    <strong>Câu #{{ $duplicate['question_id'] }}</strong>
                                                    <span>{{ Str::limit($duplicate['text'] ?? '', 100) }}</span>
                                                @else
                                                    <strong>Trong cùng file</strong>
                                                    <span>Giống dòng {{ $rows[$duplicate['batch_index']]['row_number'] ?? 'phía trên' }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="duplicate-clear"><span><i class="fa-solid fa-check"></i></span><div><strong>Không phát hiện trùng</strong><small>Có thể nhập an toàn</small></div></div>
                                        @endif
                                    </td>
                                    <td data-label="Quyết định">
                                        <label class="import-decision">
                                            <span>Hành động</span>
                                            <select name="actions[{{ $index }}]" class="form-select import-row-action" required>
                                                <option value="import" @selected(!$shouldSkip)>Nhập câu này</option>
                                                <option value="skip" @selected($shouldSkip)>Bỏ qua</option>
                                            </select>
                                        </label>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="import-confirmation-bar">
                <div class="import-confirmation-status">
                    <span><i class="fa-solid fa-file-circle-check"></i></span>
                    <div><strong><b id="import-selected-count">{{ $recommendedCount }}</b> / {{ count($rows) }} câu sẽ được nhập</strong><small>Các dòng bỏ qua không làm thay đổi dữ liệu hiện có.</small></div>
                </div>
                <div class="import-confirmation-actions">
                    <x-ui.button :href="route('questions.index')" tone="outline">Hủy</x-ui.button>
                    <x-ui.button type="submit" icon="fa-file-import">Xác nhận nhập</x-ui.button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const rows = Array.from(document.querySelectorAll('[data-import-row]'));
            const selects = rows.map(row => row.querySelector('.import-row-action'));
            const selectedCount = document.getElementById('import-selected-count');

            const refresh = () => {
                const count = selects.filter(select => select.value === 'import').length;
                if (selectedCount) selectedCount.textContent = count;
                rows.forEach((row, index) => row.classList.toggle('is-skipped', selects[index].value === 'skip'));
            };

            document.querySelectorAll('[data-import-action]').forEach(button => {
                button.addEventListener('click', () => {
                    rows.forEach((row, index) => {
                        selects[index].value = button.dataset.importAction === 'all'
                            ? 'import'
                            : button.dataset.importAction === 'none'
                                ? 'skip'
                                : (row.dataset.duplicate === '1' ? 'skip' : 'import');
                    });
                    refresh();
                });
            });
            selects.forEach(select => select.addEventListener('change', refresh));
            refresh();
        });
    </script>
@endpush
