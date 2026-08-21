<?php

namespace App\Http\Controllers;

use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request)
    {
        return success(['notifications' => $this->notifications->allFor($request->user())]);
    }

    public function markAsRead(Request $request, $id)
    {
        $this->notifications->markAsRead($request->user(), (string) $id);
        return success([], 200, 'Notification marked as read');
    }

    public function unread(Request $request)
    {
        return success($this->notifications->unreadFor($request->user()));
    }

    public function markAllAsRead(Request $request)
    {
        $this->notifications->markAllAsRead($request->user());
        return success(['message' => 'All notifications marked as read']);
    }
}
