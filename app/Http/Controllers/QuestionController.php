<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Option;
use App\Models\Course;
use Maatwebsite\Excel\Facades\Excel;

class QuestionController extends Controller
{
    // ==========================================
    // 1. HIỂN THỊ GIAO DIỆN NGÂN HÀNG CÂU HỎI
    // ==========================================
    public function index(Request $request)
    {
        $user = auth()->user();

        // Admin thấy tất cả khóa học, Giáo viên chỉ thấy khóa mình dạy
        if ($user->role === 'admin') {
            $courses = Course::all();
            // Thêm 'course.teacher' để load thông tin giáo viên phụ trách khóa học
            $query = Question::with(['course.teacher', 'options']);
        } else {
            $courses = Course::where('teacher_id', $user->id)->get();
            $query = Question::with(['course.teacher', 'options'])->whereIn('course_id', $courses->pluck('id'));
        }

        // Xử lý bộ lọc theo khóa học
        if ($request->has('course_id') && $request->course_id != '') {
            $query->where('course_id', $request->course_id);
        }

        // Lấy danh sách câu hỏi (có phân trang)
        $questions = $query->orderBy('created_at', 'desc')->paginate(15);

        // Giữ lại query string khi chuyển trang
        $questions->appends($request->all());

        return view('quizzes.question_bank', compact('courses', 'questions'));
    }

