<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('customer.notifications.index', [
            'notifications' => $request->user()
                ->notifications()
                ->latest()
                ->paginate(15),
        ]);
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $notificationModel = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        if ($notificationModel->read_at === null) {
            $notificationModel->markAsRead();
        }

        return back()->with('status', 'Notification marked as read.');
    }
}