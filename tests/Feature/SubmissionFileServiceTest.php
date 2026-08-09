<?php

namespace Tests\Feature;

use App\Services\SubmissionFileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionFileServiceTest extends TestCase
{
    public function test_store_verifies_private_upload_and_returns_checksum_metadata(): void
    {
        Storage::fake('r2');
        config(['filesystems.submission_disk' => 'r2']);
        $file = UploadedFile::fake()->createWithContent('answer.txt', 'stable submission content');

        $stored = app(SubmissionFileService::class)->store($file, 42, 7);

        $this->assertSame('r2', $stored['disk']);
        $this->assertSame('answer.txt', $stored['original_filename']);
        $this->assertSame(hash('sha256', 'stable submission content'), $stored['checksum_sha256']);
        $this->assertStringStartsWith('assignments/42/students/7/', $stored['path']);
        Storage::disk('r2')->assertExists($stored['path']);
    }
}
