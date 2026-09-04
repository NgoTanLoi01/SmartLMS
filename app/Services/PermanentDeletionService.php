<?php

namespace App\Services;

use App\Models\Assignments;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialAssignment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PermanentDeletionService
{
    public function __construct(private StoredAssetReferenceService $assetReferences) {}

    public function purge(string $type, Model $model): void
    {
        match ($type) {
            'course' => $this->purgeCourse($model),
            'module' => $this->purgeModule($model),
            'lesson' => $this->purgeLesson($model),
            'assignment' => $this->purgeAssignment($model),
            'material' => $this->purgeMaterial($model),
            'material_assignment' => $this->purgeMaterialAssignment($model),
            'schedule' => $this->purgeSchedule($model),
            default => throw new \InvalidArgumentException('Loại dữ liệu không hỗ trợ xóa vĩnh viễn.'),
        };
    }

    public function purgeCourse(Course $course): void
    {
        $lessonFiles = Lesson::whereHas('module', fn ($query) => $query->where('course_id', $course->id))
            ->whereNotNull('attachment')
            ->get(['attachment', 'attachment_disk']);
        $assignmentIds = Assignments::withTrashed()->where('course_id', $course->id)->pluck('id');
        [$submissionFiles, $legacyFiles] = $this->submissionFiles($assignmentIds);

        DB::transaction(function () use ($course, $assignmentIds): void {
            $this->deleteLegacySubmissions($assignmentIds);
            if (Schema::hasTable('document_chunks')) {
                DB::table('document_chunks')->where('course_id', $course->id)->delete();
            }
            $course->delete();
        });

        $this->deleteLessonFiles($lessonFiles);
        $this->deleteSubmissionFiles($submissionFiles, $legacyFiles);
    }

    private function purgeModule(Model $model): void
    {
        /** @var Module $module */
        $module = $model;
        $lessonIds = Lesson::where('module_id', $module->id)->pluck('id');
        $lessonFiles = Lesson::whereIn('id', $lessonIds)->whereNotNull('attachment')->get(['attachment', 'attachment_disk']);
        $assignmentIds = Assignments::withTrashed()->whereIn('lesson_id', $lessonIds)->pluck('id');
        [$submissionFiles, $legacyFiles] = $this->submissionFiles($assignmentIds);

        DB::transaction(function () use ($module, $assignmentIds): void {
            $this->deleteLegacySubmissions($assignmentIds);
            $module->delete();
        });

        $this->deleteLessonFiles($lessonFiles);
        $this->deleteSubmissionFiles($submissionFiles, $legacyFiles);
    }

    private function purgeLesson(Model $model): void
    {
        /** @var Lesson $lesson */
        $lesson = $model;
        $assignmentIds = Assignments::withTrashed()->where('lesson_id', $lesson->id)->pluck('id');
        [$submissionFiles, $legacyFiles] = $this->submissionFiles($assignmentIds);
        $lessonFile = collect($lesson->attachment ? [[
            'attachment' => $lesson->attachment,
            'attachment_disk' => $lesson->attachment_disk,
        ]] : [])->map(fn (array $file) => (object) $file);

        DB::transaction(function () use ($lesson, $assignmentIds): void {
            $this->deleteLegacySubmissions($assignmentIds);
            $lesson->delete();
        });

        $this->deleteLessonFiles($lessonFile);
        $this->deleteSubmissionFiles($submissionFiles, $legacyFiles);
    }

    private function purgeAssignment(Model $model): void
    {
        /** @var Assignments $assignment */
        $assignment = $model;
        [$submissionFiles, $legacyFiles] = $this->submissionFiles(collect([$assignment->id]));

        DB::transaction(function () use ($assignment): void {
            $this->deleteLegacySubmissions(collect([$assignment->id]));
            $assignment->forceDelete();
        });

        $this->deleteSubmissionFiles($submissionFiles, $legacyFiles);
    }

    private function purgeMaterial(Model $model): void
    {
        /** @var LearningMaterial $material */
        $material = $model;
        $deletePhysicalFile = $material->isFile() && ! $material->sources()->exists();
        $disk = $material->disk;
        $path = $material->file_path;

        DB::transaction(fn () => $material->delete());

        if ($deletePhysicalFile && $disk && $path) {
            rescue(fn () => Storage::disk($disk)->delete($path), report: false);
        }
    }

    private function purgeMaterialAssignment(Model $model): void
    {
        /** @var LearningMaterialAssignment $assignment */
        $assignment = $model;
        DB::transaction(fn () => $assignment->delete());
    }

    private function purgeSchedule(Model $model): void
    {
        /** @var Schedule $schedule */
        $schedule = $model;
        DB::transaction(fn () => $schedule->delete());
    }

    /**
     * @param  Collection<int, int>  $assignmentIds
     * @return array{0: Collection, 1: Collection}
     */
    private function submissionFiles(Collection $assignmentIds): array
    {
        $files = AssignmentSubmission::whereIn('assignment_id', $assignmentIds)
            ->whereNotNull('file_path')
            ->get(['file_path', 'file_disk']);
        $legacy = Schema::hasTable('submissions')
            ? DB::table('submissions')->whereIn('assignment_id', $assignmentIds)->whereNotNull('file_path')->pluck('file_path')
            : collect();

        return [$files, $legacy];
    }

    private function deleteLegacySubmissions(Collection $assignmentIds): void
    {
        if (Schema::hasTable('submissions')) {
            DB::table('submissions')->whereIn('assignment_id', $assignmentIds)->delete();
        }
    }

    private function deleteLessonFiles(Collection $files): void
    {
        $files->each(function ($file): void {
            $disk = $file->attachment_disk ?: 'public';
            rescue(fn () => $this->assetReferences->deleteIfUnindexed($disk, $file->attachment), report: false);
        });
    }

    private function deleteSubmissionFiles(Collection $files, Collection $legacyFiles): void
    {
        $files->each(function ($file): void {
            rescue(fn () => Storage::disk($file->file_disk ?: 'public')->delete($file->file_path), report: false);
        });
        $legacyFiles->each(fn ($path) => rescue(fn () => Storage::disk('public')->delete($path), report: false));
    }
}
