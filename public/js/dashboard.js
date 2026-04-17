const contentDisplay = document.getElementById('content-display');

// 1. Hiển thị danh sách khóa học
async function loadCourses() {
    contentDisplay.innerHTML = '<div class="text-center">Đang tải...</div>';
    try {
        const res = await instance.get('/courses');
        let html = '<h3>Khóa học hiện có</h3><div class="row mt-3">';
        res.data.data.forEach(course => {
            html += `
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5>${course.title}</h5>
                            <p class="text-muted small">${course.description}</p>
                            <button class="btn btn-sm btn-primary" onclick="loadAssignments(${course.id})">Xem bài tập</button>
                        </div>
                    </div>
                </div>`;
        });
        contentDisplay.innerHTML = html + '</div>';
    } catch (err) {
        contentDisplay.innerHTML = '<div class="alert alert-danger">Lỗi tải khóa học.</div>';
    }
}

// 2. Hiển thị bài tập của khóa học
async function loadAssignments(courseId) {
    try {
        const res = await instance.get(`/courses/${courseId}/assignments`);
        let html = `<h3>Bài tập</h3><button class="btn btn-link p-0 mb-3" onclick="loadCourses()">← Quay lại</button><ul class="list-group">`;
        res.data.forEach(as => {
            html += `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${as.title}</strong> <br>
                        <small class="text-danger">Hạn nộp: ${as.due_date}</small>
                    </div>
                    <button class="btn btn-sm btn-success" onclick="openSubmitModal(${as.id})">Nộp bài</button>
                </li>`;
        });
        contentDisplay.innerHTML = html + '</ul>';
    } catch (err) { alert('Lỗi tải bài tập'); }
}

// 3. Mở Modal nộp bài
const submitModal = new bootstrap.Modal('#submitModal');
function openSubmitModal(asId) {
    document.getElementById('target-assignment-id').value = asId;
    submitModal.show();
}

// 4. Xử lý nộp file bài tập
document.getElementById('submit-assignment-form').onsubmit = async (e) => {
    e.preventDefault();
    const asId = document.getElementById('target-assignment-id').value;
    const file = document.getElementById('file-input').files[0];

    const formData = new FormData();
    formData.append('assignment_id', asId);
    formData.append('file', file);

    try {
        await instance.post('/submissions/upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        alert('Nộp bài thành công!');
        submitModal.hide();
    } catch (err) {
        alert(err.response?.data?.message || 'Lỗi nộp bài');
    }
};