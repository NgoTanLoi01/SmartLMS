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
            $table->string('student_code')->nullable()->after('username')->index();
        });

        $usedUsernames = [];
        $students = DB::table('users')
            ->where('role', 'student')
            ->orderBy('id')
            ->get(['id', 'name', 'email']);

        foreach ($students as $student) {
            $studentCode = $this->studentCodeFromEmail($student->email);
            $username = $this->friendlyUsername($student->name, $studentCode, $usedUsernames);

            DB::table('users')->where('id', $student->id)->update([
                'username' => $username,
                'student_code' => $studentCode,
            ]);

            $usedUsernames[$username] = true;
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('student_code');
        });
    }

    private function friendlyUsername(string $name, ?string $studentCode, array $usedUsernames): string
    {
        $base = Str::slug($name, '') ?: 'hocsinh';
        $base = Str::lower($base);

        if (! isset($usedUsernames[$base]) && ! DB::table('users')->where('username', $base)->exists()) {
            return $base;
        }

        if ($studentCode) {
            $withCode = $base.'-'.Str::lower($studentCode);
            if (! isset($usedUsernames[$withCode]) && ! DB::table('users')->where('username', $withCode)->exists()) {
                return $withCode;
            }
        }

        $sequence = 2;
        do {
            $candidate = sprintf('%s-%02d', $base, $sequence);
            $sequence++;
        } while (isset($usedUsernames[$candidate]) || DB::table('users')->where('username', $candidate)->exists());

        return $candidate;
    }

    private function studentCodeFromEmail(?string $email): ?string
    {
        if (! $email || ! Str::endsWith($email, '@student.smartlms.local')) {
            return null;
        }

        $code = Str::before($email, '@student.smartlms.local');
        $code = preg_replace('/[^a-zA-Z0-9]/', '', $code);

        return $code ?: null;
    }
};
