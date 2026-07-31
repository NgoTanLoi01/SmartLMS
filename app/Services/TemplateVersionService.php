<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TemplateVersionService
{
    public function bumpForCourse(Course|int|null $course, string $section = 'content'): void
    {
        $courseId = $course instanceof Course ? $course->id : $course;
        if (! $courseId) {
            return;
        }
        if (app()->runningUnitTests() && ! Schema::hasColumns('courses', ['template_version', 'template_section_versions'])) {
            return;
        }

        DB::transaction(function () use ($courseId, $section) {
            $template = Course::query()
                ->whereKey($courseId)
                ->where('course_type', 'template')
                ->lockForUpdate()
                ->first();
            if (! $template) {
                return;
            }

            $newVersion = max(1, (int) $template->template_version) + 1;
            $sectionVersions = $template->template_section_versions ?? [];
            $sectionVersions[$section] = $newVersion;
            $template->update([
                'template_version' => $newVersion,
                'template_section_versions' => $sectionVersions,
            ]);
        }, 3);
    }
}
