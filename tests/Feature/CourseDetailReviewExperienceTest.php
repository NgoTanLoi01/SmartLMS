<?php

namespace Tests\Feature;

use Tests\TestCase;

class CourseDetailReviewExperienceTest extends TestCase
{
    public function test_course_detail_is_a_review_library_without_lesson_completion_ui(): void
    {
        $view = file_get_contents(resource_path('views/courses/show.blade.php'));
        $sidebar = file_get_contents(resource_path('views/courses/partials/sidebar.blade.php'));
        $interactions = file_get_contents(resource_path('views/courses/partials/scripts/interactions.blade.php'));

        $this->assertStringNotContainsString('id="progress-bar"', $view);
        $this->assertStringNotContainsString('id="sidebar-progress-bar"', $view);
        $this->assertStringNotContainsString('id="btn-complete"', $view);
        $this->assertStringNotContainsString('/complete`', $interactions);
        $this->assertStringNotContainsString('completedLessonIds', $sidebar);
        $this->assertStringContainsString('module-number-badge', $sidebar);
        $this->assertStringContainsString('lesson-order-badge', $sidebar);
        $this->assertStringContainsString('Chương {{ str_pad', $sidebar);
        $this->assertStringContainsString('course-outline-search', $view);
    }

    public function test_course_content_supports_direct_links_and_safe_video_embedding(): void
    {
        $interactions = file_get_contents(resource_path('views/courses/partials/scripts/interactions.blade.php'));
        $mobileScripts = file_get_contents(resource_path('views/courses/partials/show-page-scripts.blade.php'));

        $this->assertStringContainsString("params.get('lesson_id')", $interactions);
        $this->assertStringContainsString("params.get('assignment_id')", $interactions);
        $this->assertStringContainsString("params.get('quiz_id')", $interactions);
        $this->assertStringContainsString('window.history.pushState', $interactions);
        $this->assertStringContainsString('https://www.youtube.com/embed/', $interactions);
        $this->assertStringNotContainsString('http://www.youtube.com/embed/', $interactions);
        $this->assertStringContainsString('mobileContent.appendChild(outlineCard)', $mobileScripts);
        $this->assertStringContainsString('course-outline-home', $mobileScripts);
        $this->assertStringNotContainsString('cloneNode(true)', $mobileScripts);
        $this->assertStringContainsString('data-content-url', file_get_contents(resource_path('views/courses/partials/sidebar.blade.php')));
        $this->assertStringContainsString('loadLessonContent', $interactions);
    }

    public function test_mobile_course_outline_exposes_accessible_drawer_controls(): void
    {
        $view = file_get_contents(resource_path('views/courses/show.blade.php'));
        $scripts = file_get_contents(resource_path('views/courses/partials/show-page-scripts.blade.php'));

        $this->assertStringContainsString('role="dialog"', $view);
        $this->assertStringContainsString('aria-modal="true"', $view);
        $this->assertStringContainsString('aria-controls="mobile-sidebar-drawer"', $view);
        $this->assertStringContainsString("e.key === 'Escape'", $scripts);
        $this->assertStringContainsString("e.key !== 'Tab'", $scripts);
    }

    public function test_teacher_tool_dropdown_can_escape_its_panel(): void
    {
        $styles = file_get_contents(resource_path('css/pages/course-show.css'));

        $this->assertMatchesRegularExpression(
            '/\.teacher-mode-panel\s*\{[^}]*overflow:\s*visible;[^}]*z-index:\s*20;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/\.course-tool-menu\s*\{[^}]*z-index:\s*1080;/s',
            $styles
        );
    }
}
