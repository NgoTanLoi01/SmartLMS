<?php
namespace App\Http\Controllers;

use App\Jobs\ProcessDocumentPdf;
use App\Models\AiOperation;
use Illuminate\Http\Request;
use App\Models\DocumentChunk;
use App\Models\Course; // Thêm model Course
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
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
        $processingOperations = AiOperation::where('feature', 'document_embedding')
            ->where('user_id', $user->id)
            ->latest()
            ->take(20)
            ->get();
        return view('documents.upload', compact('documents', 'courses', 'processingOperations'));
    }

    // ==========================================
    // 2. XỬ LÝ UPLOAD VÀ LƯU VECTOR
    // ==========================================
    public function store(Request $request)
    {
        // Validate file và khóa học (nếu có)
        $request->validate([
            'file' => 'required|mimes:pdf|max:10240', // Giới hạn 10MB
            'course_id' => 'required|integer|min:0',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $documentName = $file->getClientOriginalName();
            $courseId = $request->input('course_id');
            $course = (int) $courseId === 0 ? null : Course::findOrFail($courseId);
            abort_unless(
                $request->user()->role === 'admin' || ($course && (int) $course->teacher_id === (int) $request->user()->id),
                403
            );

            // 1. Lưu file PDF vào hệ thống storage của Laravel
            $path = $file->store('documents', 'local');
            $operation = AiOperation::create([
                'user_id' => $request->user()->id,
                'feature' => 'document_embedding',
                'provider' => 'gemini',
                'model' => 'gemini-embedding-001',
                'status' => AiOperation::STATUS_QUEUED,
                'subject_type' => $course ? Course::class : null,
                'subject_id' => $course?->id,
                'metadata' => ['document_name' => $documentName, 'path' => $path],
            ]);
            ProcessDocumentPdf::dispatch($operation->id, $path, $documentName, (int) $courseId)->afterCommit();

            return back()->with('success', 'Tài liệu đã vào hàng đợi xử lý. Bạn có thể rời trang trong khi worker tạo embedding.');
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
