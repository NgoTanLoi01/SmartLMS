<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        $classes = DB::table('classes')->orderBy('id')->get(['id', 'code']);
        $usedUsernames = [];

        foreach ($classes as $classroom) {
            $prefix = $this->normalizePrefix($classroom->code ?: 'HS');
            $sequence = 1;

            $students = DB::table('class_user')
                ->join('users', 'class_user.user_id', '=', 'users.id')
                ->where('class_user.class_id', $classroom->id)
                ->where('users.role', 'student')
                ->orderBy('users.name')
                ->get(['users.id', 'users.username']);

            foreach ($students as $student) {
                if ($student->username) {
                    $usedUsernames[$student->username] = true;
                    continue;
                }

                do {
                    $username = sprintf('%s-%02d', $prefix, $sequence);
                    $sequence++;
                } while (isset($usedUsernames[$username]) || DB::table('users')->where('username', $username)->exists());

                DB::table('users')->where('id', $student->id)->update(['username' => $username]);
                $usedUsernames[$username] = true;
            }
        }

        $studentsWithoutClass = DB::table('users')
            ->where('role', 'student')
            ->whereNull('username')
            ->orderBy('name')
            ->get(['id']);

        $sequence = 1;
        foreach ($studentsWithoutClass as $student) {
            do {
                $username = sprintf('HS-%02d', $sequence);
                $sequence++;
            } while (isset($usedUsernames[$username]) || DB::table('users')->where('username', $username)->exists());

            DB::table('users')->where('id', $student->id)->update(['username' => $username]);
            $usedUsernames[$username] = true;
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }

    private function normalizePrefix(string $prefix): string
    {
        $normalized = Str::upper(Str::slug($prefix, ''));
        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized) ?: 'HS';

        return Str::limit($normalized, 12, '');
    }
};
