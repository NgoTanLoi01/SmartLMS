<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserDeletionGuard
{
    private const OWNED_DATA = [
        'courses' => ['teacher_id', 'khóa học'],
        'classes' => ['teacher_id', 'lớp học'],
        'learning_programs' => ['teacher_id', 'chương trình học'],
        'question_banks' => ['teacher_id', 'ngân hàng câu hỏi'],
        'teaching_records' => ['teacher_id', 'dữ liệu giảng dạy'],
        'teaching_contracts' => ['teacher_id', 'hợp đồng/thanh toán'],
        'shared_documents' => ['owner_id', 'tài liệu dùng chung'],
        'learning_materials' => ['uploaded_by', 'học liệu đã tải lên'],
    ];

    public function blockers(User $user): array
    {
        if (! $user->isTeacher()) {
            return [];
        }

        $blockers = [];

        foreach (self::OWNED_DATA as $table => [$ownerColumn, $label]) {
            if (Schema::hasTable($table)
                && Schema::hasColumn($table, $ownerColumn)
                && DB::table($table)->where($ownerColumn, $user->id)->exists()) {
                $blockers[] = $label;
            }
        }

        return $blockers;
    }
}
