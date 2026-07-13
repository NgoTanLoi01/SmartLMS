<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;
use ZipArchive;

class LocalCourseContextSearchService
{
    private const RESULT_LIMIT = 5;

    private const COURSE_LIMIT = 30;

    private const LESSON_LIMIT = 200;

    private const ATTACHMENT_READ_LIMIT = 60;

    private const ATTACHMENT_TEXT_LIMIT = 12000;

    private const CONTEXT_TEXT_LIMIT = 9000;

    public function search(string $query, ?User $user = null): string
    {
        $keywords = $this->keywords($query);

        if ($keywords->isEmpty()) {
            return '';
        }

        $courses = $this->accessibleCourses($user);

        if ($courses->isEmpty()) {
            return '';
        }

        $lessons = $this->loadLessons($courses->pluck('id'));
        $attachmentReads = 0;

        $matches = $lessons
            ->map(function (Lesson $lesson) use ($keywords, &$attachmentReads) {
                $lessonText = $this->lessonText($lesson);
                $attachmentText = '';

                if ($lesson->attachment && $attachmentReads < self::ATTACHMENT_READ_LIMIT) {
                    $attachmentReads++;
                    $attachmentText = $this->attachmentText($lesson);
                }

                $combinedText = trim($lessonText."\n".$attachmentText);
                $score = $this->score($combinedText, $keywords);

                if ($score <= 0) {
                    return null;
                }

                return [
                    'score' => $score,
                    'course' => $lesson->module?->course?->title ?? 'Khóa học',
                    'module' => $lesson->module?->title ?? 'Chương',
                    'lesson' => $lesson->title,
                    'content' => $this->excerpt($combinedText, $keywords),
                    'attachment' => $lesson->attachment_original_name ?: ($lesson->attachment ? basename($lesson->attachment) : null),
                ];
            })
            ->filter()
            ->sortByDesc('score')
            ->take(self::RESULT_LIMIT)
            ->values();

        if ($matches->isEmpty()) {
            return '';
        }

        return $this->formatContext($matches);
    }

    public function lessonContext(int $lessonId, ?User $user = null): string
    {
        if (! $user) {
            return '';
        }

        $courseIds = $this->accessibleCourses($user)->pluck('id');
        if ($courseIds->isEmpty()) {
            return '';
        }

        $lesson = Lesson::query()
            ->with(['module.course'])
            ->whereKey($lessonId)
            ->whereHas('module', fn ($query) => $query->whereIn('course_id', $courseIds))
            ->first();

        if (! $lesson || ! $lesson->module?->course) {
            return '';
        }

        if ($user->isStudent() && ! $lesson->isVisibleToStudents()) {
            return '';
        }

        $content = trim($this->lessonText($lesson)."\n".$this->attachmentText($lesson));

        if ($content === '') {
            return '';
        }

        return Str::limit(implode("\n", [
            'Bài học hiện tại',
            'Khóa học: '.$lesson->module->course->title,
            'Chương: '.$lesson->module->title,
            'Bài học: '.$lesson->title,
            'Nội dung bài học: '.$this->plainText($content),
        ]), self::CONTEXT_TEXT_LIMIT, '');
    }

    public function moduleContext(int $moduleId, ?User $user = null): string
    {
        if (! $user) {
            return '';
        }

        $courseIds = $this->accessibleCourses($user)->pluck('id');
        if ($courseIds->isEmpty()) {
            return '';
        }

        $module = Module::query()
            ->with(['course', 'lessons' => fn ($query) => $query->notArchived()->orderBy('order')])
            ->whereKey($moduleId)
            ->whereIn('course_id', $courseIds)
            ->first();

        if (! $module || ! $module->course) {
            return '';
        }

        $lessonTexts = $module->lessons
            ->map(function (Lesson $lesson) {
                return trim($this->lessonText($lesson)."\n".$this->attachmentText($lesson));
            })
            ->filter()
            ->implode("\n\n");

        if ($lessonTexts === '') {
            return '';
        }

        return Str::limit(implode("\n", [
            'Chương học nguồn',
            'Khóa học: '.$module->course->title,
            'Chương: '.$module->title,
            'Nội dung các bài học: '.$this->plainText($lessonTexts),
        ]), self::CONTEXT_TEXT_LIMIT, '');
    }

    private function accessibleCourses(?User $user): Collection
    {
        $query = Course::query()
            ->notArchived()
            ->with(['classes:id'])
            ->orderByDesc('updated_at')
            ->limit(self::COURSE_LIMIT);

        if (! $user) {
            return collect();
        }

        if ($user->isAdmin()) {
            return $query->get();
        }

        if ($user->isTeacher()) {
            return $query->where('teacher_id', $user->id)->get();
        }

        if ($user->isStudent()) {
            $classIds = $user->classes()
                ->where('classes.status', 'active')
                ->pluck('classes.id');

            if ($classIds->isEmpty()) {
                return collect();
            }

            return $query
                ->visibleToStudents()
                ->whereHas('classes', fn ($classQuery) => $classQuery->whereIn('classes.id', $classIds))
                ->get();
        }

        return collect();
    }

    private function loadLessons(Collection $courseIds): Collection
    {
        return Lesson::query()
            ->select('lessons.*')
            ->with(['module.course'])
            ->join('modules', 'lessons.module_id', '=', 'modules.id')
            ->whereIn('modules.course_id', $courseIds)
            ->where(fn ($query) => $query->whereNull('modules.status')->orWhere('modules.status', '!=', 'archived'))
            ->notArchived()
            ->orderBy('modules.order')
            ->orderBy('lessons.order')
            ->limit(self::LESSON_LIMIT)
            ->get();
    }

