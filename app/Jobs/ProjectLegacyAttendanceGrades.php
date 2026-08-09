<?php

namespace App\Jobs;

use App\Application\Gradebook\ProjectLegacyAttendanceGrades as Projector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProjectLegacyAttendanceGrades implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /** @param list<array{attendance_column_id:int,user_id:int}> $cells */
    public function __construct(
        public int $courseId,
        public array $cells,
        public int $actorId,
    ) {
        $this->onQueue('default');
    }

    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function handle(Projector $projector): void
    {
        $projector->handle($this->courseId, $this->cells, $this->actorId);
    }
}
