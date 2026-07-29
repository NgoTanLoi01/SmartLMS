<?php

namespace Tests\Unit;

use App\Services\HtmlCssCodeFormatter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HtmlCssCodeFormatterTest extends TestCase
{
    #[Test]
    public function it_formats_compact_html_and_css_without_correcting_the_exercise_error(): void
    {
        $source = "<!DOCTYPE html><html><head><style>.container { display: grid; grid-template-columns: 1fr 1fr 1fr; }.a { grid-column: 2 / 4; }.b { grid-column: 1; }</style></head><body><div class='container'><div class='a'>A</div><div class='b'>B</div></div></body></html>";

        $formatted = (new HtmlCssCodeFormatter)->format($source);

        $this->assertStringContainsString("<!DOCTYPE html>\n<html>\n  <head>\n    <style>", $formatted);
        $this->assertStringContainsString(".container {\n        display: grid;", $formatted);
        $this->assertStringContainsString('grid-column: 2 / 4;', $formatted);
        $this->assertStringContainsString("<div class='a'>\n        A\n      </div>", $formatted);
    }

    #[Test]
    public function it_preserves_quotes_comments_and_invalid_css_that_students_must_fix(): void
    {
        $source = '<style>/* Keep ; } */ .button { content: ";}"; color red; }</style><button class="button">Lưu</button>';

        $formatted = (new HtmlCssCodeFormatter)->format($source);

        $this->assertStringContainsString('/* Keep ; } */', $formatted);
        $this->assertStringContainsString('content: ";}";', $formatted);
        $this->assertStringContainsString('color red;', $formatted);
        $this->assertStringNotContainsString('color: red;', $formatted);
    }
}
