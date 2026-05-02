<div class="modal fade" id="addModuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('modules.store') }}" method="POST" class="modal-content border-0">
            @csrf
            <input type="hidden" name="course_id" value="{{ $course->id }}">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Thêm chương mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <input type="text" name="title" class="form-control bg-light border-0"
                    placeholder="Tên chương học..." required>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="submit" class="btn btn-primary rounded-pill px-4 w-100">Lưu lại</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editModuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editModuleForm" method="POST" class="modal-content border-0">
            @csrf @method('PUT')
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-warning">Sửa chương</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <input type="text" name="title" id="editModuleTitle" class="form-control bg-light border-0"
                    required>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="submit" class="btn btn-warning text-dark fw-bold rounded-pill px-4 w-100">Cập
                    nhật</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="addLessonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('lessons.store') }}" method="POST" enctype="multipart/form-data"
            class="modal-content border-0">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Thêm bài học mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="small fw-bold">Chọn chương</label>
                    <select name="module_id" class="form-select bg-light border-0" required>
                        @foreach ($course->modules as $module)
                            <option value="{{ $module->id }}">{{ $module->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Tiêu đề bài học</label>
                    <input type="text" name="title" class="form-control bg-light border-0"
                        placeholder="Tiêu đề bài học..." required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Link (Youtube, Google Drive, Zoom...)</label>
                    <input type="url" name="video_url" class="form-control bg-light border-0"
                        placeholder="https://...">
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Tài liệu đính kèm (PDF, Word, ZIP...)</label>
                    <input type="file" name="attachment" class="form-control bg-light border-0">
                    <small class="text-muted fst-italic">Bỏ trống nếu không có tài liệu</small>
                </div>
                <label class="small fw-bold">Nội dung chi tiết</label>
                <textarea name="content" id="addLessonContent" class="form-control bg-light border-0" rows="4"></textarea>
            </div>
            <div class="modal-footer border-0">
                <button type="submit" class="btn btn-primary rounded-pill px-4 w-100">Lưu bài học</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editLessonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="editLessonForm" method="POST" enctype="multipart/form-data" class="modal-content border-0">
            @csrf @method('PUT')
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-warning">Sửa bài học</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="small fw-bold">Chương</label>
                    <select name="module_id" id="editLessonModule" class="form-select bg-light border-0" required>
                        @foreach ($course->modules as $module)
                            <option value="{{ $module->id }}">{{ $module->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Tiêu đề bài học</label>
                    <input type="text" name="title" id="editLessonTitle" class="form-control bg-light border-0"
                        required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Tài liệu đính kèm (PDF, Word, ZIP...)</label>
                    <input type="file" name="attachment" class="form-control bg-light border-0">
                    <small class="text-muted fst-italic">Bỏ trống nếu không có tài liệu</small>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Link (Youtube, Google Drive, Zoom...)</label>
                    <input type="url" name="video_url" id="editLessonVideo"
                        class="form-control bg-light border-0">
                </div>
                <label class="small fw-bold">Nội dung chi tiết</label>
                <textarea name="content" id="editLessonContent" class="form-control bg-light border-0" rows="4"></textarea>
            </div>
            <div class="modal-footer border-0">
                <button type="submit" class="btn btn-warning text-dark fw-bold rounded-pill px-4 w-100">Cập
                    nhật</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="addCourseAssignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('assignments.store') }}" method="POST" class="modal-content border-0">
            @csrf
            <input type="hidden" name="course_id" value="{{ $course->id }}">
            <input type="hidden" name="status" value="published">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Thêm bài tập thực hành</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Bài tập thuộc bài học nào?</label>
                        <select name="lesson_id" class="form-select bg-light border-0" required>
                            <option value="" disabled selected>-- Chọn bài học --</option>
                            @foreach ($course->modules as $module)
                                <optgroup label="{{ $module->title }}">
                                    @foreach ($module->lessons as $lesson)
                                        <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Hạn nộp (Deadline)</label>
                        <input type="datetime-local" name="due_date" class="form-control bg-light border-0" required>
                    </div>
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Tiêu đề bài tập</label>
                        <input type="text" name="title" class="form-control bg-light border-0"
                            placeholder="VD: Bài tập thực hành HTML cơ bản" required>
                    </div>
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Nội dung yêu cầu</label>
                        <textarea name="instructions" class="form-control bg-light border-0" rows="4"
                            placeholder="Nhập yêu cầu chi tiết..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="submit" class="btn btn-warning fw-bold text-dark rounded-pill px-4 w-100">Lưu bài
                    tập</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editAssignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="editAssignmentForm" method="POST" class="modal-content border-0">
            @csrf @method('PUT')
            <input type="hidden" name="course_id" value="{{ $course->id }}">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-warning">Sửa bài tập</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Bài tập thuộc bài học nào?</label>
                        <select name="lesson_id" id="editAssignmentLesson" class="form-select bg-light border-0"
                            required>
                            @foreach ($course->modules as $module)
                                <optgroup label="{{ $module->title }}">
                                    @foreach ($module->lessons as $lesson)
                                        <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted">Hạn nộp (Deadline)</label>
                        <input type="datetime-local" name="due_date" id="editAssignmentDue"
                            class="form-control bg-light border-0" required>
                    </div>
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Tiêu đề bài tập</label>
                        <input type="text" name="title" id="editAssignmentTitle"
                            class="form-control bg-light border-0" required>
                    </div>
                    <div class="col-12">
                        <label class="small fw-bold text-muted">Nội dung yêu cầu</label>
                        <textarea name="instructions" id="editAssignmentInstructions" class="form-control bg-light border-0" rows="4"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="submit" class="btn btn-warning fw-bold text-dark rounded-pill px-4 w-100">Cập nhật bài
                    tập</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="viewSubmissionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold" id="modal-assignment-name">Danh sách nộp bài</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="px-4 py-3">Học sinh</th>
                                <th class="px-4 py-3">Trạng thái</th>
                                <th class="px-4 py-3">Thời gian nộp</th>
                                <th class="px-4 py-3">File bài làm</th>
                                <th class="px-4 py-3">Chấm điểm</th>
                            </tr>
                        </thead>
                        <tbody id="submissions-table-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addQuizModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('quizzes.store') }}" method="POST" class="modal-content border-0">
            @csrf
            <input type="hidden" name="course_id" value="{{ $course->id }}">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: #6f42c1;">Tạo đề thi ngẫu nhiên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="mb-3">
                    <label class="small fw-bold text-muted text-uppercase">Tên bài thi</label>
                    <input type="text" name="title" class="form-control fw-bold border-1"
                        placeholder="VD: Kiểm tra giữa kỳ..." required>
                </div>
                <div class="mb-4">
                    <label class="small fw-bold text-muted text-uppercase">Thời gian làm bài (Phút)</label>
                    <input type="number" name="time_limit" class="form-control border-1" value="30"
                        min="1" required>
                </div>
                <h6 class="fw-bold mb-3 border-bottom pb-2 text-primary">Cấu trúc đề thi (Số lượng câu)</h6>
                <div class="alert alert-info small border-0 py-2 mb-3">
                    <i class="fas fa-magic me-1"></i> Hệ thống sẽ bốc ngẫu nhiên câu hỏi từ Ngân hàng để trộn đề cho
                    mỗi học sinh.
                </div>
                <div class="row g-2">
                    <div class="col-4">
                        <label class="small fw-bold text-success">Câu Dễ</label>
                        <input type="number" name="easy_count" class="form-control text-center" value="10"
                            min="0" required>
                    </div>
                    <div class="col-4">
                        <label class="small fw-bold text-warning text-dark">Trung bình</label>
                        <input type="number" name="medium_count" class="form-control text-center" value="5"
                            min="0" required>
                    </div>
                    <div class="col-4">
                        <label class="small fw-bold text-danger">Câu Khó</label>
                        <input type="number" name="hard_count" class="form-control text-center" value="5"
                            min="0" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4">
                <button type="submit" class="btn text-white rounded-pill px-4 w-100 fw-bold shadow-sm"
                    style="background-color: #6f42c1;"><i class="fas fa-random me-2"></i>Tạo đề thi</button>
            </div>
        </form>
    </div>
</div>
