<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Classroom;
use App\Support\StudentLoginCode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str; 
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class StudentImport implements ToCollection, WithStartRow
{
    protected $classId;

    public function __construct($classId)
    {
        $this->classId = $classId;
    }

    public function startRow(): int
    {
        return 5;
    }

    public function collection(Collection $rows)
    {
        $classroom = Classroom::find($this->classId);
        if (!$classroom) {
            return;
        }

        foreach ($rows as $row) {
            // Nếu cột Mã HS Nghề (index 3) bị rỗng thì bỏ qua
            if (!isset($row[3])) {
                continue;
            }

            $maHs = trim($row[3]);
            $ho = trim($row[4]);
            $ten = trim($row[5]);
            $fullName = $ho . ' ' . $ten;

            $studentCode = StudentLoginCode::normalizeStudentCode($maHs);
            $emailPrefix = $studentCode ?: Str::slug($fullName, '');
            $email = $emailPrefix . '@student.smartlms.local';

            $userQuery = User::where('email', $email);
            if ($studentCode) {
                $userQuery->orWhere('student_code', $studentCode);
            }

            $user = $userQuery->first();

            if (!$user) {
                $username = StudentLoginCode::generateFromName($fullName, $studentCode);
                $user = User::create([
                    'name' => $fullName,
                    'username' => $username,
                    'student_code' => $studentCode,
                    'email' => $email,
                    'password' => Hash::make('123456'), // Mặc định pass là 123456
                    'role' => 'student',
                ]);
            } elseif (!$user->username) {
                $user->update([
                    'username' => StudentLoginCode::generateFromName($fullName, $studentCode),
                    'student_code' => $user->student_code ?: $studentCode,
                ]);
            } elseif (!$user->student_code && $studentCode) {
                $user->update(['student_code' => $studentCode]);
            }

            $classroom->students()->syncWithoutDetaching([$user->id]);
        }
    }
}
