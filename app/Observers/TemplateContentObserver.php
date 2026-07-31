<?php

namespace App\Observers;

use App\Models\Assignments;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\TemplateVersionService;

class TemplateContentObserver
{
    public function __construct(private TemplateVersionService $versions) {}

    public function saved(Module|Lesson|Assignments|Quiz|Question $model): void
    {
        $this->bump($model);
    }

    public function deleted(Module|Lesson|Assignments|Quiz|Question $model): void
    {
        $this->bump($model);
    }

    private function bump(Module|Lesson|Assignments|Quiz|Question $model): void
    {
        $courseId = match (true) {
            $model instanceof Module => $model->course_id,
            $model instanceof Lesson => $model->module?->course_id,
            default => $model->course_id,
        };

        $section = match (true) {
            $model instanceof Assignments => 'assignments',
            $model instanceof Quiz => 'quizzes',
            $model instanceof Question => 'question_banks',
            default => 'content',
        };

        $this->versions->bumpForCourse($courseId, $section);
    }
}
