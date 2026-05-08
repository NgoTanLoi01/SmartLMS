<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DocumentProcessingService;
use App\Models\DocumentChunk;

class DocumentController extends Controller
{
    protected $docService;

    public function __construct(DocumentProcessingService $docService)
    {
        $this->docService = $docService;
    }
    // 

    public function index()
    {
        // Thêm ::on('pgsql') để báo cho Laravel biết bảng này nằm ở Postgres
        $documents = \App\Models\DocumentChunk::on('pgsql')->select('document_name', \DB::raw('MAX(created_at) as created_at'), \DB::raw('COUNT(*) as total_chunks'))->groupBy('document_name')->orderBy('created_at', 'desc')->get();

        return view('documents.upload', compact('documents'));
    }

    public function store(Request $request)
    {
        // Validate file và khóa học (nếu có)
        $request->validate([
            'file' => 'required|mimes:pdf|max:10240', // Giới hạn 10MB
            'course_id' => 'nullable|integer',
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
    // app/Http/Controllers/DocumentController.php

    public function uploadPage()
    {
        // Lấy danh sách tên tài liệu duy nhất và số lượng đoạn (chunks) của mỗi tài liệu
        $documents = \App\Models\DocumentChunk::select('document_name', \DB::raw('count(*) as total_chunks'), 'created_at')->groupBy('document_name', 'created_at')->orderBy('created_at', 'desc')->get();

        return view('documents.upload', compact('documents'));
    }
    public function destroy($name)
    {
        // Xóa tất cả các đoạn vector thuộc về tài liệu này
        \App\Models\DocumentChunk::where('document_name', $name)->delete();

        return back()->with('success', 'Đã xóa kiến thức của tài liệu: ' . $name);
    }
}
