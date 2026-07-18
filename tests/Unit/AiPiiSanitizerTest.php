<?php

namespace Tests\Unit;

use App\Services\AiPiiSanitizer;
use PHPUnit\Framework\TestCase;

class AiPiiSanitizerTest extends TestCase
{
    public function test_it_redacts_email_and_phone_recursively(): void
    {
        $sanitizer = new AiPiiSanitizer;
        $result = $sanitizer->redactRecursive([
            'contact' => 'student@example.com - 0912 345 678',
            'nested' => ['teacher@example.edu.vn'],
        ]);

        $this->assertSame('[EMAIL_DA_AN] - [SO_DIEN_THOAI_DA_AN]', $result['contact']);
        $this->assertSame('[EMAIL_DA_AN]', $result['nested'][0]);
    }

    public function test_it_restores_internal_student_references(): void
    {
        $result = (new AiPiiSanitizer)->restoreReferences([
            'student' => 'HOC_VIEN_001',
            'summary' => 'HOC_VIEN_001 cần ôn tập.',
        ], ['HOC_VIEN_001' => 'Nguyễn Văn A']);

        $this->assertSame('Nguyễn Văn A', $result['student']);
        $this->assertSame('Nguyễn Văn A cần ôn tập.', $result['summary']);
    }
}
