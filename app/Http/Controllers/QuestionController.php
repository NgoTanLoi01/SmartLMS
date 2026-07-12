<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateQuizQuestions;
use App\Models\AiOperation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Question;
use App\Models\Option;
use App\Models\Course;
use App\Models\QuestionBank;
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
            $courses = Course::with('questionBanks')->get();
            $questionBanks = QuestionBank::with(['teacher', 'courses'])->latest()->get();
            $query = Question::with(['questionBank.teacher', 'questionBank.courses', 'course.teacher', 'options'])
                ->notArchived();
        } else {
            $courses = Course::with('questionBanks')->where('teacher_id', $user->id)->get();
            $courseIds = $courses->pluck('id');
            $questionBanks = QuestionBank::with(['teacher', 'courses'])
                ->where(function ($q) use ($user, $courseIds) {
                    $q->where('teacher_id', $user->id)
                        ->orWhereHas('courses', fn ($courseQuery) => $courseQuery->whereIn('courses.id', $courseIds));
                })
                ->latest()
                ->get();
            $query = Question::with(['questionBank.teacher', 'questionBank.courses', 'course.teacher', 'options'])
                ->notArchived()
                ->whereIn('question_bank_id', $questionBanks->pluck('id'));
        }

        if ($request->filled('question_bank_id')) {
            $query->where('question_bank_id', $request->question_bank_id);
        }

        if ($request->has('course_id') && $request->course_id != '') {
            $query->where(function ($q) use ($request) {
                $q->whereHas('questionBank.courses', fn ($courseQuery) => $courseQuery->where('courses.id', $request->course_id))
                    ->orWhere('course_id', $request->course_id);
            });
        }

        // Lấy danh sách câu hỏi (có phân trang)
        $questions = $query->orderBy('created_at', 'desc')->paginate(15);

        // Giữ lại query string khi chuyển trang
        $questions->appends($request->all());

        return view('quizzes.question_bank', compact('courses', 'questionBanks', 'questions'));
    }

    public function storeQuestionBank(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
        ]);

        $user = auth()->user();
        $courseIds = $this->authorizedCourseIds($request->input('course_ids', []));

        $bank = QuestionBank::create([
            'name' => $request->name,
            'description' => $request->description,
            'teacher_id' => $user->role === 'admin' ? null : $user->id,
        ]);

        if (!empty($courseIds)) {
            $bank->courses()->syncWithoutDetaching($courseIds);
        }

        return back()->with('success', 'Đã tạo ngân hàng câu hỏi dùng chung.');
    }

    public function attachQuestionBank(Request $request)
    {
        $request->validate([
            'question_bank_id' => 'required|exists:question_banks,id',
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => 'exists:courses,id',
        ]);

        $bank = QuestionBank::findOrFail($request->question_bank_id);
        $this->authorizeQuestionBank($bank);

        $bank->courses()->syncWithoutDetaching($this->authorizedCourseIds($request->course_ids));

        return back()->with('success', 'Đã gắn ngân hàng câu hỏi với khóa học.');
    }

    // ==========================================
    // 2. THÊM CÂU HỎI VÀO NGÂN HÀNG
    // ==========================================
    public function storeBank(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'question_bank_id' => 'nullable|exists:question_banks,id',
            'difficulty' => 'required|in:easy,medium,hard',
            'question_text' => 'required|string',
            'options' => 'required|array|size:4',
            'correct_option' => 'required|in:1,2,3,4',
        ]);

        $bank = $request->filled('question_bank_id')
            ? QuestionBank::findOrFail($request->question_bank_id)
            : $this->defaultQuestionBankForCourse((int) $request->course_id);
        $this->authorizeCourse(Course::findOrFail($request->course_id));
        $this->authorizeQuestionBank($bank);
        $bank->courses()->syncWithoutDetaching([(int) $request->course_id]);

        $question = Question::create([
            'course_id' => $request->course_id,
            'question_bank_id' => $bank->id,
            'difficulty' => $request->difficulty,
            'question_text' => $request->question_text,
            'status' => Question::STATUS_PUBLISHED,
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
            'question_bank_id' => 'nullable|exists:question_banks,id',
            'difficulty' => 'required|in:easy,medium,hard',
            'question_text' => 'required|string',
            'options' => 'required|array|size:4',
            'correct_option' => 'required|in:1,2,3,4',
        ]);

        $question = Question::findOrFail($id);

        $this->authorizeQuestionAccess($question);

        $bank = $request->filled('question_bank_id')
            ? QuestionBank::findOrFail($request->question_bank_id)
            : $this->defaultQuestionBankForCourse((int) $request->course_id);
        $this->authorizeCourse(Course::findOrFail($request->course_id));
        $this->authorizeQuestionBank($bank);
        $bank->courses()->syncWithoutDetaching([(int) $request->course_id]);

        $question->update([
            'course_id' => $request->course_id,
            'question_bank_id' => $bank->id,
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

        $this->authorizeQuestionAccess($question);

        $question->update(['status' => Question::STATUS_ARCHIVED]);

        return back()->with('success', 'Đã lưu trữ câu hỏi. Đáp án và dữ liệu liên quan vẫn được giữ lại!');
    }
    // ==========================================
    // 5. IMPORT CÂU HỎI TỪ FILE EXCEL
    // ==========================================
    public function importBank(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'question_bank_id' => 'nullable|exists:question_banks,id',
            'file' => 'required|file|max:5120', // Tạm bỏ "mimes" để chống lỗi chặn file Excel ẩn
        ]);

        try {
            // Khởi tạo Import Class
            $bank = $request->filled('question_bank_id')
                ? QuestionBank::findOrFail($request->question_bank_id)
                : $this->defaultQuestionBankForCourse((int) $request->course_id);
            $this->authorizeCourse(Course::findOrFail($request->course_id));
            $this->authorizeQuestionBank($bank);
            $bank->courses()->syncWithoutDetaching([(int) $request->course_id]);

            $import = new \App\Imports\QuestionImport($request->course_id, $bank->id);

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
        $courses = $user->role === 'admin'
            ? Course::with(['modules.lessons'])->get()
            : Course::with(['modules.lessons'])->where('teacher_id', $user->id)->get();

        $courseContextOptions = $courses->mapWithKeys(function ($course) {
            return [
                $course->id => [
                    'title' => $course->title,
                    'modules' => $course->modules->map(fn ($module) => [
                        'id' => $module->id,
                        'title' => $module->title,
                        'lessons' => $module->lessons->map(fn ($lesson) => [
                            'id' => $lesson->id,
                            'title' => $lesson->title,
                        ])->values(),
                    ])->values(),
                ],
            ];
        });

        return view('quizzes.ai_generate', compact('courses', 'courseContextOptions'));
    }
    // ==========================================
    // 7. LOGIC AI TRUY XUẤT VÀ SOẠN ĐỀ (AJAX)
    // ==========================================
    public function generateQuestions(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'source_type' => 'required|in:course_content,document,topic',
            'content_scope' => 'nullable|in:course,module,lesson',
            'module_id' => 'nullable|integer',
            'lesson_id' => 'nullable|integer',
            'topic' => 'nullable|string|max:255',
            'difficulty' => 'required|string',
            'quantity' => 'required|integer|min:1|max:20',
        ]);

        $course = Course::with(['modules.lessons'])->findOrFail($request->course_id);
        $this->authorizeCourse($course);

        if ($request->source_type === 'topic' && !$request->filled('topic')) {
            return response()->json(['error' => 'Thầy / Cô vui lòng nhập chủ đề để AI tạo câu hỏi.'], 422);
        }

        $contextResult = $this->quizAiContext($request, $course);
        if (empty($contextResult['text'])) {
            return response()->json(['error' => $contextResult['error'] ?? 'Không tìm thấy nội dung phù hợp để AI tạo câu hỏi.'], 404);
        }

        $contextText = $contextResult['text'];
        $sourceLabel = $contextResult['label'];
        $topic = trim((string) $request->topic);
        $topicInstruction = $topic !== ''
            ? "Tập trung vào chủ đề: '{$topic}'."
            : 'Tự chọn các ý quan trọng nhất từ nguồn nội dung đã cung cấp.';

        // 3. Gửi cho DeepSeek để soạn câu hỏi dưới dạng JSON
        $prompt = "Dựa trên nguồn nội dung: {$sourceLabel}
        ---
        {$contextText}
        ---
        Hãy tạo {$request->quantity} câu hỏi trắc nghiệm, độ khó: {$request->difficulty}.
        {$topicInstruction}
        
        YÊU CẦU BẮT BUỘC:
        1. Ngôn ngữ: Tiếng Việt.
        2. Chỉ tạo câu hỏi dựa trên nội dung được cung cấp, không bịa kiến thức ngoài.
        3. Trả về ĐÚNG cấu trúc JSON:
        {
            \"questions\": [
                {
                    \"question\": \"nội dung câu hỏi\",
                    \"options\": [\"đáp án A\", \"đáp án B\", \"đáp án C\", \"đáp án D\"],
                    \"correct_index\": 0,
                    \"explanation\": \"giải thích ngắn gọn\"
                }
            ]
        }
        4. Mỗi câu hỏi phải có đúng 4 đáp án, correct_index là số từ 0 đến 3.
        5. Không thêm markdown, không thêm chữ giải thích ngoài JSON.";

        $operation = AiOperation::create([
            'user_id' => $request->user()->id, 'feature' => 'quiz_generation', 'provider' => 'deepseek',
            'model' => config('services.deepseek.model', 'deepseek-v4-flash'), 'status' => AiOperation::STATUS_QUEUED,
            'subject_type' => Course::class, 'subject_id' => $course->id,
            'metadata' => ['quantity' => (int) $request->quantity, 'difficulty' => $request->difficulty, 'source_label' => $sourceLabel],
        ]);
        GenerateQuizQuestions::dispatch($operation->id, $prompt, $sourceLabel)->afterCommit();

        return response()->json([
            'success' => true, 'queued' => true, 'operation_id' => $operation->uuid,
            'status_url' => route('ai-operations.show', $operation->uuid),
        ], 202);
    }

    private function quizAiContext(Request $request, Course $course): array
    {
        return match ($request->source_type) {
            'document' => $this->quizAiDocumentContext($request, $course),
            'topic' => [
                'text' => $this->sanitizeContextText($request->topic),
                'label' => 'Chủ đề nhập tay',
            ],
            default => $this->quizAiCourseContentContext($request, $course),
        };
    }

    private function quizAiCourseContentContext(Request $request, Course $course): array
    {
        $scope = $request->input('content_scope', 'course');
        $parts = [
            "Khóa học: {$course->title}",
            'Mô tả khóa học: ' . $this->sanitizeContextText($course->description ?: 'Chưa có mô tả.'),
        ];
        $sourceLabel = 'Nội dung toàn khóa học';

        $modules = $course->modules;

        if ($scope === 'module') {
            $modules = $modules->where('id', (int) $request->module_id);
            $sourceLabel = 'Nội dung một chương trong khóa học';
        }

        if ($scope === 'lesson') {
            $selectedLessonId = (int) $request->lesson_id;
            $foundLesson = null;

            foreach ($course->modules as $module) {
                $lesson = $module->lessons->firstWhere('id', $selectedLessonId);
                if ($lesson) {
                    $foundLesson = [$module, $lesson];
                    break;
                }
            }

            if (!$foundLesson) {
                return ['text' => '', 'error' => 'Không tìm thấy bài học đã chọn trong khóa học này.'];
            }

            [$module, $lesson] = $foundLesson;
            $parts[] = "Chương: {$module->title}";
            $parts[] = "Bài học: {$lesson->title}";
            $parts[] = $this->sanitizeContextText($lesson->content);
            $sourceLabel = 'Nội dung một bài học trong khóa học';

            return [
                'text' => Str::limit(implode("\n\n", array_filter($parts)), 18000, ''),
                'label' => $sourceLabel,
            ];
        }

        if ($modules->isEmpty()) {
            return ['text' => '', 'error' => 'Khóa học chưa có chương hoặc bài học để AI tạo câu hỏi.'];
        }

        foreach ($modules as $module) {
            $parts[] = "Chương: {$module->title}";

            foreach ($module->lessons as $lesson) {
                $content = $this->sanitizeContextText($lesson->content);
                if ($content === '') {
                    continue;
                }

                $parts[] = "Bài học: {$lesson->title}\n{$content}";
            }
        }

        $context = trim(implode("\n\n", array_filter($parts)));

        return [
            'text' => Str::limit($context, 18000, ''),
            'label' => $sourceLabel,
            'error' => $context === '' ? 'Khóa học chưa có nội dung bài học để AI tạo câu hỏi.' : null,
        ];
    }

    private function quizAiDocumentContext(Request $request, Course $course): array
    {
        // 1. Lấy Vector câu hỏi từ Gemini (Sử dụng API Key Gemini)
        $apiKeyGemini = env('GOOGLE_API_KEY');

        if (empty($apiKeyGemini)) {
            return ['text' => '', 'error' => 'Chưa cấu hình API Key của Gemini trong hệ thống.'];
        }

        $searchTopic = trim((string) $request->topic) ?: $course->title;

        $responseGemini = Http::timeout(30)
            ->withoutVerifying()
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent?key={$apiKeyGemini}", [
                'model' => 'models/gemini-embedding-001',
                'content' => [
                    'parts' => [['text' => $searchTopic]],
                ],
            ]);

        if ($responseGemini->failed() || !isset($responseGemini->json()['embedding'])) {
            $errorData = $responseGemini->json();
            $errorMsg = $errorData['error']['message'] ?? 'Lỗi không xác định từ Gemini API';
            \Illuminate\Support\Facades\Log::error('Gemini API Error: ', $errorData);

            return ['text' => '', 'error' => 'Lỗi tạo Vector (Gemini): ' . $errorMsg];
        }

        $queryVector = $responseGemini->json()['embedding']['values'];
        $queryVectorStr = '[' . implode(',', $queryVector) . ']';

        $contextChunks = DB::connection('pgsql')
            ->table('document_chunks')
            ->select('content')
            ->where('course_id', $course->id)
            ->orderByRaw('embedding <=> ?::vector', [$queryVectorStr])
            ->limit(10)
            ->get();

        if ($contextChunks->isEmpty()) {
            return ['text' => '', 'error' => 'Không tìm thấy tài liệu huấn luyện cho khóa học này. Thầy / Cô hãy upload tài liệu trước nhé!'];
        }

        return [
            'text' => Str::limit($contextChunks->pluck('content')->implode("\n"), 18000, ''),
            'label' => 'Tài liệu upload của khóa học',
        ];
    }

    private function sanitizeContextText(?string $text): string
    {
        $text = strip_tags((string) $text);
        $text = html_entity_decode($text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    // ==========================================
    // 8. LƯU CÂU HỎI ĐÃ CHỌN VÀO MYSQL
    // ==========================================
    public function saveGeneratedQuestions(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'question_bank_id' => 'nullable|exists:question_banks,id',
            'difficulty' => 'required',
            'questions' => 'required|array',
        ]);

        // Chuyển đổi nhãn độ khó để khớp với DB (easy, medium, hard)
        $difficultyMap = ['Dễ' => 'easy', 'Trung bình' => 'medium', 'Khó' => 'hard'];
        $dbDifficulty = $difficultyMap[$request->difficulty] ?? 'medium';

        $bank = $request->filled('question_bank_id')
            ? QuestionBank::findOrFail($request->question_bank_id)
            : $this->defaultQuestionBankForCourse((int) $request->course_id);
        $this->authorizeCourse(Course::findOrFail($request->course_id));
        $this->authorizeQuestionBank($bank);
        $bank->courses()->syncWithoutDetaching([(int) $request->course_id]);

        foreach ($request->questions as $q) {
            $question = Question::create([
                'course_id' => $request->course_id,
                'question_bank_id' => $bank->id,
                'difficulty' => $dbDifficulty,
                'question_text' => $q['question'],
                'status' => Question::STATUS_PUBLISHED,
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

    private function defaultQuestionBankForCourse(int $courseId): QuestionBank
    {
        $course = Course::with('questionBanks')->findOrFail($courseId);
        $this->authorizeCourse($course);

        if ($course->questionBanks->isNotEmpty()) {
            return $course->questionBanks->first();
        }

        $bank = QuestionBank::create([
            'name' => $course->title,
            'description' => 'Ngân hàng câu hỏi dùng chung cho ' . $course->title,
            'teacher_id' => $course->teacher_id,
        ]);

        $bank->courses()->syncWithoutDetaching([$course->id]);

        return $bank;
    }

    private function authorizeQuestionBank(QuestionBank $bank): void
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return;
        }

        $teacherOwnsBank = $bank->teacher_id === $user->id;
        $teacherOwnsLinkedCourse = $bank->courses()->where('courses.teacher_id', $user->id)->exists();

        if (!$teacherOwnsBank && !$teacherOwnsLinkedCourse) {
            abort(403, 'Bạn không có quyền sử dụng ngân hàng câu hỏi này.');
        }
    }

    private function authorizeQuestionAccess(Question $question): void
    {
        if (auth()->user()->role === 'admin') {
            return;
        }

        if ($question->questionBank) {
            $this->authorizeQuestionBank($question->questionBank);
            return;
        }

        if (!$question->course || $question->course->teacher_id !== auth()->id()) {
            abort(403, 'Bạn không có quyền thao tác câu hỏi này.');
        }
    }

    private function authorizeCourse(Course $course): void
    {
        if (auth()->user()->role !== 'admin' && $course->teacher_id !== auth()->id()) {
            abort(403, 'Bạn không có quyền sử dụng khóa học này.');
        }
    }

    private function authorizedCourseIds(array $courseIds): array
    {
        $ids = collect($courseIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if (auth()->user()->role === 'admin') {
            return $ids->all();
        }

        return Course::where('teacher_id', auth()->id())
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();
    }
}
