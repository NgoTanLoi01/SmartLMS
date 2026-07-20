<?php

namespace Tests\Feature;

use App\Models\SharedDocument;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SharedDocumentStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new \RuntimeException('SharedDocumentStorageTest chỉ được phép chạy trên SQLite cô lập.');
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('shared_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('folder')->nullable();
            $table->string('visibility', 20);
            $table->string('disk', 40);
            $table->string('file_path', 1024);
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('file_size');
            $table->string('checksum', 64)->nullable();
            $table->timestamps();
        });

        config(['filesystems.shared_document_disk' => 'r2']);
        Storage::fake('r2');
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('shared_documents');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_teacher_can_upload_supported_document_to_r2(): void
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $this->actingAs($teacher)
            ->post(route('shared-documents.store'), [
                'files' => [UploadedFile::fake()->create('Giao an.pdf', 1024, 'application/pdf')],
                'description' => 'Giáo án dùng chung',
                'folder' => '  Giáo án  ',
                'visibility' => SharedDocument::VISIBILITY_TEACHERS,
            ])
            ->assertRedirect(route('shared-documents.index'));

        $document = SharedDocument::firstOrFail();

        $this->assertSame($teacher->id, $document->owner_id);
        $this->assertSame('Giáo án', $document->folder);
        $this->assertSame('pdf', $document->extension);
        $this->assertStringStartsWith("shared-documents/{$teacher->id}/", $document->file_path);
        Storage::disk('r2')->assertExists($document->file_path);
    }

    public function test_shared_document_is_downloadable_by_other_teacher_but_private_document_is_not(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $shared = $this->createDocument($owner, SharedDocument::VISIBILITY_TEACHERS, 'shared.pdf');
        $private = $this->createDocument($owner, SharedDocument::VISIBILITY_PRIVATE, 'private.pdf');

        $this->actingAs($otherTeacher)
            ->get(route('shared-documents.download', $shared))
            ->assertOk();

        $this->actingAs($otherTeacher)
            ->get(route('shared-documents.download', $private))
            ->assertForbidden();

        $this->actingAs($otherTeacher)
            ->get(route('shared-documents.preview', $shared))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($otherTeacher)
            ->get(route('shared-documents.preview', $private))
            ->assertForbidden();
    }

    public function test_supported_documents_are_previewed_inline_and_unsupported_files_are_rejected(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $image = $this->createDocument($owner, SharedDocument::VISIBILITY_PRIVATE, 'minh-hoa.webp');
        $unsupported = $this->createDocument($owner, SharedDocument::VISIBILITY_PRIVATE, 'tai-lieu.docx');

        $response = $this->actingAs($owner)
            ->get(route('shared-documents.preview', $image))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/webp')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringStartsWith('inline;', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $this->actingAs($owner)
            ->get(route('shared-documents.preview', $unsupported))
            ->assertNotFound();
    }

    public function test_other_teacher_cannot_update_or_delete_document(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $document = $this->createDocument($owner, SharedDocument::VISIBILITY_TEACHERS, 'protected.pdf');

        $this->actingAs($otherTeacher)
            ->patch(route('shared-documents.update', $document), [
                'title' => 'Đã chiếm quyền',
                'visibility' => SharedDocument::VISIBILITY_PRIVATE,
            ])
            ->assertForbidden();

        $this->actingAs($otherTeacher)
            ->delete(route('shared-documents.destroy', $document))
            ->assertForbidden();

        $this->assertDatabaseHas('shared_documents', ['id' => $document->id, 'title' => 'protected']);
        Storage::disk('r2')->assertExists($document->file_path);
    }

    public function test_owner_can_delete_document_and_object_from_r2(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $document = $this->createDocument($owner, SharedDocument::VISIBILITY_PRIVATE, 'delete-me.pdf');

        $this->actingAs($owner)
            ->delete(route('shared-documents.destroy', $document))
            ->assertRedirect();

        $this->assertDatabaseMissing('shared_documents', ['id' => $document->id]);
        Storage::disk('r2')->assertMissing($document->file_path);
    }

    public function test_student_cannot_access_shared_document_storage(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->actingAs($student)
            ->post(route('shared-documents.store'), [
                'files' => [UploadedFile::fake()->create('student.pdf', 20, 'application/pdf')],
                'visibility' => SharedDocument::VISIBILITY_TEACHERS,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('shared_documents', 0);
    }

    private function createDocument(User $owner, string $visibility, string $name): SharedDocument
    {
        $path = "shared-documents/{$owner->id}/{$name}";
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        Storage::disk('r2')->put($path, 'document-content');

        return SharedDocument::create([
            'owner_id' => $owner->id,
            'title' => pathinfo($name, PATHINFO_FILENAME),
            'visibility' => $visibility,
            'disk' => 'r2',
            'file_path' => $path,
            'original_name' => $name,
            'mime_type' => match ($extension) {
                'pdf' => 'application/pdf',
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                default => 'application/octet-stream',
            },
            'extension' => $extension,
            'file_size' => 16,
            'checksum' => hash('sha256', 'document-content'),
        ]);
    }
}
