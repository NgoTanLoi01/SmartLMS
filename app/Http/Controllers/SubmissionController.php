<?php

namespace App\Http\Controllers;

use App\Services\SubmissionService;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    protected $submissionService;

    // Dependency Injection: Laravel tự động nạp Service vào đây
    public function __construct(SubmissionService $submissionService)
    {
        $this->submissionService = $submissionService;
    }

    public function submit(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'file' => 'required|mimes:pdf,doc,docx,jpg,png|max:5120', // Max 5MB
        ]);

        try {
            $submission = $this->submissionService->submitWork($request, auth()->id());
            return response()->json(['message' => 'Nộp bài thành công', 'data' => $submission]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}