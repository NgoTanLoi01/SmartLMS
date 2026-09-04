<?php

namespace App\Http\Controllers;

use App\Models\GradingFeedbackTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GradingFeedbackTemplateController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'content' => ['required', 'string', 'max:5000'],
        ]);

        GradingFeedbackTemplate::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'title' => $data['title']],
            ['content' => $data['content']]
        );

        return back()->with('success', 'Đã lưu mẫu nhận xét.');
    }

    public function destroy(Request $request, GradingFeedbackTemplate $template): RedirectResponse
    {
        abort_unless((int) $template->user_id === (int) $request->user()->id, 403);
        $template->delete();

        return back()->with('success', 'Đã xóa mẫu nhận xét.');
    }
}
