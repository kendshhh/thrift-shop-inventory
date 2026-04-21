<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'read_state' => ['nullable', Rule::in(['unread', 'read'])],
        ]);

        $notificationsQuery = $request->user()->notifications();

        if (!empty($validated['read_state'])) {
            if ($validated['read_state'] === 'unread') {
                $notificationsQuery->whereNull('read_at');
            } else {
                $notificationsQuery->whereNotNull('read_at');
            }
        }

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);

            $notificationsQuery->where(function ($builder) use ($search): void {
                $builder->where('data->title', 'like', '%'.$search.'%')
                    ->orWhere('data->message', 'like', '%'.$search.'%')
                    ->orWhere('data->reference', 'like', '%'.$search.'%')
                    ->orWhere('data->status_label', 'like', '%'.$search.'%');
            });
        }

        return view('customer.notifications.index', [
            'notifications' => $notificationsQuery
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'unreadCount' => $request->user()
                ->unreadNotifications()
                ->count(),
            'filters' => $request->only(['search', 'read_state']),
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

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()
            ->unreadNotifications()
            ->get()
            ->each
            ->markAsRead();

        return back()->with('status', 'All notifications marked as read.');
    }
}