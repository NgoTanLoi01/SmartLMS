<?php

namespace Tests\Benchmarks;

use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttendanceSaveBenchmarkTest extends TestCase
{
    private bool $capturing = false;

    /** @var array{queries:int,writes:int,selects:int,query_ms:float} */
    private array $metrics = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new \RuntimeException('Attendance benchmark chỉ được phép chạy trên SQLite cô lập.');
        }

        DB::listen(function (QueryExecuted $query): void {
            if (! $this->capturing) {
                return;
            }

            $verb = strtolower(strtok(ltrim($query->sql), " \t\n\r"));
            $this->metrics['queries']++;
            $this->metrics['query_ms'] += $query->time;
            if ($verb === 'select') {
                $this->metrics['selects']++;
            }
            if (in_array($verb, ['insert', 'update', 'delete', 'replace'], true)) {
                $this->metrics['writes']++;
            }
        });
    }

    protected function tearDown(): void
    {
        $this->dropSchema();
        parent::tearDown();
    }

    public function test_attendance_save_benchmark(): void
    {
        $results = [];
        $payloadMode = getenv('ATTENDANCE_BENCHMARK_PAYLOAD') ?: 'dirty';

        foreach ([30, 50, 100] as $studentCount) {
            $this->dropSchema();
            $this->createSchema();
            [$teacher, $courseId, $payload] = $this->seedDataset($studentCount, 8, $payloadMode);

            $this->metrics = ['queries' => 0, 'writes' => 0, 'selects' => 0, 'query_ms' => 0.0];
            $memoryBefore = memory_get_usage(true);
            $startedAt = hrtime(true);
            $this->capturing = true;

            $response = $this->actingAs($teacher)->post(route('attendance.save', $courseId), $payload);

            $this->capturing = false;
            $elapsedMs = (hrtime(true) - $startedAt) / 1_000_000;
            $response->assertRedirect()->assertSessionHas('success');

            $results[] = [
                'students' => $studentCount,
                'columns' => 8,
                'payload_mode' => $payloadMode,
                'submitted_cells' => collect($payload['data'])->sum(fn (array $users): int => count($users)),
                ...$this->metrics,
                'response_ms' => round($elapsedMs, 2),
                'memory_delta_kb' => round((memory_get_usage(true) - $memoryBefore) / 1024, 2),
            ];
        }

        fwrite(STDERR, "\nATTENDANCE_BENCHMARK=".json_encode($results, JSON_UNESCAPED_SLASHES)."\n");
        $this->assertCount(3, $results);
    }

    /** @return array{User,int,array<string,mixed>} */
    private function seedDataset(int $studentCount, int $columnCount, string $payloadMode): array
    {
        $now = now();
        $teacherId = DB::table('users')->insertGetId([
            'name' => 'Benchmark Teacher',
            'email' => 'benchmark-teacher@example.test',
            'password' => 'unused',
            'role' => User::ROLE_TEACHER,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $courseId = DB::table('courses')->insertGetId([
            'title' => 'Attendance Benchmark',
            'teacher_id' => $teacherId,
            'course_type' => 'delivery',
            'status' => 'published',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $classId = DB::table('classes')->insertGetId([
            'name' => 'Benchmark Class',
            'code' => 'BENCH-'.$studentCount,
            'teacher_id' => $teacherId,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('class_course')->insert([
            'class_id' => $classId,
            'course_id' => $courseId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $studentIds = [];
        foreach (range(1, $studentCount) as $number) {
            $studentIds[] = DB::table('users')->insertGetId([
                'name' => 'Student '.$number,
                'email' => "student-{$studentCount}-{$number}@example.test",
                'password' => 'unused',
                'role' => User::ROLE_STUDENT,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        DB::table('class_user')->insert(array_map(fn (int $studentId): array => [
            'class_id' => $classId,
            'user_id' => $studentId,
            'created_at' => $now,
            'updated_at' => $now,
        ], $studentIds));

        $columnIds = [];
        foreach (range(1, $columnCount) as $number) {
            $columnIds[] = DB::table('attendance_columns')->insertGetId([
                'course_id' => $courseId,
                'name' => 'Buổi '.$number,
                'type' => 'attendance',
                'order' => $number,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $existingRows = [];
        $data = [];
        $notes = [];
        foreach ($columnIds as $columnIndex => $columnId) {
            foreach ($studentIds as $studentIndex => $studentId) {
                $existingRows[] = [
                    'attendance_column_id' => $columnId,
                    'user_id' => $studentId,
                    'value' => 'present',
                    'note' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $changed = $columnIndex === $columnCount - 1 && $studentIndex % 10 === 0;
                if ($payloadMode === 'full' || $changed) {
                    $data[$columnId][$studentId] = $changed ? 'absent' : 'present';
                    $notes[$columnId][$studentId] = null;
                }
            }
        }
        DB::table('attendance_data')->insert($existingRows);

        return [User::findOrFail($teacherId), $courseId, ['data' => $data, 'notes' => $notes]];
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('teacher_id');
            $table->string('course_type');
            $table->string('status');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamps();
        });
        Schema::create('classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedBigInteger('teacher_id');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('class_user', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->unique(['class_id', 'user_id']);
        });
        Schema::create('class_course', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('course_id');
            $table->timestamps();
            $table->unique(['class_id', 'course_id']);
        });
        Schema::create('attendance_columns', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('name');
            $table->string('type');
            $table->integer('order');
            $table->timestamps();
        });
        Schema::create('attendance_data', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attendance_column_id');
            $table->unsignedBigInteger('user_id');
            $table->string('value')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['attendance_column_id', 'user_id']);
        });
        Schema::create('smart_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->string('action_url')->nullable();
            $table->json('data')->nullable();
            $table->string('dedupe_key')->nullable()->unique();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    private function dropSchema(): void
    {
        foreach ([
            'smart_notifications', 'attendance_data', 'attendance_columns',
            'class_course', 'class_user', 'classes', 'courses', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
}
