<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Queries\Grading\TeacherWorkQueueQuery;
use Illuminate\Http\Request;

class TeacherWorkQueueController extends Controller
{
    public function __construct(private TeacherWorkQueueQuery $workQueue) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $courses = Course::query()
            ->when($user->isTeacher(), fn ($query) => $query->where('teacher_id', $user->id))
            ->notArchived()
            ->orderBy('title')
            ->get(['id', 'title']);
        $items = $this->workQueue->paginate($courses->pluck('id'), $request->only(['type', 'course_id', 'urgency', 'q']));

        return view('grading.inbox', compact('items', 'courses'));
    }
}
