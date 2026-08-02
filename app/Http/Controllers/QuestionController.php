<?php

namespace App\Http\Controllers;

use App\Imports\QuestionImport;
use App\Jobs\GenerateQuizQuestions;
use App\Models\AiOperation;
use App\Models\Course;
use App\Models\Option;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuizPassage;
use App\Rules\SafeSpreadsheet;
use App\Services\AiResponseValidator;
use App\Services\GeminiEmbeddingService;
use App\Services\QuestionAiQualityService;
use App\Services\QuestionDefinitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class QuestionController extends Controller
{
    public function __construct(
        private GeminiEmbeddingService $embeddingService,
        private AiResponseValidator $responseValidator,
        private QuestionDefinitionService $questionDefinitionService,
        private QuestionAiQualityService $questionAiQualityService,
    ) {}

    // ==========================================
    // 1. HIỂN THỊ GIAO DIỆN NGÂN HÀNG CÂU HỎI
    // ==========================================
    public function index(Request $request)
    {
        $user = auth()->user();
        $status = $request->validate([
            'status' => ['nullable', 'in:active,archived,all'],
        ])['status'] ?? 'active';

        // Admin thấy tất cả khóa học, Giáo viên chỉ thấy khóa mình dạy
        if ($user->role === 'admin') {
            $courses = Course::with('questionBanks')->get();
            $questionBanks = QuestionBank::with(['teacher', 'courses'])->latest()->get();
            $query = Question::with(['questionBank.teacher', 'questionBank.courses', 'course.teacher', 'options', 'passage']);
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
            $query = Question::with(['questionBank.teacher', 'questionBank.courses', 'course.teacher', 'options', 'passage'])
                ->whereIn('question_bank_id', $questionBanks->pluck('id'));
        }

        if ($status === 'archived') {
            $query->where('status', Question::STATUS_ARCHIVED);
        } elseif ($status === 'active') {
            $query->notArchived();
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

        if ($request->filled('question_type')) {
            $query->where('question_type', $request->question_type);
        }

        $questionStats = (clone $query)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN difficulty = 'easy' THEN 1 ELSE 0 END) AS easy")
            ->selectRaw("SUM(CASE WHEN difficulty = 'medium' THEN 1 ELSE 0 END) AS medium")
            ->selectRaw("SUM(CASE WHEN difficulty = 'hard' THEN 1 ELSE 0 END) AS hard")
            ->first();

        // Lấy danh sách câu hỏi (có phân trang)
        $questions = $query->orderBy('created_at', 'desc')->paginate(15);

        // Giữ lại query string khi chuyển trang
        $questions->appends($request->all());

        $passages = QuizPassage::query()
            ->with('course:id,title')
            ->withCount('questions')
            ->whereIn('course_id', $courses->pluck('id'))
            ->orderBy('title')
            ->get();

        $questionTypeLabels = Question::typeLabels();

        return view('quizzes.question_bank', compact(
            'courses',
            'questionBanks',
            'questions',
            'passages',
            'questionTypeLabels',
            'questionStats'
        ));
    }

    public function storePassage(Request $request)
    {
        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:50000'],
            'source_label' => ['nullable', 'string', 'max:255'],
        ]);
        $this->authorizeCourse(Course::findOrFail($data['course_id']));
        QuizPassage::create($data);

        return back()->with('success', 'Đã tạo ngữ liệu dùng chung cho nhóm câu hỏi.');
    }

    public function destroyPassage(QuizPassage $passage)
    {
        $this->authorizeCourse($passage->course);
        DB::transaction(function () use ($passage) {
            $passage->questions()->update(['quiz_passage_id' => null]);
            $passage->delete();
        });

        return back()->with('success', 'Đã xóa ngữ liệu và gỡ liên kết khỏi các câu hỏi.');
    }

    public function storeQuestionBank(Request $request)
    {
        Gate::authorize('create', QuestionBank::class);

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

        if (! empty($courseIds)) {
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
            'quiz_passage_id' => 'nullable|exists:quiz_passages,id',
            'difficulty' => 'required|in:easy,medium,hard',
            'question_text' => 'required|string|max:10000',
        ]);
        $definition = $this->questionDefinitionService->validate($request);

        $bank = $request->filled('question_bank_id')
            ? QuestionBank::findOrFail($request->question_bank_id)
            : $this->defaultQuestionBankForCourse((int) $request->course_id);
        $this->authorizeCourse(Course::findOrFail($request->course_id));
        $this->authorizeQuestionBank($bank);
        $bank->courses()->syncWithoutDetaching([(int) $request->course_id]);
        $passageId = $this->validatedPassageId($request);

        DB::transaction(function () use ($request, $bank, $passageId, $definition) {
            $question = Question::create([
                'course_id' => $request->course_id,
                'question_bank_id' => $bank->id,
                'quiz_passage_id' => $passageId,
                'question_type' => $definition['question_type'],
                'difficulty' => $request->difficulty,
                'question_text' => $request->question_text,
                'answer_config' => $definition['answer_config'],
                'status' => Question::STATUS_PUBLISHED,
            ]);
            $this->questionDefinitionService->syncOptions($question, $definition);
        });

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
            'quiz_passage_id' => 'nullable|exists:quiz_passages,id',
            'difficulty' => 'required|in:easy,medium,hard',
            'question_text' => 'required|string|max:10000',
        ]);
        $definition = $this->questionDefinitionService->validate($request);

        $question = Question::findOrFail($id);

        $this->authorizeQuestionAccess($question);

        $bank = $request->filled('question_bank_id')
            ? QuestionBank::findOrFail($request->question_bank_id)
            : $this->defaultQuestionBankForCourse((int) $request->course_id);
        $this->authorizeCourse(Course::findOrFail($request->course_id));
        $this->authorizeQuestionBank($bank);
        $bank->courses()->syncWithoutDetaching([(int) $request->course_id]);
        $passageId = $this->validatedPassageId($request);

        DB::transaction(function () use ($question, $request, $bank, $passageId, $definition) {
            $question->update([
                'course_id' => $request->course_id,
                'question_bank_id' => $bank->id,
                'quiz_passage_id' => $passageId,
                'question_type' => $definition['question_type'],
                'difficulty' => $request->difficulty,
                'question_text' => $request->question_text,
                'answer_config' => $definition['answer_config'],
            ]);
            $this->questionDefinitionService->syncOptions($question, $definition);
        });

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

    public function bulkDestroyBank(Request $request)
    {
        $data = $request->validate([
            'question_ids' => ['required', 'array', 'min:1', 'max:200'],
            'question_ids.*' => ['required', 'integer', 'distinct', 'exists:questions,id'],
        ]);

        $ids = collect($data['question_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $questions = Question::query()->whereKey($ids)->get();
        abort_unless($questions->count() === $ids->count(), 422, 'Danh sách câu hỏi không hợp lệ.');

        $questions->each(fn (Question $question) => $this->authorizeQuestionAccess($question));

        DB::transaction(function () use ($questions) {
            Question::query()
                ->whereKey($questions->modelKeys())
                ->update(['status' => Question::STATUS_ARCHIVED, 'updated_at' => now()]);
        });

        return back()->with('success', 'Đã lưu trữ '.$questions->count().' câu hỏi. Các đề đã phát và dữ liệu bài làm không bị thay đổi.');
    }

    public function restoreBank($id)
    {
        $question = Question::findOrFail($id);

        $this->authorizeQuestionAccess($question);

        if ($question->status !== Question::STATUS_ARCHIVED) {
            return back()->with('info', 'Câu hỏi này đang được sử dụng, không cần khôi phục.');
        }

        $question->update(['status' => Question::STATUS_PUBLISHED]);

        return back()->with('success', 'Đã khôi phục câu hỏi vào Ngân hàng câu hỏi.');
    }

    // ==========================================
    // 5. IMPORT CÂU HỎI TỪ FILE EXCEL
    // ==========================================
    public function importBank(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'question_bank_id' => 'nullable|exists:question_banks,id',
            'file' => ['required', 'file', 'max:5120', new SafeSpreadsheet],
        ]);

        try {
            // Khởi tạo Import Class
            $bank = $request->filled('question_bank_id')
                ? QuestionBank::findOrFail($request->question_bank_id)
                : $this->defaultQuestionBankForCourse((int) $request->course_id);
            $this->authorizeCourse(Course::findOrFail($request->course_id));
            $this->authorizeQuestionBank($bank);
            $bank->courses()->syncWithoutDetaching([(int) $request->course_id]);

            $import = new QuestionImport($request->course_id, $bank->id);

            // Import nguyên tử: file lỗi giữa chừng sẽ không để lại bộ câu hỏi dở dang.
            DB::transaction(fn () => Excel::import($import, $request->file('file')));

            return back()->with('success', "Thành công! Đã thêm {$import->importedCount} câu hỏi vào Ngân hàng.");
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Không thể đọc file bảng tính. Vui lòng kiểm tra đúng mẫu 7 cột, định dạng .xlsx/.xls/.csv rồi thử lại.');
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
            'difficulty' => 'required|string|in:Dễ,Trung bình,Khó',
            'question_type' => 'required|in:single_choice,multiple_choice,true_false_group,fill_blank,numeric,essay,code_debug',
            'quantity' => 'required|integer|min:1|max:20',
        ]);

        $course = Course::with(['modules.lessons'])->findOrFail($request->course_id);
        $this->authorizeCourse($course);

        if ($request->source_type === 'topic' && ! $request->filled('topic')) {
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
        $questionType = (string) $request->question_type;
        $typeLabel = Question::typeLabels()[$questionType];
        $questionSchema = $this->aiQuestionSchema($questionType);
        $typeInstruction = $this->aiQuestionTypeInstruction($questionType);

        // 3. Gửi cho DeepSeek để soạn câu hỏi dưới dạng JSON
        $prompt = "Dựa trên nguồn nội dung: {$sourceLabel}
        ---
        {$contextText}
        ---
        Hãy tạo {$request->quantity} câu hỏi loại {$typeLabel}, độ khó: {$request->difficulty}.
        {$topicInstruction}
        
        YÊU CẦU BẮT BUỘC:
        1. Ngôn ngữ: Tiếng Việt.
        2. Chỉ tạo câu hỏi dựa trên nội dung được cung cấp, không bịa kiến thức ngoài.
        3. Trả về ĐÚNG cấu trúc JSON, mỗi phần tử questions theo schema:
        {
            \"questions\": [{$questionSchema}]
        }
        4. Luôn đặt question_type là {$questionType}; giải thích phải nêu được vì sao đáp án đúng.
        5. quality_review là mảng cảnh báo tự kiểm định; để [] nếu không phát hiện vấn đề.
        6. {$typeInstruction}
        7. Không thêm markdown, không thêm chữ giải thích ngoài JSON.";

        $operation = AiOperation::create([
            'user_id' => $request->user()->id, 'feature' => 'quiz_generation', 'provider' => 'deepseek',
            'model' => config('services.deepseek.model', 'deepseek-v4-flash'), 'status' => AiOperation::STATUS_QUEUED,
            'subject_type' => Course::class, 'subject_id' => $course->id,
            'metadata' => ['quantity' => (int) $request->quantity, 'difficulty' => $request->difficulty, 'question_type' => $questionType, 'source_label' => $sourceLabel],
        ]);
        GenerateQuizQuestions::dispatch($operation->id, $prompt, $sourceLabel, (int) $request->quantity, $course->id)->afterCommit();

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
            'Mô tả khóa học: '.$this->sanitizeContextText($course->description ?: 'Chưa có mô tả.'),
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

            if (! $foundLesson) {
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
        $searchTopic = trim((string) $request->topic) ?: $course->title;
        try {
            $queryVector = $this->embeddingService->embed($searchTopic);
        } catch (Throwable $e) {
            Log::warning('Gemini quiz context embedding failed', ['error' => $e->getMessage()]);

            return ['text' => '', 'error' => 'Không thể tìm kiếm tài liệu AI lúc này. Vui lòng thử lại sau.'];
        }
        $queryVectorStr = '['.implode(',', $queryVector).']';

        $contextChunks = DB::connection('pgsql')
            ->table('document_chunks')
            ->select('content')
            ->where('course_id', $course->id)
            ->where('is_active', true)
            ->orderByRaw('embedding::halfvec(3072) <=> ?::halfvec(3072)', [$queryVectorStr])
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
            'difficulty' => 'required|string|in:Dễ,Trung bình,Khó,easy,medium,hard',
            'allow_duplicates' => 'nullable|boolean',
            'questions' => 'required|array|min:1|max:20',
        ]);
        $validatedQuestions = $this->validatedAiQuestions($request->questions);

        // Chuyển đổi nhãn độ khó để khớp với DB (easy, medium, hard)
        $difficultyMap = ['Dễ' => 'easy', 'Trung bình' => 'medium', 'Khó' => 'hard'];
        $dbDifficulty = $difficultyMap[$request->difficulty] ?? 'medium';

        $bank = $request->filled('question_bank_id')
            ? QuestionBank::findOrFail($request->question_bank_id)
            : $this->defaultQuestionBankForCourse((int) $request->course_id);
        $this->authorizeCourse(Course::findOrFail($request->course_id));
        $this->authorizeQuestionBank($bank);
        $bank->courses()->syncWithoutDetaching([(int) $request->course_id]);

        $reviewed = $this->questionAiQualityService->reviewBatch((int) $request->course_id, $validatedQuestions);
        $duplicates = collect($reviewed)->filter(fn ($question) => (int) data_get($question, 'quality.duplicate.similarity', 0) >= 92);
        if ($duplicates->isNotEmpty() && ! $request->boolean('allow_duplicates')) {
            return response()->json([
                'message' => 'Có câu hỏi rất giống dữ liệu hiện có. Hãy xem lại trước khi lưu.',
                'needs_confirmation' => true,
                'questions' => $reviewed,
            ], 422);
        }

        DB::transaction(function () use ($validatedQuestions, $request, $bank, $dbDifficulty) {
            foreach ($validatedQuestions as $q) {
                [$answerConfig, $options] = $this->aiQuestionDefinition($q);
                $question = Question::create([
                    'course_id' => $request->course_id,
                    'question_bank_id' => $bank->id,
                    'question_type' => $q['question_type'],
                    'difficulty' => $dbDifficulty,
                    'question_text' => $q['question'],
                    'answer_config' => $answerConfig,
                    'status' => Question::STATUS_PUBLISHED,
                ]);

                foreach ($options as $option) {
                    Option::create([
                        'question_id' => $question->id,
                        'option_text' => $option['text'],
                        'is_correct' => $option['is_correct'],
                    ]);
                }
            }
        });

        return response()->json(['success' => 'Đã lưu '.count($request->questions).' câu hỏi vào ngân hàng!']);
    }

    public function reviewGeneratedQuestions(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'questions' => 'required|array|min:1|max:20',
        ]);
        $course = Course::findOrFail($request->integer('course_id'));
        $this->authorizeCourse($course);

        return response()->json([
            'questions' => $this->questionAiQualityService->reviewBatch(
                $course,
                $this->validatedAiQuestions($request->questions)
            ),
        ]);
    }

    private function validatedAiQuestions(array $questions): array
    {
        try {
            return $this->responseValidator->quizQuestions($questions, count($questions));
        } catch (\UnexpectedValueException $exception) {
            throw ValidationException::withMessages(['questions' => $exception->getMessage()]);
        }
    }

    private function aiQuestionSchema(string $type): string
    {
        return match ($type) {
            Question::TYPE_MULTIPLE_CHOICE => '{\"question_type\":\"multiple_choice\",\"question\":\"Nội dung\",\"options\":[\"A\",\"B\",\"C\",\"D\"],\"correct_indexes\":[0,2],\"explanation\":\"Giải thích\",\"quality_review\":[]}',
            Question::TYPE_TRUE_FALSE_GROUP => '{\"question_type\":\"true_false_group\",\"question\":\"Xác định đúng sai\",\"statements\":[{\"text\":\"Nhận định 1\",\"is_true\":true},{\"text\":\"Nhận định 2\",\"is_true\":false}],\"explanation\":\"Giải thích\",\"quality_review\":[]}',
            Question::TYPE_FILL_BLANK => '{\"question_type\":\"fill_blank\",\"question\":\"Nội dung [[1]] và [[2]]\",\"blanks\":[{\"accepted\":[\"đáp án 1\",\"cách viết khác\"]},{\"accepted\":[\"đáp án 2\"]}],\"case_sensitive\":false,\"explanation\":\"Giải thích\",\"quality_review\":[]}',
            Question::TYPE_NUMERIC => '{\"question_type\":\"numeric\",\"question\":\"Nội dung\",\"numeric_answer\":10,\"numeric_tolerance\":0.1,\"numeric_unit\":\"cm\",\"explanation\":\"Giải thích\",\"quality_review\":[]}',
            Question::TYPE_ESSAY => '{\"question_type\":\"essay\",\"question\":\"Yêu cầu tự luận rõ ràng\",\"max_score\":10,\"word_limit\":500,\"allow_attachments\":false,\"rubric\":[{\"criterion\":\"Nội dung chính xác\",\"max_score\":6},{\"criterion\":\"Lập luận và trình bày\",\"max_score\":4}],\"explanation\":\"Đáp án tham khảo và hướng dẫn chấm\",\"quality_review\":[]}',
            Question::TYPE_CODE_DEBUG => '{\"question_type\":\"code_debug\",\"question\":\"Mô tả lỗi HTML/CSS cần sửa và kết quả mong đợi\",\"max_score\":10,\"starter_code\":\"<!doctype html><html><head><style>.box { color red; }</style></head><body><div class=\\\"box\\\">Nội dung</div></body></html>\",\"explanation_mode\":\"required\",\"explanation_word_limit\":150,\"rubric\":[{\"criterion\":\"Mã sửa đúng và hiển thị đúng\",\"max_score\":7},{\"criterion\":\"Giải thích đúng nguyên nhân\",\"max_score\":3}],\"explanation\":\"Mã đã sửa và nguyên nhân lỗi\",\"quality_review\":[]}',
            default => '{\"question_type\":\"single_choice\",\"question\":\"Nội dung\",\"options\":[\"A\",\"B\",\"C\",\"D\"],\"correct_indexes\":[0],\"explanation\":\"Giải thích\",\"quality_review\":[]}',
        };
    }

    private function aiQuestionTypeInstruction(string $type): string
    {
        return match ($type) {
            Question::TYPE_ESSAY => 'Câu tự luận phải có yêu cầu cụ thể, rubric đo được; tổng max_score của rubric phải bằng max_score câu hỏi. explanation là đáp án tham khảo và hướng dẫn chấm.',
            Question::TYPE_CODE_DEBUG => 'Chỉ tạo bài sửa lỗi HTML/CSS, tuyệt đối không dùng JavaScript. starter_code phải có lỗi thật và đủ ngữ cảnh; tổng max_score của rubric phải bằng max_score. explanation phải nêu mã sửa đúng và nguyên nhân lỗi.',
            default => 'Câu hỏi và đáp án phải rõ ràng, duy nhất và phù hợp với độ khó đã chọn.',
        };
    }

    private function aiQuestionDefinition(array $question): array
    {
        return match ($question['question_type']) {
            Question::TYPE_SINGLE_CHOICE, Question::TYPE_MULTIPLE_CHOICE => [
                $question['question_type'] === Question::TYPE_MULTIPLE_CHOICE ? ['grading' => 'all_or_nothing'] : null,
                collect($question['options'])->map(fn ($text, $index) => [
                    'text' => $text,
                    'is_correct' => in_array($index, $question['correct_indexes'], true),
                ])->all(),
            ],
            Question::TYPE_TRUE_FALSE_GROUP => [
                ['grading' => 'all_or_nothing'],
                collect($question['statements'])->map(fn ($statement) => [
                    'text' => $statement['text'],
                    'is_correct' => $statement['is_true'],
                ])->all(),
            ],
            Question::TYPE_FILL_BLANK => [[
                'blanks' => $question['blanks'],
                'case_sensitive' => $question['case_sensitive'],
            ], []],
            Question::TYPE_NUMERIC => [[
                'target' => $question['numeric_answer'],
                'tolerance' => $question['numeric_tolerance'],
                'unit' => $question['numeric_unit'],
            ], []],
            Question::TYPE_ESSAY => [[
                'grading_mode' => 'manual',
                'max_score' => $question['max_score'],
                'word_limit' => $question['word_limit'],
                'allow_attachments' => $question['allow_attachments'],
                'allowed_extensions' => ['pdf', 'doc', 'docx', 'txt', 'png', 'jpg', 'jpeg'],
                'max_files' => 3,
                'max_file_size_kb' => 10240,
                'rubric' => $question['rubric'],
            ], []],
            Question::TYPE_CODE_DEBUG => [[
                'grading_mode' => 'manual',
                'max_score' => $question['max_score'],
                'language' => 'html_css',
                'starter_code' => $question['starter_code'],
                'explanation_mode' => $question['explanation_mode'],
                'explanation_word_limit' => $question['explanation_word_limit'],
                'rubric' => $question['rubric'],
            ], []],
        };
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
            'description' => 'Ngân hàng câu hỏi dùng chung cho '.$course->title,
            'teacher_id' => $course->teacher_id,
        ]);

        $bank->courses()->syncWithoutDetaching([$course->id]);

        return $bank;
    }

    private function authorizeQuestionBank(QuestionBank $bank): void
    {
        Gate::authorize('update', $bank);
    }

    private function validatedPassageId(Request $request): ?int
    {
        if (! $request->filled('quiz_passage_id')) {
            return null;
        }

        $passage = QuizPassage::findOrFail($request->integer('quiz_passage_id'));
        abort_unless((int) $passage->course_id === (int) $request->course_id, 422, 'Ngữ liệu không thuộc khóa học đã chọn.');
        $this->authorizeCourse($passage->course);

        return $passage->id;
    }

    private function authorizeQuestionAccess(Question $question): void
    {
        Gate::authorize('update', $question);
    }

    private function authorizeCourse(Course $course): void
    {
        Gate::authorize('manageContent', $course);
    }

    private function authorizedCourseIds(array $courseIds): array
    {
        $ids = collect($courseIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        $courses = Course::whereIn('id', $ids)->get()->keyBy('id');
        abort_unless($courses->count() === $ids->count(), 422, 'Danh sách khóa học không hợp lệ.');
        $ids->each(fn ($id) => Gate::authorize('manageContent', $courses->get($id)));

        return $ids->all();
    }
}
