<?php

namespace Tests\Feature;

use Tests\TestCase;

class CatalogIndexExperienceTest extends TestCase
{
    public function test_course_and_class_indexes_share_the_same_design_system(): void
    {
        $courses = file_get_contents(resource_path('views/courses/index.blade.php'));
        $classes = file_get_contents(resource_path('views/classes/index.blade.php'));

        foreach ([$courses, $classes] as $view) {
            $this->assertStringContainsString("@vite('resources/css/pages/catalog-index.css')", $view);
            $this->assertStringContainsString('catalog-summary', $view);
            $this->assertStringContainsString('catalog-filter-panel', $view);
            $this->assertStringContainsString('catalog-grid', $view);
            $this->assertStringNotContainsString('<style>', $view);
        }
    }

    public function test_course_catalog_matches_the_review_only_learning_model(): void
    {
        $card = file_get_contents(resource_path('views/courses/partials/course-card.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/CourseController.php'));

        $this->assertStringContainsString('Nội dung dùng để xem lại bài học', $card);
        $this->assertStringContainsString('Xem nội dung', $card);
        $this->assertStringNotContainsString('progress-bar', $card);
        $this->assertStringNotContainsString('completedLessons', $controller);
        $this->assertStringContainsString("'Đã xuất bản'", $card);
        $this->assertStringContainsString("'Bản nháp'", $card);
    }

    public function test_class_catalog_exposes_search_filters_and_accessible_actions(): void
    {
        $view = file_get_contents(resource_path('views/classes/index.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/ClassManagementController.php'));

        $this->assertStringContainsString('name="search"', $view);
        $this->assertStringContainsString('name="teacher_id"', $view);
        $this->assertStringContainsString('aria-label="Bộ lọc lớp học"', $view);
        $this->assertStringContainsString('aria-label="Đóng"', $view);
        $this->assertStringContainsString('<x-ui.pagination', $view);
        $this->assertStringContainsString("'search' => trim(request('search', ''))", $controller);
        $this->assertStringContainsString('paginate(12)->withQueryString()', $controller);
    }
}
