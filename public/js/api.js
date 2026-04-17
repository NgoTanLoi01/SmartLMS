// Cấu hình mặc định cho Axios
const instance = axios.create({
    baseURL: '/api', // Chạy trên cùng domain nên chỉ cần /api
    headers: { 'Accept': 'application/json' }
});

// Mỗi khi gửi request, tự động đính kèm Token nếu có
instance.interceptors.request.use(config => {
    const token = localStorage.getItem('lms_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Xử lý nếu Token hết hạn (Lỗi 401)
instance.interceptors.response.use(
    response => response,
    error => {
        if (error.response.status === 401) {
            localStorage.clear();
            location.reload();
        }
        return Promise.reject(error);
    }
);