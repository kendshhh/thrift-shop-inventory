<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ItemCondition;
use App\Enums\ItemStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BrowseController extends Controller
{
    public function home(Request $request): View
    {
        $customerStats = [
            'total_reservations' => 0,
            'pending_reservations' => 0,
            'expiring_soon' => 0,
            'upcoming_pickups' => 0,
        ];

        $upcomingReservations = collect();

        if ($request->user()?->hasRole('customer')) {
            $statusCounts = $request->user()
                ->reservations()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            $customerStats = [
                'total_reservations' => (int) $statusCounts->sum(),
                'pending_reservations' => (int) ($statusCounts[ReservationStatus::PENDING->value] ?? 0),
                'expiring_soon' => $request->user()
                    ->reservations()
                    ->where('status', ReservationStatus::PENDING->value)
                    ->whereBetween('expires_at', [now(), now()->addDay()])
                    ->count(),
                'upcoming_pickups' => $request->user()
                    ->reservations()
                    ->whereIn('status', [
                        ReservationStatus::PENDING->value,
                        ReservationStatus::OVERDUE->value,
                    ])
                    ->whereDate('pickup_date', '>=', today())
                    ->count(),
            ];

            $upcomingReservations = $request->user()
                ->reservations()
                ->with('reservationItems.item')
                ->whereIn('status', [
                    ReservationStatus::PENDING->value,
                    ReservationStatus::OVERDUE->value,
                ])
                ->whereDate('pickup_date', '>=', today())
                ->orderBy('pickup_date')
                ->orderBy('created_at')
                ->limit(3)
                ->get();
        }

        return view('customer.home', [
            'featuredItems' => Item::query()
                ->with(['category', 'reservationItems.reservation'])
                ->where(function ($builder): void {
                    $builder
                        ->where(function ($available): void {
                            $available->where('status', ItemStatus::ACTIVE->value);
                        })
                        ->orWhere(function ($restocking): void {
                            $restocking->where('status', ItemStatus::OUT_OF_STOCK->value)
                                ->whereNotNull('restock_at')
                                ->where('restock_at', '>', now());
                        });
                })
                ->orderByDesc('created_at')
                ->limit(8)
                ->get(),
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'customerStats' => $customerStats,
            'upcomingReservations' => $upcomingReservations,
        ]);
    }

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'condition' => ['nullable', Rule::in(ItemCondition::values())],
            'sort' => ['nullable', Rule::in(['latest', 'price_asc', 'price_desc'])],
        ]);

        $query = Item::query()
            ->with(['category', 'reservationItems.reservation'])
            ->where(function ($builder): void {
                $builder
                    ->where(function ($available): void {
                        $available->where('status', ItemStatus::ACTIVE->value);
                    })
                    ->orWhere(function ($restocking): void {
                        $restocking->where('status', ItemStatus::OUT_OF_STOCK->value)
                            ->whereNotNull('restock_at')
                            ->where('restock_at', '>', now());
                    });
            });

        if (!empty($validated['search'])) {
            $query->where(function ($builder) use ($validated): void {
                $builder->where('name', 'like', '%'.$validated['search'].'%')
                    ->orWhere('description', 'like', '%'.$validated['search'].'%');
            });
        }

        if (!empty($validated['category_id'])) {
            $query->where('category_id', (int) $validated['category_id']);
        }

        if (!empty($validated['condition'])) {
            $query->where('condition', $validated['condition']);
        }

        $sort = $validated['sort'] ?? 'latest';

        if ($sort === 'price_asc') {
            $query->orderBy('price');
        } elseif ($sort === 'price_desc') {
            $query->orderByDesc('price');
        } else {
            $query->latest();
        }

        return view('customer.items.index', [
            'items' => $query->paginate(12)->withQueryString(),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'conditions' => ItemCondition::cases(),
            'filters' => $request->only(['search', 'category_id', 'condition', 'sort']),
        ]);
    }

    public function show(Item $item): View
    {
        abort_if($item->status === ItemStatus::ARCHIVED, 404);

        $reservationLock = [
            'is_locked' => false,
            'pending_count' => 0,
            'limit' => Reservation::MAX_PENDING_RESERVATIONS_PER_USER,
        ];

        if (auth()->user()?->hasRole('customer')) {
            $pendingCount = auth()->user()
                ->reservations()
                ->where('status', ReservationStatus::PENDING->value)
                ->count();

            $reservationLock = [
                'is_locked' => $pendingCount >= Reservation::MAX_PENDING_RESERVATIONS_PER_USER,
                'pending_count' => $pendingCount,
                'limit' => Reservation::MAX_PENDING_RESERVATIONS_PER_USER,
            ];
        }

        return view('customer.items.show', [
            'item' => $item->load(['category', 'reservationItems.reservation']),
            'reservationLock' => $reservationLock,
        ]);
    }
}
