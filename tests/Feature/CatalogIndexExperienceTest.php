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
            $this->assertStringContainsString('catalog-hero', $view);
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

        $this->assertStringContainsString("route('courses.show', \$course)", $card);
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

    public function test_core_content_management_indexes_use_the_unified_workspace_pattern(): void
    {
        $interfaces = [
            resource_path('views/courses/index.blade.php') => 'catalog-hero',
            resource_path('views/classes/index.blade.php') => 'catalog-hero',
            resource_path('views/assignments/index.blade.php') => 'assignment-hero',
            resource_path('views/shared-documents/index.blade.php') => 'document-hero',
            resource_path('views/quizzes/question_bank.blade.php') => 'question-overview',
        ];

        foreach ($interfaces as $path => $heroClass) {
            $view = file_get_contents($path);

            $this->assertStringContainsString('lms-page', $view);
            $this->assertStringContainsString('<x-ui.page-header', $view);
            $this->assertStringContainsString($heroClass, $view);
        }
    }

    public function test_material_library_and_vocational_grade_tool_use_the_unified_workspace_pattern(): void
    {
        $materials = file_get_contents(resource_path('views/courses/materials_index.blade.php'));
        $gradeTool = file_get_contents(resource_path('views/tools/grade-calculator.blade.php'));

        $this->assertStringContainsString('materials-index-hero__accent', $materials);
        $this->assertStringContainsString('materials-stat-shelf', $materials);
        $this->assertStringContainsString('grade-tool-hero__accent', $gradeTool);
        $this->assertStringContainsString('grade-tool-summary', $gradeTool);
        $this->assertStringContainsString('<x-ui.page-header', $gradeTool);
        $this->assertStringContainsString('grade-workspace-head', $gradeTool);
    }
}
