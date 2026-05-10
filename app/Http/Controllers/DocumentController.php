<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DocumentProcessingService;
use App\Models\DocumentChunk;
use App\Models\Course; // Thêm model Course
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    protected $docService;

    public function __construct(DocumentProcessingService $docService)
    {
        $this->docService = $docService;
    }

    // ==========================================
    // 1. HIỂN THỊ TRANG UPLOAD TÀI LIỆU
    // ==========================================
    public function index()
    {
        $user = auth()->user();

        // 1. Lấy danh sách khóa học (Admin thấy hết, Giáo viên thấy khóa của mình)
        if ($user->role === 'admin') {
            $courses = Course::all();
        } else {
            $courses = Course::where('teacher_id', $user->id)->get();
        }

        // 2. Lấy danh sách tài liệu từ PostgreSQL
        $documents = DocumentChunk::on('pgsql')->select('document_name', 'course_id', DB::raw('MAX(created_at) as created_at'), DB::raw('COUNT(*) as total_chunks'))->groupBy('document_name', 'course_id')->orderBy('created_at', 'desc')->get();
        return view('documents.upload', compact('documents', 'courses'));
    }

    // ==========================================
    // 2. XỬ LÝ UPLOAD VÀ LƯU VECTOR
    // ==========================================
    public function store(Request $request)
    {
        // Validate file và khóa học (nếu có)
        $request->validate([
            'file' => 'required|mimes:pdf|max:10240', // Giới hạn 10MB
            'course_id' => 'required|integer', // Đổi nullable thành required để ép chọn khóa học
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $documentName = $file->getClientOriginalName();
            $courseId = $request->input('course_id');

            // 1. Lưu file PDF vào hệ thống storage của Laravel
            $path = $file->store('documents', 'local');
            $fullPath = storage_path('app/private/' . $path);

            // 2. Kích hoạt tiến trình đọc PDF và nhúng Vector
            $this->docService->processAndStorePdf($fullPath, $documentName, $courseId);

            return back()->with('success', 'Tài liệu đã được tải lên. AI đang xử lý ngữ cảnh!');
        }

        return back()->with('error', 'Không tìm thấy file để xử lý.');
    }

    // ==========================================
    // 3. XÓA TÀI LIỆU KHỎI BỘ NÃO AI
    // ==========================================
    public function destroy($name)
    {
        // Thêm on('pgsql') để đảm bảo xóa đúng CSDL Vector, nếu không sẽ lỗi bảng không tồn tại bên MySQL
        DocumentChunk::on('pgsql')->where('document_name', $name)->delete();

        return back()->with('success', 'Đã xóa kiến thức của tài liệu: ' . $name);
    }
}
