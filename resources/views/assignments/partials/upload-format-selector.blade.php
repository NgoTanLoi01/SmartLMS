@php
    $formatGroups = \App\Support\AssignmentUploadTypes::groups();
    $defaultFormats = \App\Support\AssignmentUploadTypes::defaultExtensions();
    $selectedFormats = $selectedFormats ?? $defaultFormats;
    $fieldPrefix = $fieldPrefix ?? 'assignment';
    $maxFileSize = $maxFileSize ?? 20480;
    $controlClass = $controlClass ?? 'form-control';
@endphp

<div class="assignment-upload-settings" data-upload-settings="{{ $fieldPrefix }}">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
        <div>
            <label class="form-label fw-bold mb-1">Định dạng học viên được phép nộp</label>
            <div class="form-text mt-0">Chọn ít nhất một định dạng cho bài nộp dạng file.</div>
        </div>
        <div class="d-flex gap-1 flex-shrink-0">
            <button type="button" class="btn btn-sm btn-light" data-format-action="all">Chọn tất cả</button>
            <button type="button" class="btn btn-sm btn-light" data-format-action="default">Mặc định</button>
        </div>
    </div>

    <div class="row g-2">
        @foreach ($formatGroups as $groupKey => $group)
            <div class="col-12 col-md-6">
                <fieldset class="border rounded-3 p-3 h-100">
                    <legend class="float-none w-auto px-1 mb-1 small fw-bold text-muted">{{ $group['label'] }}</legend>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($group['extensions'] as $extension => $label)
                            @php($inputId = $fieldPrefix.'-format-'.$extension)
                            <div class="form-check form-check-inline m-0 border rounded-pill px-2 py-1">
                                <input id="{{ $inputId }}" class="form-check-input ms-0 me-1 assignment-format-input"
                                    type="checkbox" name="allowed_extensions[]" value="{{ $extension }}"
                                    data-default="{{ in_array($extension, $defaultFormats, true) ? '1' : '0' }}"
                                    @checked(in_array($extension, $selectedFormats, true))>
                                <label class="form-check-label small" for="{{ $inputId }}" title="{{ $label }}">
                                    .{{ strtoupper($extension) }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </fieldset>
            </div>
        @endforeach
    </div>

    <div class="mt-3">
        <label class="form-label fw-bold small text-muted" for="{{ $fieldPrefix }}-max-file-size">
            Dung lượng tối đa mỗi file
        </label>
        <input id="{{ $fieldPrefix }}-max-file-size" type="number" name="max_file_size"
            class="{{ $controlClass }}" value="{{ $maxFileSize }}" min="1" max="20480" step="1" required>
        <div class="form-text">Đơn vị KB, tối đa 20 MB. Ví dụ: 5120 = 5 MB, 10240 = 10 MB.</div>
    </div>
</div>
