<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\TrashService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrashController extends Controller
{
    public function __construct(private TrashService $trash) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'type' => ['nullable', Rule::in(array_keys(TrashService::TYPES))],
            'search' => 'nullable|string|max:150',
        ]);
        $type = $validated['type'] ?? 'course';
        $search = trim((string) ($validated['search'] ?? ''));
        $user = $request->user();

        return view('trash.index', [
            'types' => TrashService::TYPES,
            'activeType' => $type,
            'search' => $search,
            'counts' => $this->trash->counts($user),
            'items' => $this->trash->paginate($type, $user, $search),
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    public function restore(Request $request)
    {
        $items = $this->validatedItems($request);
        $count = $this->trash->restoreMany($items, $request->user());

        AuditLogger::log(
            AuditLogger::TRASH_RESTORED,
            null,
            null,
            ['items' => $items],
            ['restored_count' => $count],
            'Khôi phục dữ liệu từ thùng rác.'
        );

        return back()->with('success', "Đã khôi phục {$count} mục. Các nội dung xuất bản được đưa về bản nháp hoặc ẩn để kiểm tra trước.");
    }

    public function permanentlyDelete(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $items = $this->validatedItems($request);
        $validated = $request->validate(['confirmation' => 'required|string']);

        if (! hash_equals('XOA VINH VIEN', trim((string) $validated['confirmation']))) {
            throw ValidationException::withMessages([
                'confirmation' => 'Vui lòng nhập chính xác XOA VINH VIEN để xác nhận.',
            ]);
        }

        $count = $this->trash->permanentlyDeleteMany($items, $request->user());

        AuditLogger::log(
            AuditLogger::TRASH_PERMANENTLY_DELETED,
            null,
            ['items' => $items],
            null,
            ['deleted_count' => $count],
            'Xóa vĩnh viễn dữ liệu trong thùng rác.'
        );

        return back()->with('success', "Đã xóa vĩnh viễn {$count} mục và dữ liệu phụ thuộc liên quan.");
    }

    /** @return array<int, array{type: string, id: int}> */
    private function validatedItems(Request $request): array
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1|max:100',
            'items.*' => ['required', 'string', 'distinct', 'regex:/^(course|module|lesson|assignment|material|material_assignment|schedule):[1-9][0-9]*$/'],
        ], [
            'items.required' => 'Vui lòng chọn ít nhất một mục.',
            'items.min' => 'Vui lòng chọn ít nhất một mục.',
            'items.*.regex' => 'Danh sách mục đã chọn không hợp lệ.',
        ]);

        return collect($validated['items'])->map(function (string $token): array {
            [$type, $id] = explode(':', $token, 2);

            return ['type' => $type, 'id' => (int) $id];
        })->all();
    }
}
