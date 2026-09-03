<?php

namespace Tests\Unit;

use App\Support\AssignmentUploadTypes;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AssignmentUploadTypesTest extends TestCase
{
    public function test_default_extensions_never_allow_php(): void
    {
        $this->assertNotContains('php', AssignmentUploadTypes::safeExtensions(null));
        $this->assertStringNotContainsString('php', AssignmentUploadTypes::DEFAULT);
    }

    public function test_legacy_php_extension_is_filtered_before_upload_validation(): void
    {
        $this->assertSame(
            ['pdf', 'js', 'png'],
            AssignmentUploadTypes::safeExtensions('pdf,php,PHP,js,png')
        );
    }

    public function test_assignment_configuration_rejects_php(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AssignmentUploadTypes::normalize('pdf,php');
    }

    public function test_powerpoint_excel_and_archive_formats_are_available(): void
    {
        $extensions = AssignmentUploadTypes::safeExtensions(null);

        $this->assertContains('ppt', $extensions);
        $this->assertContains('pptx', $extensions);
        $this->assertContains('xls', $extensions);
        $this->assertContains('xlsx', $extensions);
        $this->assertContains('zip', $extensions);
        $this->assertContains('rar', $extensions);
    }

    public function test_checkbox_array_is_normalized_for_database_storage(): void
    {
        $this->assertSame(
            'ppt,pptx,pdf',
            AssignmentUploadTypes::normalize(['.PPT', 'pptx', 'PDF', 'pptx'])
        );
    }
}
