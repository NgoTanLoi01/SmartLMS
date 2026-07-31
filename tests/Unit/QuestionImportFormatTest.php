<?php

namespace Tests\Unit;

use App\Imports\QuestionImport;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuestionImportFormatTest extends TestCase
{
    #[Test]
    public function incomplete_question_row_is_rejected_before_database_writes(): void
    {
        $import = new QuestionImport(1, 1);

        try {
            $import->collection(collect([
                collect(['Câu hỏi', 'easy', 'A']),
            ]));
            $this->fail('Malformed row was accepted.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('đủ 7 cột', $exception->errors()['file'][0]);
            $this->assertSame(0, $import->importedCount);
        }
    }

    #[Test]
    public function invalid_difficulty_is_rejected_before_database_writes(): void
    {
        $import = new QuestionImport(1, 1);

        try {
            $import->collection(collect([
                collect(['Câu hỏi', 'unknown', 'A', 'B', 'C', 'D', 'A']),
            ]));
            $this->fail('Invalid difficulty was accepted.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('easy, medium hoặc hard', $exception->errors()['file'][0]);
            $this->assertSame(0, $import->importedCount);
        }
    }
}
