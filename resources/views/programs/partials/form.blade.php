@php
    $selectedStatus = old('status', $program->status ?? 'draft');
@endphp

<div class="mb-3">
    <label class="form-label fw-bold small text-muted">Tên chương trình <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control bg-light border-0 py-2"
        value="{{ old('name', $program->name ?? '') }}" placeholder="Ví dụ: Web Frontend" required>
</div>

<div class="mb-3">
    <label class="form-label fw-bold small text-muted">Mã chương trình <span class="text-danger">*</span></label>
    <input type="text" name="code" class="form-control bg-light border-0 py-2"
        value="{{ old('code', $program->code ?? '') }}" placeholder="Ví dụ: WEB-FE" required>
    <div class="form-text">Mã nên ngắn, dễ nhận biết và không trùng nhau.</div>
</div>

<div class="mb-3">
    <label class="form-label fw-bold small text-muted">Mô tả</label>
    <textarea name="description" class="form-control bg-light border-0 py-2" rows="4"
        placeholder="Mục tiêu, đối tượng học, phạm vi kiến thức...">{{ old('description', $program->description ?? '') }}</textarea>
</div>

<div class="mb-0">
    <label class="form-label fw-bold small text-muted">Trạng thái</label>
    <select name="status" class="form-select bg-light border-0 py-2" required>
        <option value="draft" @selected($selectedStatus === 'draft')>Draft - Đang soạn</option>
        <option value="published" @selected($selectedStatus === 'published')>Published - Đang sử dụng</option>
        <option value="hidden" @selected($selectedStatus === 'hidden')>Hidden - Tạm ẩn</option>
    </select>
</div>