    // ==========================================
    // 2. THÊM CÂU HỎI VÀO NGÂN HÀNG
    // ==========================================
    public function storeBank(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'difficulty' => 'required|in:easy,medium,hard',
            'question_text' => 'required|string',
            'options' => 'required|array|size:4',
            'correct_option' => 'required|in:1,2,3,4',
        ]);

        $question = Question::create([
            'course_id' => $request->course_id,
            'difficulty' => $request->difficulty,
            'question_text' => $request->question_text,
        ]);

        foreach ($request->options as $index => $optionText) {
            Option::create([
                'question_id' => $question->id,
                'option_text' => $optionText,
                'is_correct' => $index == $request->correct_option ? true : false,
            ]);
        }

        return back()->with('success', 'Đã thêm câu hỏi vào Ngân hàng thành công!');
    }

    // ==========================================
    // 3. CẬP NHẬT CÂU HỎI TRONG NGÂN HÀNG
    // ==========================================
    public function updateBank(Request $request, $id)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'difficulty' => 'required|in:easy,medium,hard',
            'question_text' => 'required|string',
            'options' => 'required|array|size:4',
            'correct_option' => 'required|in:1,2,3,4',
        ]);

        $question = Question::findOrFail($id);

        if (auth()->user()->role !== 'admin' && auth()->id() !== $question->course->teacher_id) {
            abort(403, 'Bạn không có quyền sửa câu hỏi này.');
        }

        $question->update([
            'course_id' => $request->course_id,
            'difficulty' => $request->difficulty,
            'question_text' => $request->question_text,
        ]);

        // Cập nhật các đáp án
        $options = $question->options()->orderBy('id', 'asc')->get();
        $index = 1;
        foreach ($request->options as $optionText) {
            if (isset($options[$index - 1])) {
                $options[$index - 1]->update([
                    'option_text' => $optionText,
                    'is_correct' => $index == $request->correct_option ? true : false,
                ]);
            }
            $index++;
        }

        return back()->with('success', 'Đã cập nhật câu hỏi thành công!');
    }

    // ==========================================
    // 4. XÓA CÂU HỎI KHỎI NGÂN HÀNG
    // ==========================================
    public function destroyBank($id)
    {
        $question = Question::findOrFail($id);

        if (auth()->user()->role !== 'admin' && auth()->id() !== $question->course->teacher_id) {
            abort(403, 'Bạn không có quyền xóa câu hỏi này.');
        }

        $question->delete();

        return back()->with('success', 'Đã xóa câu hỏi khỏi Ngân hàng!');
    }
    // ==========================================
    // 5. IMPORT CÂU HỎI TỪ FILE EXCEL
    // ==========================================
    public function importBank(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'file' => 'required|file|max:5120', // Tạm bỏ "mimes" để chống lỗi chặn file Excel ẩn
        ]);

        try {
            // Khởi tạo Import Class
            $import = new \App\Imports\QuestionImport($request->course_id);

            // Chạy Import
            Excel::import($import, $request->file('file'));

            return back()->with('success', "Thành công! Đã thêm {$import->importedCount} câu hỏi vào Ngân hàng.");
        } catch (\Exception $e) {
            // Nếu có lỗi hệ thống, báo lỗi đỏ ra màn hình để ta biết đường sửa
            return back()->with('error', 'Lỗi khi đọc file: ' . $e->getMessage());
        }
    }
    // ==========================================
    // 6. GIAO DIỆN AI SINH CÂU HỎI
    // ==========================================
    public function aiGenerateView()
    {
        $user = auth()->user();
        $courses = $user->role === 'admin' ? Course::all() : Course::where('teacher_id', $user->id)->get();

        return view('quizzes.ai_generate', compact('courses'));
    }
    // ==========================================
    // 7. LOGIC AI TRUY XUẤT VÀ SOẠN ĐỀ (AJAX)
    // ==========================================
    public function generateQuestions(Request $request)
    {
        $request->validate([
            'course_id' => 'required',
            'topic' => 'required|string',
            'difficulty' => 'required',
            'quantity' => 'required|integer|max:20',
        ]);

        // 1. Lấy Vector câu hỏi từ Gemini (Sử dụng API Key Gemini)
        $apiKeyGemini = env('GOOGLE_API_KEY'); // Lấy trực tiếp từ env giống Service

        if (empty($apiKeyGemini)) {
            return response()->json(['error' => 'Chưa cấu hình API Key của Gemini trong hệ thống.'], 500);
        }

        // BÊ Y NGUYÊN CẤU HÌNH TỪ DocumentProcessingService SANG ĐÂY
        $responseGemini = Http::timeout(30)
            ->withoutVerifying() // Bỏ qua check SSL trên localhost
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent?key={$apiKeyGemini}", [
                'model' => 'models/gemini-embedding-001',
                'content' => [
                    'parts' => [['text' => $request->topic]],
                ],
            ]);

        // BẮT LỖI TỪ GOOGLE TRẢ VỀ
        if ($responseGemini->failed() || !isset($responseGemini->json()['embedding'])) {
            $errorData = $responseGemini->json();
            $errorMsg = $errorData['error']['message'] ?? 'Lỗi không xác định từ Gemini API';
            \Illuminate\Support\Facades\Log::error('Gemini API Error: ', $errorData);
            return response()->json(['error' => 'Lỗi tạo Vector (Gemini): ' . $errorMsg], 500);
        }

        $queryVector = $responseGemini->json()['embedding']['values']; // Gemini trả về 768 chiều
        $queryVectorStr = '[' . implode(',', $queryVector) . ']';

        // 2. Tìm kiếm nội dung liên quan nhất trong PostgreSQL
        // Chúng ta lấy khoảng 10 đoạn liên quan để AI có đủ dữ liệu soạn bài
        $contextChunks = DB::connection('pgsql')
            ->table('document_chunks')
            ->select('content')
            ->where('course_id', $request->course_id)
            ->orderByRaw('embedding <=> ?::vector', [$queryVectorStr])
            ->limit(10)
            ->get();

        if ($contextChunks->isEmpty()) {
            return response()->json(['error' => 'Không tìm thấy tài liệu huấn luyện cho khóa học này. Thầy hãy upload tài liệu trước nhé!'], 404);
        }

        $contextText = $contextChunks->pluck('content')->implode("\n");

        // 3. Gửi cho DeepSeek để soạn câu hỏi dưới dạng JSON
        $prompt = "Dựa trên nội dung bài giảng sau:
        ---
        {$contextText}
        ---
        Hãy tạo {$request->quantity} câu hỏi trắc nghiệm về chủ đề: '{$request->topic}', độ khó: {$request->difficulty}.
        
        YÊU CẦU BẮT BUỘC:
        1. Chỉ trả về dữ liệu định dạng JSON Array, không thêm văn bản dẫn chuyện.
        2. Mỗi đối tượng trong mảng phải có cấu trúc:
        {
            'question': 'nội dung câu hỏi',
            'options': ['đáp án A', 'đáp án B', 'đáp án C', 'đáp án D'],
            'correct_index': 0, // số từ 0 đến 3 tương ứng với vị trí đáp án đúng
            'explanation': 'giải thích ngắn gọn'
        }";

        $responseDeepSeek = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.deepseek.key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.deepseek.com/v1/chat/completions', [
            'model' => 'deepseek-chat',
            'messages' => [['role' => 'system', 'content' => 'You are a professional teacher assistant that outputs only JSON.'], ['role' => 'user', 'content' => $prompt]],
            'response_format' => ['type' => 'json_object'], // Ép DeepSeek trả về JSON
        ]);

        $aiResult = $responseDeepSeek->json()['choices'][0]['message']['content'];

        // Trả kết quả về cho giao diện xử lý tiếp
        return response()->json(json_decode($aiResult, true));
    }

    // ==========================================
    // 8. LƯU CÂU HỎI ĐÃ CHỌN VÀO MYSQL
    // ==========================================
    public function saveGeneratedQuestions(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'difficulty' => 'required',
            'questions' => 'required|array',
        ]);

        // Chuyển đổi nhãn độ khó để khớp với DB (easy, medium, hard)
        $difficultyMap = ['Dễ' => 'easy', 'Trung bình' => 'medium', 'Khó' => 'hard'];
        $dbDifficulty = $difficultyMap[$request->difficulty] ?? 'medium';

        foreach ($request->questions as $q) {
            $question = Question::create([
                'course_id' => $request->course_id,
                'difficulty' => $dbDifficulty,
                'question_text' => $q['question'],
            ]);

            foreach ($q['options'] as $index => $optionText) {
                Option::create([
                    'question_id' => $question->id,
                    'option_text' => $optionText,
                    'is_correct' => $index == $q['correct_index'],
                ]);
            }
        }

        return response()->json(['success' => 'Đã lưu ' . count($request->questions) . ' câu hỏi vào ngân hàng!']);
    }
}