    private function lessonText(Lesson $lesson): string
    {
        return implode("\n", array_filter([
            'Khóa học: '.($lesson->module?->course?->title ?? ''),
            'Chương: '.($lesson->module?->title ?? ''),
            'Bài học: '.$lesson->title,
            $this->plainText((string) $lesson->content),
            $lesson->attachment_original_name ? 'File bài giảng: '.$lesson->attachment_original_name : null,
        ]));
    }

    private function attachmentText(Lesson $lesson): string
    {
        $diskName = $lesson->attachment_disk ?: config('filesystems.lesson_attachment_disk', 'public');
        $path = $lesson->attachment;

        if (! $path) {
            return '';
        }

        $cacheKey = 'lesson_attachment_text:'.sha1(implode('|', [
            $diskName,
            $path,
            (string) $lesson->attachment_size,
            (string) optional($lesson->updated_at)->timestamp,
        ]));

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($diskName, $path, $lesson) {
            try {
                $disk = Storage::disk($diskName);

                if (! $disk->exists($path)) {
                    return '';
                }

                $content = $disk->get($path);
                $name = $lesson->attachment_original_name ?: basename($path);
                $extension = Str::lower(pathinfo($name, PATHINFO_EXTENSION));
                $text = $this->extractTextFromContent($content, $extension);

                if ($text === '') {
                    return '';
                }

                return Str::limit($this->plainText($text), self::ATTACHMENT_TEXT_LIMIT, '');
            } catch (\Throwable $e) {
                Log::warning('Không đọc được file bài giảng cho chatbot local search', [
                    'lesson_id' => $lesson->id,
                    'disk' => $diskName,
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);

                return '';
            }
        });
    }

    private function extractTextFromContent(string $content, string $extension): string
    {
        return match ($extension) {
            'txt', 'md', 'csv', 'json', 'xml' => $content,
            'html', 'htm' => strip_tags($content),
            'pdf' => $this->extractPdfText($content),
            'docx' => $this->extractDocxText($content),
            default => '',
        };
    }

    private function extractPdfText(string $content): string
    {
        try {
            return (new Parser)->parseContent($content)->getText();
        } catch (\Throwable) {
            return '';
        }
    }

    private function extractDocxText(string $content): string
    {
        if (! class_exists(ZipArchive::class)) {
            return '';
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'smartlms-docx-');

        if (! $tempPath) {
            return '';
        }

        try {
            file_put_contents($tempPath, $content);

            $zip = new ZipArchive;
            if ($zip->open($tempPath) !== true) {
                return '';
            }

            $xml = $zip->getFromName('word/document.xml') ?: '';
            $zip->close();

            if ($xml === '') {
                return '';
            }

            $xml = preg_replace('/<\/w:p>/', "\n", $xml);

            return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
        } finally {
            @unlink($tempPath);
        }
    }

    private function score(string $text, Collection $keywords): int
    {
        $normalizedText = $this->normalize($text);

        return $keywords->sum(function (string $keyword) use ($normalizedText) {
            return substr_count($normalizedText, $keyword);
        });
    }

    private function excerpt(string $text, Collection $keywords): string
    {
        $plain = $this->plainText($text);
        $normalized = $this->normalize($plain);
        $position = 0;

        foreach ($keywords as $keyword) {
            $found = strpos($normalized, $keyword);
            if ($found !== false) {
                $position = max(0, $found - 350);
                break;
            }
        }

        return trim(Str::limit(substr($plain, $position, 1600), 1600));
    }

    private function formatContext(Collection $matches): string
    {
        $context = $matches
            ->map(function (array $match, int $index) {
                $lines = [
                    'Nguồn '.($index + 1),
                    'Khóa học: '.$match['course'],
                    'Chương: '.$match['module'],
                    'Bài học: '.$match['lesson'],
                ];

                if ($match['attachment']) {
                    $lines[] = 'File bài giảng: '.$match['attachment'];
                }

                $lines[] = 'Nội dung liên quan: '.$match['content'];

                return implode("\n", $lines);
            })
            ->implode("\n\n---\n\n");

        return Str::limit($context, self::CONTEXT_TEXT_LIMIT, '');
    }

    private function keywords(string $query): Collection
    {
        $stopwords = [
            'cua', 'cho', 'voi', 'nhung', 'cac', 'mot', 'toi', 'em', 'anh', 'chi', 'hay',
            'giup', 'giai', 'thich', 'la', 'va', 've', 'trong', 'nhu', 'the', 'nao',
            'duoc', 'khong', 'bai', 'hoc', 'phan',
        ];

        preg_match_all('/[\p{L}\p{N}_-]+/u', $this->normalize($query), $matches);

        return collect($matches[0] ?? [])
            ->filter(fn ($word) => mb_strlen($word) >= 2 && ! in_array($word, $stopwords, true))
            ->unique()
            ->values();
    }

    private function plainText(string $text): string
    {
        $text = $this->cleanUtf8($text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($text);
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($this->plainText($text), 'UTF-8');
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        return $ascii !== false ? $ascii : $text;
    }

    private function cleanUtf8(string $text): string
    {
        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = @iconv('UTF-8', 'UTF-8//IGNORE', $text) ?: '';
        }

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';
    }
}
