<?php

namespace App\Http\Controllers;

use App\Services\SubmissionService;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    protected $submissionService;

    public function __construct(SubmissionService $submissionService)
    {
        $this->submissionService = $submissionService;
    }

    public function submit(Request $request)
    {
        $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,html,htm,zip,rar|max:10240',
        ]);

        try {
            $submission = $this->submissionService->submitWork($request, auth()->id());
            return response()->json(['message' => 'Nộp bài thành công', 'data' => $submission]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
