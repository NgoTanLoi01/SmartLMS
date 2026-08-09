<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorePaginationArchitectureTest extends TestCase
{
    public function test_high_volume_catalogs_use_server_side_pagination(): void
    {
        $courseController = file_get_contents(app_path('Http/Controllers/CourseController.php'));
        $documentController = file_get_contents(app_path('Http/Controllers/DocumentController.php'));
        $assignmentController = file_get_contents(app_path('Http/Controllers/AssignmentController.php'));

        $this->assertStringContainsString('->paginate(18)', $courseController);
        $this->assertStringNotContainsString("'classes.students:id'", $courseController);
        $this->assertStringContainsString('->paginate(18)', $documentController);
        $this->assertStringContainsString('->paginate(25)', $assignmentController);
        $this->assertStringContainsString("'pagination' => [", $assignmentController);
    }

    public function test_pagination_keeps_query_strings_and_has_navigation_in_each_view(): void
    {
        $courseController = file_get_contents(app_path('Http/Controllers/CourseController.php'));
        $documentController = file_get_contents(app_path('Http/Controllers/DocumentController.php'));
        $courseView = file_get_contents(resource_path('views/courses/index.blade.php'));
        $documentView = file_get_contents(resource_path('views/documents/upload.blade.php'));
        $assignmentView = file_get_contents(resource_path('views/assignments/index.blade.php'));

        $this->assertStringContainsString('->withQueryString()', $courseController);
        $this->assertStringContainsString('->withQueryString()', $documentController);
        $this->assertStringContainsString(':paginator="$courses"', $courseView);
        $this->assertStringContainsString(':paginator="$documents"', $documentView);
        $this->assertStringContainsString('submission-page-link', $assignmentView);
    }
}
