<?php

namespace Tests\Feature;

use App\Imports\StudentImport;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudentImportIntegrityTest extends TestCase
{
    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new \RuntimeException('StudentImportIntegrityTest chỉ được phép chạy trên SQLite cô lập.');
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable()->unique();
            $table->string('student_code')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedBigInteger('teacher_id');
            $table->string('status')->default(Classroom::STATUS_ACTIVE);
            $table->timestamps();
        });
        Schema::create('class_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->unique(['class_id', 'user_id']);
        });

        $teacher = $this->createUser('Giáo viên', 'teacher@example.com', User::ROLE_TEACHER);
        $this->classroom = Classroom::create([
            'name' => 'Lớp kiểm thử',
            'code' => 'TEST-01',
            'teacher_id' => $teacher->id,
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('class_user');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_append_mode_keeps_existing_roster(): void
    {
        $existing = $this->createStudent('Học viên cũ', 'HS001');
        $this->classroom->students()->attach($existing);

        $import = new StudentImport($this->classroom->id, StudentImport::MODE_APPEND);
        $import->collection($this->rows([
            ['HS002', 'Nguyễn', 'Mới'],
        ]));

        $this->assertTrue($this->classroom->students()->whereKey($existing->id)->exists());
        $this->assertTrue($this->classroom->students()->where('student_code', 'hs002')->exists());
        $this->assertSame(0, $import->detachedCount);
        $this->assertDatabaseCount('class_user', 2);
    }

    public function test_replace_preview_lists_exact_students_without_writing(): void
    {
        $kept = $this->createStudent('Học viên giữ lại', 'HS001');
        $removed = $this->createStudent('Học viên sẽ gỡ', 'HS002');
        $this->classroom->students()->attach([$kept->id, $removed->id]);

        $preview = new StudentImport($this->classroom->id, StudentImport::MODE_REPLACE, true);
        $preview->collection($this->rows([
            ['HS001', 'Học viên', 'giữ lại'],
            ['HS003', 'Học viên', 'mới'],
        ]));

        $this->assertSame(1, $preview->detachedCount);
        $this->assertSame($removed->id, $preview->studentsToDetach[0]['id']);
        $this->assertDatabaseMissing('users', ['student_code' => 'hs003']);
        $this->assertDatabaseCount('class_user', 2);
    }

    public function test_replace_mode_detaches_only_students_missing_from_file(): void
    {
        $kept = $this->createStudent('Học viên giữ lại', 'HS001');
        $removed = $this->createStudent('Học viên sẽ gỡ', 'HS002');
        $this->classroom->students()->attach([$kept->id, $removed->id]);

        $import = new StudentImport($this->classroom->id, StudentImport::MODE_REPLACE);
        $import->collection($this->rows([
            ['HS001', 'Học viên', 'giữ lại'],
            ['HS003', 'Học viên', 'mới'],
        ]));

        $this->assertTrue($this->classroom->students()->whereKey($kept->id)->exists());
        $this->assertFalse($this->classroom->students()->whereKey($removed->id)->exists());
        $this->assertTrue($this->classroom->students()->where('student_code', 'hs003')->exists());
        $this->assertSame(1, $import->detachedCount);
        $this->assertDatabaseCount('class_user', 2);
    }

    public function test_import_rolls_back_all_rows_when_one_row_fails(): void
    {
        DB::statement(<<<'SQL'
CREATE TRIGGER reject_failed_student
BEFORE INSERT ON users
WHEN NEW.name = 'Lỗi Giao dịch'
BEGIN
    SELECT RAISE(ABORT, 'forced import failure');
END
SQL);

        $import = new StudentImport($this->classroom->id, StudentImport::MODE_APPEND);

        try {
            $import->collection($this->rows([
                ['HS010', 'Học viên', 'Hợp lệ'],
                ['HS011', 'Lỗi', 'Giao dịch'],
            ]));
            $this->fail('Import phải ném lỗi ở dòng thứ hai.');
        } catch (QueryException) {
            $this->assertDatabaseMissing('users', ['student_code' => 'hs010']);
            $this->assertDatabaseMissing('users', ['student_code' => 'hs011']);
            $this->assertDatabaseCount('class_user', 0);
        }
    }

    private function createStudent(string $name, string $studentCode): User
    {
        $studentCode = strtolower($studentCode);

        return $this->createUser($name, $studentCode.'@example.com', User::ROLE_STUDENT, $studentCode);
    }

    private function createUser(string $name, string $email, string $role, ?string $studentCode = null): User
    {
        return User::create([
            'name' => $name,
            'username' => strtolower(str_replace(' ', '', $name)).uniqid(),
            'student_code' => $studentCode,
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function rows(array $students): Collection
    {
        return collect($students)->map(fn (array $student) => collect([
            null, null, null, $student[0], $student[1], $student[2],
        ]));
    }
}
