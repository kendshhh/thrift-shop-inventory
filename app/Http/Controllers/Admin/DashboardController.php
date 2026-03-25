<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReservationStatus;
use App\Enums\ItemStatus;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Reservation;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'totalInventoryItems' => Item::query()->count(),
            'activeReservations' => Reservation::query()
                ->where('status', ReservationStatus::PENDING->value)
                ->count(),
            'overdueReservations' => Reservation::query()
                ->where('status', ReservationStatus::OVERDUE->value)
                ->count(),
            'lowStockItems' => Item::query()
                ->where('status', ItemStatus::ACTIVE->value)
                ->whereRaw('(quantity - reserved_quantity) <= 3')
                ->count(),
        ]);
    }
}
