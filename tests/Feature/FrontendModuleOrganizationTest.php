<?php

namespace Tests\Feature;

use Tests\TestCase;

class FrontendModuleOrganizationTest extends TestCase
{
    public function test_attendance_uses_a_guarded_vite_module_without_inline_handlers(): void
    {
        $view = file_get_contents(resource_path('views/attendance/show.blade.php'));
        $module = file_get_contents(resource_path('js/pages/attendance.js'));
        $viteConfig = file_get_contents(base_path('vite.config.js'));

        $this->assertStringContainsString("@vite('resources/js/pages/attendance.js')", $view);
        $this->assertStringContainsString("'resources/js/pages/attendance.js'", $viteConfig);
        $this->assertStringNotContainsString('onclick=', $view);
        $this->assertStringNotContainsString('onblur=', $view);
        $this->assertStringNotContainsString('<script src="https://cdn.jsdelivr.net/npm/axios', $view);
        $this->assertStringContainsString("if (!page || page.dataset.attendanceInitialized === 'true') return;", $module);
        $this->assertStringContainsString("page.dataset.attendanceInitialized = 'true';", $module);
    }
}
