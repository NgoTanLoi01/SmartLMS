<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VectorCourseContextSearchService
{
    public function __construct(
        private GeminiEmbeddingService $embeddingService,
        private RagDocumentAccessService $documentAccess,
    ) {}

    public function search(string $query, ?User $user, ?int $courseId = null): array
    {
        if (! $user || trim($query) === '') {
            return ['context' => '', 'sources' => []];
        }

        $accessibleCourseIds = $this->documentAccess->accessibleCourseIds($user);
        if ($courseId !== null && ! in_array($courseId, $accessibleCourseIds, true)) {
            return ['context' => '', 'sources' => []];
        }

        try {
            $vector = '['.implode(',', $this->embeddingService->embed($query)).']';
            $limit = max(1, min(10, (int) config('ai.rag.result_limit', 5)));
            $maxDistance = max(0.0, min(2.0, (float) config('ai.rag.max_distance', 0.65)));
            $distanceMargin = max(0.0, min(1.0, (float) config('ai.rag.distance_margin', 0.18)));
            $candidatesQuery = DB::connection('pgsql')
                ->table('document_chunks')
                ->select(['document_name', 'course_id', 'content', 'page_number', 'chunk_index'])
                ->selectRaw('embedding::halfvec(3072) <=> ?::halfvec(3072) AS distance', [$vector])
                ->where('is_active', true)
                ->whereNotNull('embedding');
            $candidates = $this->documentAccess
                ->scopeForRetrieval($candidatesQuery, $user, $courseId, $accessibleCourseIds)
                ->orderBy('distance')
                ->limit($limit)
                ->get();
            $bestDistance = (float) ($candidates->first()?->distance ?? 2.0);
            $distanceCutoff = min($maxDistance, $bestDistance + $distanceMargin);
            $chunks = $candidates
                ->filter(fn ($chunk) => (float) $chunk->distance <= $distanceCutoff)
                ->values();

            if ($chunks->isEmpty()) {
                return ['context' => '', 'sources' => []];
            }

            $courseTitles = Course::whereIn('id', $chunks->pluck('course_id')->unique())
                ->pluck('title', 'id');
            $grouped = $chunks->groupBy(fn ($chunk) => implode('|', [
                (string) $chunk->course_id,
                $chunk->document_name,
                (string) ($chunk->page_number ?? 'unknown'),
            ]));
            $sources = [];
            $sections = [];

            foreach ($grouped as $items) {
                $first = $items->first();
                $label = 'S'.(count($sources) + 1);
                $pages = $items->pluck('page_number')->filter()->unique()->sort()->values()->all();
                $source = [
                    'label' => $label,
                    'document_name' => $first->document_name,
                    'course_title' => $first->course_id === null || (int) $first->course_id === 0
                        ? 'Toàn hệ thống'
                        : ($courseTitles[$first->course_id] ?? 'Khóa học'),
                    'pages' => $pages,
                ];
                $sources[] = $source;
                $pageText = $pages ? ' · Trang '.implode(', ', $pages) : '';
                $sections[] = "[{$label}] Tài liệu: {$source['document_name']} · Khóa học: {$source['course_title']}{$pageText}\n"
                    .$items->pluck('content')->map(fn ($content) => trim((string) $content))->implode("\n");
            }

            return [
                'context' => Str::limit(implode("\n\n---\n\n", $sections), (int) config('ai.rag.context_limit', 9000), ''),
                'sources' => $sources,
            ];
        } catch (\Throwable $e) {
            Log::warning('Không thể tìm kiếm ngữ cảnh pgvector cho chatbot', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return ['context' => '', 'sources' => []];
        }
    }
}
