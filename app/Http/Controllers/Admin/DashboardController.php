<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\ItemStatus;
use App\Http\Controllers\Controller;
use App\Models\BrandingSetting;
use App\Models\Item;
use App\Models\Reservation;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $paymentDetails = data_get(BrandingSetting::query()->find(1) ?? BrandingSetting::query()->first(), 'payment_details', []);

        // Trigger low stock notifications for all admins
        $lowStockItems = Item::query()
            ->where('status', ItemStatus::ACTIVE->value)
            ->whereRaw('(quantity - reserved_quantity) <= 3')
            ->get();
        if ($lowStockItems->isNotEmpty()) {
            $adminUsers = \App\Models\User::role('admin')->get();
            foreach ($lowStockItems as $item) {
                foreach ($adminUsers as $admin) {
                    // Only notify if not already notified and unread
                    $alreadyNotified = $admin->unreadNotifications()
                        ->where('type', \App\Notifications\LowStockNotification::class)
                        ->where('data->item_id', $item->id)
                        ->exists();
                    if (!$alreadyNotified) {
                        $admin->notify(new \App\Notifications\LowStockNotification($item));
                    }
                }
            }
        }

        // Trigger overdue reservation notifications for all admins
        $overdueReservations = Reservation::query()
            ->where('status', ReservationStatus::OVERDUE->value)
            ->get();
        if ($overdueReservations->isNotEmpty()) {
            $adminUsers = \App\Models\User::role('admin')->get();
            foreach ($overdueReservations as $reservation) {
                foreach ($adminUsers as $admin) {
                    $alreadyNotified = $admin->unreadNotifications()
                        ->where('type', \App\Notifications\OverdueReservationNotification::class)
                        ->where('data->reservation_id', $reservation->id)
                        ->exists();
                    if (!$alreadyNotified) {
                        $admin->notify(new \App\Notifications\OverdueReservationNotification($reservation));
                    }
                }
            }
        }

        return view('admin.dashboard', [
            'totalInventoryItems' => Item::query()->count(),
            'activeReservations' => Reservation::query()
                ->whereIn('status', [
                    ReservationStatus::PENDING->value,
                    ReservationStatus::READY_FOR_PICKUP->value,
                ])
                ->count(),
            'overdueReservations' => $overdueReservations->count(),
            'lowStockItems' => $lowStockItems->count(),
            'awaitingPaymentReservations' => Reservation::query()
                ->whereIn('status', ReservationStatus::activeValues())
                ->where('payment_status', PaymentStatus::PENDING->value)
                ->count(),
            'readyForPickupReservations' => Reservation::query()
                ->where('status', ReservationStatus::READY_FOR_PICKUP->value)
                ->where('payment_status', PaymentStatus::PENDING->value)
                ->count(),
            'completedPayments' => Reservation::query()
                ->where('payment_status', PaymentStatus::COMPLETED->value)
                ->count(),
            'paymentDetailCount' => is_array($paymentDetails) ? count($paymentDetails) : 0,
        ]);
    }
}
