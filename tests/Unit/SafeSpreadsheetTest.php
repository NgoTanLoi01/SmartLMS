<?php

namespace Tests\Unit;

use App\Rules\SafeSpreadsheet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SafeSpreadsheetTest extends TestCase
{
    public function test_valid_csv_is_accepted(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'questions.csv',
            "question,difficulty,a,b,c,d,correct\n2 + 2,easy,3,4,5,6,B\n"
        );

        $validator = Validator::make(['file' => $file], ['file' => [new SafeSpreadsheet]]);

        $this->assertFalse($validator->fails());
    }

    public function test_renamed_executable_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent('questions.xlsx', "#!/bin/sh\necho unsafe\n");

        $validator = Validator::make(['file' => $file], ['file' => [new SafeSpreadsheet]]);

        $this->assertTrue($validator->fails());
    }
}
