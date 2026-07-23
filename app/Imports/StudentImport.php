<?php

namespace App\Imports;

use App\Models\Classroom;
use App\Models\User;
use App\Support\StudentLoginCode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use RuntimeException;

class StudentImport implements ToCollection, WithStartRow
{
    public const MODE_APPEND = 'append';

    public const MODE_REPLACE = 'replace';

    protected $classId;

    protected string $mode;

    protected bool $previewOnly;

    public int $processedCount = 0;

    public int $createdCount = 0;

    public int $updatedCount = 0;

    public int $syncedCount = 0;

    public int $skippedCount = 0;

    public int $detachedCount = 0;

    public array $studentsToDetach = [];

    public function __construct($classId, string $mode = self::MODE_APPEND, bool $previewOnly = false)
    {
        if (! in_array($mode, [self::MODE_APPEND, self::MODE_REPLACE], true)) {
            throw new InvalidArgumentException('Chế độ import học viên không hợp lệ.');
        }

        $this->classId = $classId;
        $this->mode = $mode;
        $this->previewOnly = $previewOnly;
    }

    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            $classroom = Classroom::query()->lockForUpdate()->find($this->classId);
            if (! $classroom) {
                throw new RuntimeException('Không tìm thấy lớp học cần import.');
            }

            $importedUserIds = [];

            foreach ($rows as $index => $row) {
                $maHs = trim((string) ($row[3] ?? ''));
                $ho = trim((string) ($row[4] ?? ''));
                $ten = trim((string) ($row[5] ?? ''));
                $fullName = trim(preg_replace('/\s+/', ' ', $ho.' '.$ten));

                if ($fullName === '' || $this->isHeaderRow($ho, $ten, $maHs)) {
                    $this->skippedCount++;

                    continue;
                }

                $this->processedCount++;
                $studentCode = StudentLoginCode::normalizeStudentCode($maHs);
                $rowNumber = $index + $this->startRow();
                $email = $this->importEmail($classroom, $studentCode, $fullName, $rowNumber);

                $user = $studentCode
                    ? $classroom->students()->where('student_code', $studentCode)->first()
                    : null;
                $user = $user ?: User::where('email', $email)->first();

                if ($user && ! $user->isStudent()) {
                    throw new RuntimeException("Dòng {$rowNumber} trùng email với tài khoản không phải học viên.");
                }

                if ($this->previewOnly) {
                    if ($user) {
                        $importedUserIds[] = $user->id;
                    }

                    continue;
                }

                if (! $user) {
                    $username = StudentLoginCode::generateFromName($fullName, $studentCode);
                    $user = User::create([
                        'name' => $fullName,
                        'username' => $username,
                        'student_code' => $studentCode,
                        'email' => $email,
                        'password' => Hash::make('123456'),
                        'role' => User::ROLE_STUDENT,
                    ]);
                    $this->createdCount++;
                } elseif (! $user->username) {
                    $user->update([
                        'username' => StudentLoginCode::generateFromName($fullName, $studentCode),
                        'student_code' => $user->student_code ?: $studentCode,
                    ]);
                    $this->updatedCount++;
                } elseif (! $user->student_code && $studentCode) {
                    $user->update(['student_code' => $studentCode]);
                    $this->updatedCount++;
                }

                $importedUserIds[] = $user->id;
            }

            $importedUserIds = collect($importedUserIds)->unique()->values()->all();
            $currentStudents = $classroom->students()->get(['users.id', 'users.name', 'users.student_code']);
            $studentsToDetach = $currentStudents->whereNotIn('id', $importedUserIds)->values();
            $this->detachedCount = $studentsToDetach->count();
            $this->studentsToDetach = $studentsToDetach->map(fn (User $student) => [
                'id' => $student->id,
                'name' => $student->name,
                'student_code' => $student->student_code,
            ])->all();

            if ($this->previewOnly) {
                return;
            }

            if ($this->mode === self::MODE_REPLACE) {
                $classroom->students()->sync($importedUserIds);
            } else {
                if ($importedUserIds !== []) {
                    $classroom->students()->syncWithoutDetaching($importedUserIds);
                }

                $this->detachedCount = 0;
                $this->studentsToDetach = [];
            }

            $this->syncedCount = count($importedUserIds);
        }, 3);
    }

    private function importEmail(Classroom $classroom, ?string $studentCode, string $fullName, int $rowNumber): string
    {
        $classCode = StudentLoginCode::normalizeStudentCode($classroom->code) ?: 'class'.$classroom->id;
        $studentKey = $studentCode ?: 'row'.$rowNumber.Str::slug($fullName, '');

        return $classCode.'.'.$studentKey.'@student.smartlms.local';
    }

    private function isHeaderRow(string $ho, string $ten, string $studentCode): bool
    {
        $headerText = Str::lower(Str::slug($ho.' '.$ten.' '.$studentCode, ''));

        return str_contains($headerText, 'ho')
            && str_contains($headerText, 'ten')
            && str_contains($headerText, 'mahs');
    }
}
