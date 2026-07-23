<?php

namespace Tests\Unit;

use App\Models\Assignments;
use App\Models\Lesson;
use App\Services\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_it_removes_active_content_event_handlers_and_unsafe_urls(): void
    {
        $html = <<<'HTML'
<p onclick="alert(1)">Nội dung<script>alert(2)</script></p>
<iframe src="https://evil.example"></iframe>
<a href="java&#x0A;script:alert(3)" target="_blank">Liên kết</a>
<img src="javascript:alert(4)" onerror="alert(5)">
<span style="color: red; background-image: url(javascript:alert(6)); width: 50%">An toàn</span>
HTML;

        $result = (new HtmlSanitizer)->sanitize($html);

        $this->assertStringNotContainsString('<script', $result);
        $this->assertStringNotContainsString('<iframe', $result);
        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringNotContainsString('onerror', $result);
        $this->assertStringNotContainsString('javascript:', strtolower($result));
        $this->assertStringNotContainsString('background-image', $result);
        $this->assertStringContainsString('style="color: red; width: 50%"', $result);
    }

    public function test_it_preserves_supported_learning_content_and_secures_new_tabs(): void
    {
        $html = <<<'HTML'
<h2>Chủ đề</h2><p style="text-align: center; font-size: 18px" class="editor-only">Mô tả</p>
<ul><li>Mục 1</li></ul><table border="1"><tbody><tr><td colspan="2">Ô</td></tr></tbody></table>
<a href="https://example.edu/tai-lieu" target="_blank">Tài liệu</a>
<img src="https://cdn.example.edu/image.png" alt="Minh họa" width="640">
HTML;

        $result = (new HtmlSanitizer)->sanitize($html);

        $this->assertStringContainsString('<h2>Chủ đề</h2>', $result);
        $this->assertStringContainsString('<table border="1">', $result);
        $this->assertStringContainsString('colspan="2"', $result);
        $this->assertStringContainsString('href="https://example.edu/tai-lieu"', $result);
        $this->assertStringContainsString('rel="noopener noreferrer"', $result);
        $this->assertStringContainsString('src="https://cdn.example.edu/image.png"', $result);
        $this->assertStringNotContainsString('class=', $result);
    }

    public function test_lesson_and_assignment_models_sanitize_html_on_read_and_write(): void
    {
        $lesson = new Lesson;
        $lesson->setRawAttributes(['content' => '<p onclick="alert(1)">Bài học</p>']);

        $assignment = new Assignments;
        $assignment->instructions = '<p>Bài tập<img src="x" onerror="alert(2)"></p>';

        $emptyAssignment = new Assignments;
        $emptyAssignment->instructions = null;

        $this->assertSame('<p>Bài học</p>', $lesson->content);
        $this->assertStringNotContainsString('onerror', $assignment->getAttributes()['instructions']);
        $this->assertNull($emptyAssignment->getAttributes()['instructions']);
    }
}
