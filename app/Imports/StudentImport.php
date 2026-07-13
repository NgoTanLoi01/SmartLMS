<?php

namespace App\Imports;

use App\Models\Classroom;
use App\Models\User;
use App\Support\StudentLoginCode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class StudentImport implements ToCollection, WithStartRow
{
    protected $classId;

    public int $processedCount = 0;

    public int $createdCount = 0;

    public int $updatedCount = 0;

    public int $syncedCount = 0;

    public int $skippedCount = 0;

    public function __construct($classId)
    {
        $this->classId = $classId;
    }

    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $rows)
    {
        $classroom = Classroom::find($this->classId);
        if (! $classroom) {
            return;
        }

        $importedUserIds = [];

        foreach ($rows as $index => $row) {
            $maHs = trim((string) ($row[3] ?? ''));
            $ho = trim((string) ($row[4] ?? ''));
            $ten = trim((string) ($row[5] ?? ''));
            $fullName = $ho.' '.$ten;
            $fullName = trim(preg_replace('/\s+/', ' ', $fullName));

            if ($fullName === '' || $this->isHeaderRow($ho, $ten, $maHs)) {
                $this->skippedCount++;

                continue;
            }

            $this->processedCount++;
            $studentCode = StudentLoginCode::normalizeStudentCode($maHs);
            $rowNumber = $index + $this->startRow();
            $email = $this->importEmail($classroom, $studentCode, $fullName, $rowNumber);

            $userQuery = User::where('email', $email);
            if ($studentCode) {
                $user = $classroom->students()
                    ->where('student_code', $studentCode)
                    ->first();
            } else {
                $user = null;
            }

            $user = $user ?: $userQuery->first();

            if (! $user) {
                $username = StudentLoginCode::generateFromName($fullName, $studentCode);
                $user = User::create([
                    'name' => $fullName,
                    'username' => $username,
                    'student_code' => $studentCode,
                    'email' => $email,
                    'password' => Hash::make('123456'), // Mặc định pass là 123456
                    'role' => 'student',
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
        if (! empty($importedUserIds)) {
            $classroom->students()->sync($importedUserIds);
            $this->syncedCount = count($importedUserIds);
        }
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
