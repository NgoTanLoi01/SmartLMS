<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Classroom;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str; // <-- Import thư viện xử lý chuỗi của Laravel
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

        foreach ($rows as $row) {
            // Nếu cột Mã HS Nghề (index 3) bị rỗng thì bỏ qua
            if (!isset($row[3])) {
                continue;
            }

            $maHs = trim($row[3]);
            $ho = trim($row[4]);
            $ten = trim($row[5]);
            $fullName = $ho . ' ' . $ten;

            // 1. Tạo email ảo: Chuyển "Nguyễn Gia Bảo" -> "nguyengiabao"
            // Hàm Str::slug mặc định sẽ bỏ dấu tiếng Việt, tham số '' giúp xóa khoảng trắng
            $emailPrefix = Str::slug($fullName, '');
            $email = $emailPrefix . '@gmail.com';

            // 2. Kiểm tra xem user này đã có trên hệ thống chưa
            $user = User::where('email', $email)->first();

            // 3. Nếu chưa có thì tạo mới
            if (!$user) {
                $user = User::create([
                    'name' => $fullName,
                    'email' => $email,
                    'password' => Hash::make('123456'), // Mặc định pass là 123456
                    'role' => 'student',
                ]);
            }

            // 4. Gán học sinh này vào lớp
            $classroom->students()->syncWithoutDetaching([$user->id]);
        }
    }
}
