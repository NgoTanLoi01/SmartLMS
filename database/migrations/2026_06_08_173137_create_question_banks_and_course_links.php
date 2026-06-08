<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('course_question_bank', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_bank_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['course_id', 'question_bank_id']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('question_bank_id')->nullable()->after('course_id')->constrained()->nullOnDelete();
        });

        $courses = DB::table('courses')->select('id', 'title', 'teacher_id')->get();

        foreach ($courses as $course) {
            $hasQuestions = DB::table('questions')->where('course_id', $course->id)->exists();
            if (!$hasQuestions) {
                continue;
            }

            $bankId = DB::table('question_banks')->insertGetId([
                'name' => $course->title,
                'description' => 'Ngân hàng câu hỏi được tạo từ khóa học ' . $course->title,
                'teacher_id' => $course->teacher_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('course_question_bank')->insertOrIgnore([
                'course_id' => $course->id,
                'question_bank_id' => $bankId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('questions')
                ->where('course_id', $course->id)
                ->whereNull('question_bank_id')
                ->update(['question_bank_id' => $bankId]);
        }
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['question_bank_id']);
            $table->dropColumn('question_bank_id');
        });

        Schema::dropIfExists('course_question_bank');
        Schema::dropIfExists('question_banks');
    }
};
