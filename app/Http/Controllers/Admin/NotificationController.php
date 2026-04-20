<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Notifications\AdminReservationNotification;
use App\Notifications\LowStockNotification;
use App\Notifications\OverdueReservationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * @return array<int, string>
     */
    private function adminNotificationTypes(): array
    {
        return [
            AdminReservationNotification::class,
            LowStockNotification::class,
            OverdueReservationNotification::class,
        ];
    }

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'read_state' => ['nullable', Rule::in(['unread', 'read'])],
            'event_type' => ['nullable', Rule::in([
                'new_reservation',
                'customer_request_submitted',
                'status_changed',
                'low_stock',
                'overdue_reservation',
            ])],
            'sort' => ['nullable', Rule::in(['latest', 'oldest'])],
        ]);

        $notificationsQuery = $request->user()
            ->notifications()
            ->whereIn('type', $this->adminNotificationTypes());

        if (!empty($validated['read_state'])) {
            if ($validated['read_state'] === 'unread') {
                $notificationsQuery->whereNull('read_at');
            } else {
                $notificationsQuery->whereNotNull('read_at');
            }
        }

        if (!empty($validated['event_type'])) {
            $notificationsQuery->where('data->event_type', $validated['event_type']);
        }

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);

            $notificationsQuery->where(function ($builder) use ($search): void {
                $builder->where('data->title', 'like', '%'.$search.'%')
                    ->orWhere('data->message', 'like', '%'.$search.'%')
                    ->orWhere('data->reference', 'like', '%'.$search.'%')
                    ->orWhere('data->customer_name', 'like', '%'.$search.'%')
                    ->orWhere('data->item_name', 'like', '%'.$search.'%');
            });
        }

        return view('admin.notifications.index', [
            'notifications' => $notificationsQuery
                ->when(($validated['sort'] ?? 'latest') === 'oldest', static fn ($query) => $query->oldest(), static fn ($query) => $query->latest())
                ->paginate(15)
                ->withQueryString(),
            'unreadCount' => $request->user()
                ->unreadNotifications()
                ->whereIn('type', $this->adminNotificationTypes())
                ->count(),
            'filters' => $request->only(['search', 'read_state', 'event_type', 'sort']),
        ]);
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $notificationModel = $request->user()
            ->notifications()
            ->whereIn('type', $this->adminNotificationTypes())
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
            ->whereIn('type', $this->adminNotificationTypes())
            ->get()
            ->each
            ->markAsRead();

        return back()->with('status', 'All admin notifications marked as read.');
    }
}