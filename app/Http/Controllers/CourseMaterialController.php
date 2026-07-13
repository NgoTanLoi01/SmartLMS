<?php

namespace App\Http\Controllers;

use App\Jobs\SyncLegacyLearningMaterials;
use App\Models\AiOperation;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialAssignment;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\NotificationCenter;

class CourseMaterialController extends Controller
{
    public function library(Request $request)
    {
        $user = auth()->user();
        $query = Course::with(['teacher', 'classes'])->notArchived()->orderBy('title');

        if ($user->role === 'teacher') {
            $query->where('teacher_id', $user->id);
        } elseif ($user->role === 'student') {
            $classIds = $user->classes()
                ->where('classes.status', Classroom::STATUS_ACTIVE)
                ->pluck('classes.id');

            $query->visibleToStudents()
                ->whereHas('classes', fn ($q) => $q->whereIn('classes.id', $classIds));
        }

        $courses = $query
            ->withCount([
                'materialAssignments as materials_count' => fn ($q) => $q->notArchived(),
            ])
            ->get();

        $materials = null;
        $syncOperations = collect();
        if (!$user->isStudent()) {
            $materials = LearningMaterial::with(['uploader', 'sources.course'])
                ->withCount('sources')
                ->notArchived()
                ->accessibleTo($user)
                ->when($request->filled('q'), function ($query) use ($request) {
                    $search = trim((string) $request->input('q'));
                    $query->where(function ($match) use ($search) {
                        $match->where('title', 'like', "%{$search}%")
                            ->orWhere('original_name', 'like', "%{$search}%");
                    });
                })
                ->when($request->filled('type'), fn ($query) => $query->where('type', $request->input('type')))
                ->latest('imported_at')
                ->latest('id')
                ->paginate(18, ['*'], 'materials_page')
                ->withQueryString();

            $syncOperations = AiOperation::query()
                ->where('user_id', $user->id)
                ->whereIn('feature', ['material_library_scan', 'material_library_sync'])
                ->latest()
                ->limit(5)
                ->get();
        }

        return view('courses.materials_index', compact('courses', 'materials', 'syncOperations'));
    }

    public function index(Course $course)
    {
        Gate::authorize('view', $course);

        $course->load(['classes', 'modules.lessons']);
        $isManager = $this->canManage($course);
        $assignments = LearningMaterialAssignment::with(['material', 'classroom', 'lesson', 'unlockLesson'])
            ->where('course_id', $course->id)
            ->notArchived()
            ->orderBy('sort_order')
            ->latest()
            ->get();

        if (!$isManager) {
            $assignments = $assignments
                ->filter(fn ($assignment) => $assignment->visibleToStudent(auth()->user()))
                ->values();
        }

        $availableMaterials = LearningMaterial::notArchived()
            ->accessibleTo(auth()->user())
            ->latest('id')
            ->limit(30)
            ->get();

        return view('courses.materials', [
            'course' => $course,
            'assignments' => $assignments,
            'availableMaterials' => $availableMaterials,
            'lessons' => $course->modules->flatMap->lessons->values(),
            'classes' => $course->classes->where('status', '!=', Classroom::STATUS_ARCHIVED)->values(),
            'isManager' => $isManager,
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $this->authorizeManage($course);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'type' => 'required|in:pdf,slide,video,website,code,image,document,other',
            'source_type' => 'required|in:file,link',
            'file' => 'nullable|required_if:source_type,file|file|max:51200',
            'url' => 'nullable|required_if:source_type,link|url|max:2048',
            'class_id' => 'nullable|integer|exists:classes,id',
            'lesson_id' => 'nullable|integer|exists:lessons,id',
            'unlock_when_lesson_id' => 'nullable|integer|exists:lessons,id',
            'available_from' => 'nullable|date',
            'status' => 'nullable|in:published,hidden',
        ]);

        $this->assertCourseClass($course, $validated['class_id'] ?? null);
        $this->assertCourseLesson($course, $validated['lesson_id'] ?? null);
        $this->assertCourseLesson($course, $validated['unlock_when_lesson_id'] ?? null);

        DB::transaction(function () use ($request, $course, $validated) {
            $material = $validated['source_type'] === LearningMaterial::SOURCE_FILE
                ? $this->createFileMaterial($request, $validated)
                : $this->createLinkMaterial($validated);

            $this->createAssignment($course, $material, $validated);
        });

        return back()->with('success', 'Đã thêm học liệu vào kho.');
    }

