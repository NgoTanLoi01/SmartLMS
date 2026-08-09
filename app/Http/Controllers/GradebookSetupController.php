<?php

namespace App\Http\Controllers;

use App\Application\Gradebook\ConfigureGradebook;
use App\Domain\Gradebook\GradebookException;
use App\Http\Requests\Gradebook\StoreGradebookSetupRequest;
use App\Models\Course;
use App\Models\GradeItem;
use App\Services\GradebookMigrationService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class GradebookSetupController extends Controller
{
    public function create(Course $course, GradebookMigrationService $migration)
    {
        Gate::authorize('update', $course);
        $discovery = $migration->discover($course)['discovery'];
        $sources = $this->mergeOldInput($this->sources($discovery));

        return view('gradebook.setup', compact('course', 'sources'));
    }

    public function store(
        StoreGradebookSetupRequest $request,
        Course $course,
        ConfigureGradebook $configure,
    ) {
        $input = $request->validated();
        $dryRun = $input['mode'] === 'preview';
        $sessionKey = "gradebook_setup_preview_{$course->id}";

        try {
            $configured = $configure->handle($course, $request->user(), $input, true);
        } catch (GradebookException $exception) {
            return back()->withInput()->withErrors(['gradebook_setup' => $exception->getMessage()]);
        }

        if ($dryRun) {
            session()->put($sessionKey, $configured['checksum']);

            return back()->withInput()->with('gradebook_setup_preview', $configured['result']);
        }

        $approvedChecksum = (string) session()->get($sessionKey, '');
        if ($approvedChecksum === '' || ! hash_equals($approvedChecksum, $configured['checksum'])) {
            return back()->withInput()->withErrors([
                'gradebook_setup' => 'Cấu hình đã thay đổi hoặc chưa được xem trước. Hãy bấm “Kiểm tra trước” rồi áp dụng lại.',
            ]);
        }

        try {
            $configured = $configure->handle($course, $request->user(), $input, false);
        } catch (GradebookException $exception) {
            return back()->withInput()->withErrors(['gradebook_setup' => $exception->getMessage()]);
        }
        session()->forget($sessionKey);

        return redirect()
            ->route('gradebook.index', [$course, 'period_id' => $configured['period']->id])
            ->with('success', 'Đã tạo kỳ điểm, mapping nguồn và đồng bộ dữ liệu hiện có.');
    }

    /**
     * @param  array<string,mixed>  $discovery
     * @return array<int,array<string,mixed>>
     */
    private function sources(array $discovery): array
    {
        $legacy = collect($discovery['legacy_grade_columns'])->map(function (array $source): array {
            $type = $source['suggested_item_type'] ?? GradeItem::TYPE_MANUAL;

            return [
                ...$source,
                'enabled' => $type !== GradeItem::TYPE_MANUAL,
                'item_type' => $type,
                'category_code' => $type === GradeItem::TYPE_EXAM ? 'exam' : 'process',
                'item_weight' => $type === GradeItem::TYPE_HS2 ? 2 : 1,
                'code' => $this->sourceCode('legacy', $source['source_id'], $source['name']),
                'absence_policy' => 'missing',
                'attempt_policy' => null,
                'source_label' => 'Điểm danh',
            ];
        });
        $assignments = collect($discovery['assignments'])->map(fn (array $source): array => [
            ...$source,
            'enabled' => false,
            'item_type' => GradeItem::TYPE_ASSIGNMENT,
            'category_code' => 'process',
            'item_weight' => 1,
            'code' => $this->sourceCode('assignment', $source['source_id'], $source['name']),
            'absence_policy' => null,
            'attempt_policy' => null,
            'source_label' => 'Bài tập',
        ]);
        $quizzes = collect($discovery['quizzes'])->map(fn (array $source): array => [
            ...$source,
            'enabled' => false,
            'item_type' => GradeItem::TYPE_QUIZ,
            'category_code' => 'process',
            'item_weight' => 1,
            'code' => $this->sourceCode('quiz', $source['source_id'], $source['name']),
            'absence_policy' => null,
            'attempt_policy' => 'highest_released',
            'source_label' => 'Trắc nghiệm/Thi',
        ]);

        return $legacy->concat($assignments)->concat($quizzes)->values()->all();
    }

    /**
     * Rehydrate editable old input with authoritative discovery metadata after validation redirects.
     *
     * @param  array<int,array<string,mixed>>  $sources
     * @return array<int,array<string,mixed>>
     */
    private function mergeOldInput(array $sources): array
    {
        $oldItems = session()->getOldInput('items');
        if (! is_array($oldItems)) {
            return $sources;
        }

        $editable = [
            'enabled', 'code', 'name', 'category_code', 'item_type', 'item_weight',
            'absence_policy', 'attempt_policy',
        ];
        $oldBySource = collect($oldItems)->mapWithKeys(function ($item): array {
            if (! is_array($item) || empty($item['source_type']) || empty($item['source_id'])) {
                return [];
            }

            return [$item['source_type'].':'.(int) $item['source_id'] => $item];
        });

        return collect($sources)->map(function (array $source) use ($editable, $oldBySource): array {
            $old = $oldBySource->get($source['source_type'].':'.(int) $source['source_id']);
            if (! is_array($old)) {
                return $source;
            }

            return [...$source, ...array_intersect_key($old, array_flip($editable))];
        })->values()->all();
    }

    private function sourceCode(string $prefix, int $id, string $name): string
    {
        return Str::limit($prefix.'-'.$id.'-'.Str::slug($name), 100, '');
    }
}
