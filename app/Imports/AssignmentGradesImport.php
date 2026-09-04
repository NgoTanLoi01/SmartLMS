<?php

namespace App\Imports;

use App\Models\Assignments;
use App\Models\AssignmentSubmission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class AssignmentGradesImport implements ToCollection, WithStartRow
{
    public int $updatedCount = 0;

    /** @var array<int, AssignmentSubmission> */
    public array $publishedSubmissions = [];

    public function __construct(private readonly Assignments $assignment) {}

    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $rows): void
    {
        if ($rows->count() > 1000) {
            throw ValidationException::withMessages([
                'file' => 'File điểm không được vượt quá 1.000 dòng.',
            ]);
        }

        $submissions = AssignmentSubmission::query()
            ->with('user:id,name,email,student_code')
            ->where('assignment_id', $this->assignment->id)
            ->get()
            ->keyBy('id');
        $plans = [];
        $errors = [];
        $seenIds = [];
        $scale = (float) ($this->assignment->grading_scale ?: 10);

        foreach ($rows->values() as $offset => $row) {
            $rowNumber = $offset + 2;
            $values = collect($row)->values();
            if ($values->filter(fn ($value) => trim((string) $value) !== '')->isEmpty()) {
                continue;
            }

            $submissionId = filter_var($values->get(0), FILTER_VALIDATE_INT);
            if (! $submissionId || ! $submissions->has($submissionId)) {
                $errors[] = "Dòng {$rowNumber}: ID bài nộp không thuộc bài tập này.";

                continue;
            }
            if (isset($seenIds[$submissionId])) {
                $errors[] = "Dòng {$rowNumber}: ID bài nộp bị lặp.";

                continue;
            }
            $seenIds[$submissionId] = true;

            $submission = $submissions->get($submissionId);
            if (! $this->identityMatches($submission, $values->get(1), $values->get(3))) {
                $errors[] = "Dòng {$rowNumber}: mã học viên hoặc email không khớp với bài nộp.";

                continue;
            }

            $rawGrade = trim((string) $values->get(4));
            if ($rawGrade === '') {
                continue;
            }
            if (! is_numeric(str_replace(',', '.', $rawGrade))) {
                $errors[] = "Dòng {$rowNumber}: điểm phải là một số.";

                continue;
            }

            $grade = (float) str_replace(',', '.', $rawGrade);
            if ($grade < 0 || $grade > $scale) {
                $errors[] = "Dòng {$rowNumber}: điểm phải từ 0 đến {$scale}.";

                continue;
            }

            $status = $this->normalizeStatus($values->get(5));
            if ($status === null) {
                $errors[] = "Dòng {$rowNumber}: trạng thái chỉ nhận Nháp hoặc Công bố.";

                continue;
            }

            $feedback = $this->normalizeText($values->get(6));
            if (mb_strlen($feedback) > 5000) {
                $errors[] = "Dòng {$rowNumber}: nhận xét không được vượt quá 5.000 ký tự.";

                continue;
            }

            $plans[] = compact('submission', 'grade', 'status', 'feedback');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'file' => implode(' ', array_slice($errors, 0, 10)),
            ]);
        }

        DB::transaction(function () use ($plans): void {
            foreach ($plans as $plan) {
                /** @var AssignmentSubmission $submission */
                $submission = $plan['submission'];
                $changed = $submission->grade === null
                    || abs((float) $submission->grade - $plan['grade']) > 0.00001
                    || (string) $submission->feedback !== $plan['feedback']
                    || $submission->grading_status !== $plan['status'];

                if (! $changed) {
                    continue;
                }

                $submission->update([
                    'grade' => $plan['grade'],
                    'feedback' => $plan['feedback'],
                    'grading_status' => $plan['status'],
                    'grade_published_at' => $plan['status'] === AssignmentSubmission::GRADING_PUBLISHED ? now() : null,
                ]);
                $this->updatedCount++;

                if ($plan['status'] === AssignmentSubmission::GRADING_PUBLISHED) {
                    $this->publishedSubmissions[] = $submission->fresh(['assignment', 'user']);
                }
            }
        });
    }

    private function identityMatches(AssignmentSubmission $submission, mixed $studentCode, mixed $email): bool
    {
        $studentCode = trim((string) $studentCode);
        $email = trim((string) $email);

        if ($studentCode !== '' && trim((string) $submission->user?->student_code) !== $studentCode) {
            return false;
        }

        return $email === '' || Str::lower((string) $submission->user?->email) === Str::lower($email);
    }

    private function normalizeStatus(mixed $value): ?string
    {
        $status = Str::lower(Str::ascii(trim((string) $value)));

        return match ($status) {
            '', 'nhap', 'draft' => AssignmentSubmission::GRADING_DRAFT,
            'cong bo', 'da cong bo', 'published', 'publish' => AssignmentSubmission::GRADING_PUBLISHED,
            default => null,
        };
    }

    private function normalizeText(mixed $value): string
    {
        $text = trim((string) $value);

        return preg_match("/^'[=+\\-@]/", $text) === 1 ? mb_substr($text, 1) : $text;
    }
}