    public function attachExisting(Request $request, Course $course)
    {
        $this->authorizeManage($course);

        $validated = $request->validate([
            'learning_material_id' => 'required|exists:learning_materials,id',
            'class_id' => 'nullable|integer|exists:classes,id',
            'lesson_id' => 'nullable|integer|exists:lessons,id',
            'unlock_when_lesson_id' => 'nullable|integer|exists:lessons,id',
            'available_from' => 'nullable|date',
            'status' => 'nullable|in:published,hidden',
        ]);

        $this->assertCourseClass($course, $validated['class_id'] ?? null);
        $this->assertCourseLesson($course, $validated['lesson_id'] ?? null);
        $this->assertCourseLesson($course, $validated['unlock_when_lesson_id'] ?? null);

        $material = LearningMaterial::notArchived()->findOrFail($validated['learning_material_id']);
        abort_unless($material->accessibleBy(auth()->user()), 403);
        $this->createAssignment($course, $material, $validated);

        return back()->with('success', 'Đã gắn học liệu có sẵn vào khóa học.');
    }

    public function updateAssignment(Request $request, Course $course, LearningMaterialAssignment $assignment)
    {
        $this->authorizeManage($course);
        abort_unless((int) $assignment->course_id === (int) $course->id, 404);

        $validated = $request->validate([
            'class_id' => 'nullable|integer|exists:classes,id',
            'lesson_id' => 'nullable|integer|exists:lessons,id',
            'unlock_when_lesson_id' => 'nullable|integer|exists:lessons,id',
            'available_from' => 'nullable|date',
            'status' => 'nullable|in:published,hidden,archived',
        ]);

        $this->assertCourseClass($course, $validated['class_id'] ?? null);
        $this->assertCourseLesson($course, $validated['lesson_id'] ?? null);
        $this->assertCourseLesson($course, $validated['unlock_when_lesson_id'] ?? null);

        $wasPublished = $assignment->status === LearningMaterialAssignment::STATUS_PUBLISHED;
        $assignment->update([
            'class_id' => $validated['class_id'] ?? null,
            'lesson_id' => $validated['lesson_id'] ?? null,
            'unlock_when_lesson_id' => $validated['unlock_when_lesson_id'] ?? null,
            'available_from' => $validated['available_from'] ?? null,
            'status' => $validated['status'] ?? LearningMaterialAssignment::STATUS_PUBLISHED,
        ]);

        if (!$wasPublished && $assignment->status === LearningMaterialAssignment::STATUS_PUBLISHED) {
            $assignment->load('material');
            app(NotificationCenter::class)->notifyCourseStudents(
                $course, 'material', 'Có học liệu mới',
                "Học liệu \"{$assignment->material->title}\" vừa được thêm vào khóa học.",
                route('courses.materials.index', $course), ['material_assignment_id' => $assignment->id],
                "material-assignment:{$assignment->id}:published", $assignment->class_id
            );
        }

        return back()->with('success', 'Đã cập nhật điều kiện mở học liệu.');
    }

    public function destroyAssignment(Course $course, LearningMaterialAssignment $assignment)
    {
        $this->authorizeManage($course);
        abort_unless((int) $assignment->course_id === (int) $course->id, 404);

        $assignment->update(['status' => LearningMaterialAssignment::STATUS_ARCHIVED]);

        return back()->with('success', 'Đã bỏ học liệu khỏi khóa học. File gốc vẫn được giữ để tái sử dụng.');
    }

