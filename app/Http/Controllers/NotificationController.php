<?php

namespace App\Http\Controllers;

use App\Models\SmartNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = SmartNotification::forUser(auth()->id())->latest();
        if ($request->input('status') === 'unread') {
            $query->unread();
        }

        return view('notifications.index', [
            'notifications' => $query->paginate(20)->withQueryString(),
            'unreadCount' => SmartNotification::forUser(auth()->id())->unread()->count(),
            'filter' => $request->input('status'),
        ]);
    }

    public function open(SmartNotification $notification)
    {
        abort_unless($notification->user_id === auth()->id(), 403);
        $notification->update(['read_at' => $notification->read_at ?? now()]);

        return $notification->action_url
            ? redirect()->to($notification->action_url)
            : redirect()->route('notifications.index');
    }

    public function read(SmartNotification $notification)
    {
        abort_unless($notification->user_id === auth()->id(), 403);
        $notification->update(['read_at' => $notification->read_at ?? now()]);

        return back();
    }

    public function readAll()
    {
        SmartNotification::forUser(auth()->id())->unread()->update(['read_at' => now()]);

        return back()->with('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }
}
