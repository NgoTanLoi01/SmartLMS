<?php

namespace App\Services;

use App\Models\Assignments;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialSource;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class LegacyLearningMaterialService
{
    public function run(User $user, bool $dryRun = false): array
    {
        $summary = [
            'mode' => $dryRun ? 'scan' : 'sync',
            'references' => 0,
            'unique_files' => 0,
            'available_files' => 0,
            'missing_files' => 0,
            'already_indexed' => 0,
            'imported' => 0,
            'sources_added' => 0,
            'errors' => 0,
        ];
        $resolved = [];

        $process = function (array $candidate) use (&$summary, &$resolved, $dryRun): void {
            $summary['references']++;
            $key = $candidate['disk'].'|'.$candidate['path'];

            if (! array_key_exists($key, $resolved)) {
                $summary['unique_files']++;
                $resolved[$key] = $this->resolveMaterial($candidate, $summary, $dryRun);
            }

            $material = $resolved[$key];
            if ($dryRun || ! $material) {
                return;
            }

            $source = LearningMaterialSource::firstOrCreate([
                'learning_material_id' => $material->id,
                'source_type' => $candidate['source_type'],
                'source_id' => $candidate['source_id'],
            ], [
                'course_id' => $candidate['course_id'],
            ]);

            if ($source->wasRecentlyCreated) {
                $summary['sources_added']++;
            } elseif (! $source->course_id && $candidate['course_id']) {
                $source->update(['course_id' => $candidate['course_id']]);
            }
        };

        $this->scanLessons($user, $process);
        $this->scanAssignments($user, $process);

        return $summary;
    }

    private function resolveMaterial(array $candidate, array &$summary, bool $dryRun): ?LearningMaterial
    {
        try {
            $disk = Storage::disk($candidate['disk']);
            if (! $disk->exists($candidate['path'])) {
                $summary['missing_files']++;

                return null;
            }

            $summary['available_files']++;
            $existing = LearningMaterial::where('disk', $candidate['disk'])
                ->where('file_path', $candidate['path'])
                ->first();

            if ($existing) {
                $summary['already_indexed']++;
                if (! $dryRun) {
                    $existing->update([
                        'storage_status' => 'available',
                        'last_verified_at' => now(),
                    ]);
                }

                return $existing;
            }

            if ($dryRun) {
                return null;
            }

            $mimeType = $candidate['mime_type'] ?: rescue(fn () => $disk->mimeType($candidate['path']), null, false);
            $fileSize = $candidate['file_size'] ?: rescue(fn () => $disk->size($candidate['path']), null, false);
            $material = LearningMaterial::create([
                'title' => $this->title($candidate['original_name'] ?: basename($candidate['path'])),
                'description' => $candidate['description'],
                'type' => $this->materialType($candidate['original_name'] ?: $candidate['path'], $mimeType),
                'source_type' => LearningMaterial::SOURCE_FILE,
                'disk' => $candidate['disk'],
                'file_path' => $candidate['path'],
                'original_name' => $candidate['original_name'] ?: basename($candidate['path']),
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'uploaded_by' => $candidate['owner_id'],
                'status' => LearningMaterial::STATUS_PUBLISHED,
                'storage_status' => 'available',
                'last_verified_at' => now(),
                'imported_at' => now(),
            ]);
            $summary['imported']++;

            return $material;
        } catch (Throwable $exception) {
            report($exception);
            $summary['errors']++;

            return null;
        }
    }

    private function scanLessons(User $user, callable $process): void
    {
        Lesson::query()
            ->join('modules', 'modules.id', '=', 'lessons.module_id')
            ->join('courses', 'courses.id', '=', 'modules.course_id')
            ->when($user->isTeacher(), fn ($query) => $query->where('courses.teacher_id', $user->id))
            ->select([
                'lessons.*',
                'modules.course_id as source_course_id',
                'courses.teacher_id as source_teacher_id',
                'courses.title as source_course_title',
            ])
            ->orderBy('lessons.id')
            ->chunkById(100, function ($lessons) use ($user, $process): void {
                foreach ($lessons as $lesson) {
                    $ownerId = $lesson->source_teacher_id ?: $user->id;

                    if ($lesson->attachment) {
                        $process([
                            'disk' => $lesson->attachment_disk ?: config('filesystems.lesson_attachment_disk', 'public'),
                            'path' => ltrim($lesson->attachment, '/'),
                            'original_name' => $lesson->attachment_original_name,
                            'mime_type' => $lesson->attachment_mime_type,
                            'file_size' => $lesson->attachment_size,
                            'owner_id' => $ownerId,
                            'course_id' => $lesson->source_course_id,
                            'source_type' => LearningMaterialSource::LESSON_ATTACHMENT,
                            'source_id' => $lesson->id,
                            'description' => "Đồng bộ từ file đính kèm bài học \"{$lesson->title}\" – {$lesson->source_course_title}.",
                        ]);
                    }

                    foreach ($this->r2Assets((string) $lesson->content) as $asset) {
                        $process([
                            ...$asset,
                            'owner_id' => $ownerId,
                            'course_id' => $lesson->source_course_id,
                            'source_type' => LearningMaterialSource::LESSON_CONTENT,
                            'source_id' => $lesson->id,
                            'description' => "Đồng bộ từ nội dung bài học \"{$lesson->title}\" – {$lesson->source_course_title}.",
                        ]);
                    }
                }
            }, 'lessons.id', 'id');
    }

    private function scanAssignments(User $user, callable $process): void
    {
        Assignments::query()
            ->join('courses', 'courses.id', '=', 'assignments.course_id')
            ->when($user->isTeacher(), fn ($query) => $query->where('courses.teacher_id', $user->id))
            ->select([
                'assignments.*',
                'courses.teacher_id as source_teacher_id',
                'courses.title as source_course_title',
            ])
            ->orderBy('assignments.id')
            ->chunkById(100, function ($assignments) use ($user, $process): void {
                foreach ($assignments as $assignment) {
                    foreach ($this->r2Assets((string) $assignment->instructions) as $asset) {
                        $process([
                            ...$asset,
                            'owner_id' => $assignment->source_teacher_id ?: $user->id,
                            'course_id' => $assignment->course_id,
                            'source_type' => LearningMaterialSource::ASSIGNMENT_CONTENT,
                            'source_id' => $assignment->id,
                            'description' => "Đồng bộ từ nội dung bài tập \"{$assignment->title}\" – {$assignment->source_course_title}.",
                        ]);
                    }
                }
            }, 'assignments.id', 'id');
    }

    private function r2Assets(string $html): array
    {
        if ($html === '' || ! preg_match_all('/<(?:img|a)\b[^>]*(?:src|href)\s*=\s*(["\'])(.*?)\1/isu', $html, $matches)) {
            return [];
        }

        $assets = [];
        foreach ($matches[2] as $url) {
            $path = $this->r2Path(html_entity_decode($url, ENT_QUOTES | ENT_HTML5));
            if (! $path) {
                continue;
            }

            $assets[$path] = [
                'disk' => 'r2',
                'path' => $path,
                'original_name' => basename($path),
                'mime_type' => null,
                'file_size' => null,
            ];
        }

        return array_values($assets);
    }

    private function r2Path(string $url): ?string
    {
        foreach (['url', 'endpoint'] as $configKey) {
            $base = rtrim((string) config("filesystems.disks.r2.{$configKey}"), '/');
            if ($base === '') {
                continue;
            }

            $baseParts = parse_url($base);
            $urlParts = parse_url($url);
            if (! isset($baseParts['host'], $urlParts['host']) || strcasecmp($baseParts['host'], $urlParts['host']) !== 0) {
                continue;
            }

            $basePath = rtrim($baseParts['path'] ?? '', '/');
            $urlPath = rawurldecode($urlParts['path'] ?? '');
            if ($basePath !== '' && ! Str::startsWith($urlPath, $basePath.'/')) {
                continue;
            }

            $path = ltrim(substr($urlPath, strlen($basePath)), '/');
            if ($configKey === 'endpoint') {
                $bucket = trim((string) config('filesystems.disks.r2.bucket'), '/');
                if ($bucket !== '' && Str::startsWith($path, $bucket.'/')) {
                    $path = substr($path, strlen($bucket) + 1);
                }
            }

            return $path !== '' ? $path : null;
        }

        return null;
    }

    private function materialType(string $name, ?string $mimeType): string
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return match (true) {
            $extension === 'pdf' || $mimeType === 'application/pdf' => 'pdf',
            in_array($extension, ['ppt', 'pptx', 'odp'], true) => 'slide',
            in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'avif'], true)
                || Str::startsWith((string) $mimeType, 'image/') => 'image',
            in_array($extension, ['mp4', 'webm', 'mov', 'm4v'], true)
                || Str::startsWith((string) $mimeType, 'video/') => 'video',
            in_array($extension, ['html', 'css', 'js', 'ts', 'php', 'py', 'java', 'json', 'xml'], true) => 'code',
            default => 'document',
        };
    }

    private function title(string $name): string
    {
        $decoded = rawurldecode($name);

        return pathinfo($decoded, PATHINFO_FILENAME) ?: 'Học liệu cũ';
    }
}