    public function destroyMaterial(Course $course, LearningMaterial $material)
    {
        $this->authorizeManage($course);
        abort_unless($material->ownedBy(auth()->user()), 403);

        $material->update(['status' => LearningMaterial::STATUS_ARCHIVED]);
        $material->assignments()->update(['status' => LearningMaterialAssignment::STATUS_ARCHIVED]);

        return back()->with('success', 'Đã lưu trữ học liệu và các lượt gắn liên quan.');
    }

    public function download(LearningMaterialAssignment $assignment)
    {
        $assignment->load(['material', 'course.classes', 'lesson', 'unlockLesson']);
        Gate::authorize('view', $assignment->course);

        if (!$this->canManage($assignment->course) && !$assignment->visibleToStudent(auth()->user())) {
            abort(403);
        }

        $material = $assignment->material;
        abort_unless($material && $material->status !== LearningMaterial::STATUS_ARCHIVED, 404);

        if ($material->isLink()) {
            return redirect()->away($material->url);
        }

        abort_unless($material->fileExists(), 404, 'Không tìm thấy file học liệu.');

        return Storage::disk($material->disk)->download(
            $material->file_path,
            $material->original_name ?: basename($material->file_path)
        );
    }

    public function downloadLibrary(LearningMaterial $material)
    {
        abort_unless($material->status !== LearningMaterial::STATUS_ARCHIVED, 404);
        abort_unless($material->accessibleBy(auth()->user()), 403);
        abort_unless($material->isFile() && $material->fileExists(), 404, 'Không tìm thấy file học liệu.');

        return Storage::disk($material->disk)->download(
            $material->file_path,
            $material->original_name ?: basename($material->file_path)
        );
    }

    public function scanLegacy()
    {
        return $this->dispatchLegacyOperation(true);
    }

    public function syncLegacy()
    {
        return $this->dispatchLegacyOperation(false);
    }

    public function searchLibrary(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $user->isTeacher(), 403);

        $validated = $request->validate([
            'q' => 'nullable|string|max:150',
            'type' => 'nullable|in:pdf,slide,video,website,code,image,document,other',
        ]);
        $search = trim((string) ($validated['q'] ?? ''));

        $materials = LearningMaterial::notArchived()
            ->accessibleTo($user)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($match) use ($search) {
                    $match->where('title', 'like', "%{$search}%")
                        ->orWhere('original_name', 'like', "%{$search}%");
                });
            })
            ->when($validated['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(fn (LearningMaterial $material) => [
                'id' => $material->id,
                'title' => $material->title,
                'type' => $material->typeLabel(),
                'size' => $material->humanSize(),
            ]);

        return response()->json(['data' => $materials]);
    }

    private function createFileMaterial(Request $request, array $data): LearningMaterial
    {
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'hoc-lieu';
        $storedName = $safeName . '-' . now()->format('YmdHis') . '-' . Str::random(8) . ($extension ? '.' . $extension : '');
        $disk = config('filesystems.lesson_attachment_disk', config('filesystems.submission_disk', 'public'));

        return LearningMaterial::create([
            'title' => ($data['title'] ?? null) ?: pathinfo($originalName, PATHINFO_FILENAME),
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'source_type' => LearningMaterial::SOURCE_FILE,
            'disk' => $disk,
            'file_path' => $file->storeAs('course-materials', $storedName, $disk),
            'original_name' => $originalName,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
            'status' => LearningMaterial::STATUS_PUBLISHED,
        ]);
    }

    private function createLinkMaterial(array $data): LearningMaterial
    {
        return LearningMaterial::create([
            'title' => ($data['title'] ?? null) ?: $this->titleFromUrl($data['url']),
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'source_type' => LearningMaterial::SOURCE_LINK,
            'url' => $data['url'],
            'uploaded_by' => auth()->id(),
            'status' => LearningMaterial::STATUS_PUBLISHED,
        ]);
    }

    private function createAssignment(Course $course, LearningMaterial $material, array $data): LearningMaterialAssignment
    {
        $assignment = LearningMaterialAssignment::create([
            'learning_material_id' => $material->id,
            'course_id' => $course->id,
            'class_id' => $data['class_id'] ?? null,
            'lesson_id' => $data['lesson_id'] ?? null,
            'unlock_when_lesson_id' => $data['unlock_when_lesson_id'] ?? null,
            'available_from' => $data['available_from'] ?? null,
            'status' => $data['status'] ?? LearningMaterialAssignment::STATUS_PUBLISHED,
            'sort_order' => LearningMaterialAssignment::where('course_id', $course->id)->count() + 1,
        ]);

        if ($assignment->status === LearningMaterialAssignment::STATUS_PUBLISHED) {
            app(NotificationCenter::class)->notifyCourseStudents(
                $course,
                'material',
                'Có học liệu mới',
                "Học liệu \"{$material->title}\" vừa được thêm vào khóa học.",
                route('courses.materials.index', $course),
                ['material_assignment_id' => $assignment->id],
                "material-assignment:{$assignment->id}:published",
                $assignment->class_id
            );
        }

        return $assignment;
    }

    private function titleFromUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host ? 'Tài nguyên từ ' . preg_replace('/^www\./', '', $host) : 'Tài nguyên tham khảo';
    }

    private function assertCourseClass(Course $course, $classId): void
    {
        if (!$classId) {
            return;
        }

        abort_unless($course->classes()->where('classes.id', $classId)->exists(), 422, 'Lớp đã chọn không thuộc khóa học này.');
    }

    private function assertCourseLesson(Course $course, $lessonId): void
    {
        if (!$lessonId) {
            return;
        }

        $exists = Lesson::query()
            ->join('modules', 'lessons.module_id', '=', 'modules.id')
            ->where('modules.course_id', $course->id)
            ->where('lessons.id', $lessonId)
            ->exists();

        abort_unless($exists, 422, 'Bài học đã chọn không thuộc khóa học này.');
    }

    private function authorizeManage(Course $course): void
    {
        Gate::authorize('update', $course);
    }

    private function canManage(Course $course): bool
    {
        return auth()->check() && Gate::allows('update', $course);
    }

    private function dispatchLegacyOperation(bool $dryRun)
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $user->isTeacher(), 403);

        $active = AiOperation::where('user_id', $user->id)
            ->whereIn('feature', ['material_library_scan', 'material_library_sync'])
            ->whereIn('status', [AiOperation::STATUS_QUEUED, AiOperation::STATUS_PROCESSING])
            ->exists();

        if ($active) {
            return back()->with('error', 'Một tác vụ quét hoặc đồng bộ học liệu đang chạy. Vui lòng chờ hoàn tất.');
        }

        $operation = AiOperation::create([
            'user_id' => $user->id,
            'feature' => $dryRun ? 'material_library_scan' : 'material_library_sync',
            'provider' => 'storage',
            'model' => config('filesystems.lesson_attachment_disk', 'public'),
            'status' => AiOperation::STATUS_QUEUED,
            'metadata' => ['dry_run' => $dryRun],
        ]);

        SyncLegacyLearningMaterials::dispatch($operation->id, $user->id, $dryRun);

        return redirect()->route('materials.index')
            ->with('success', $dryRun
                ? 'Đã đưa yêu cầu quét file cũ vào hàng đợi.'
                : 'Đã đưa yêu cầu đồng bộ học liệu cũ vào hàng đợi.');
    }

    private function typeOptions(): array
    {
        return [
            'pdf' => 'PDF',
            'slide' => 'Slide bài giảng',
            'video' => 'Link video',
            'website' => 'Website tham khảo',
            'code' => 'File code mẫu',
            'image' => 'Hình ảnh',
            'document' => 'Tài liệu',
            'other' => 'Khác',
        ];
    }
}
