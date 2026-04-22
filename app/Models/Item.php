<?php

namespace App\Models;

use App\Enums\ItemCondition;
use App\Enums\ItemStatus;
use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::created(function (self $item): void {
            $item->syncStatusWithAvailability();
        });

        static::updated(function (self $item): void {
            $item->syncStatusWithAvailability();
        });
    }

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'price',
        'quantity',
        'reserved_quantity',
        'description',
        'seller_name',
        'seller_contact_number',
        'condition',
        'tags',
        'image_path',
        'status',
        'restock_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'tags' => 'array',
        'condition' => ItemCondition::class,
        'status' => ItemStatus::class,
        'restock_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reservationItems(): HasMany
    {
        return $this->hasMany(ReservationItem::class);
    }

    public function availableQuantity(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    public function isAvailableForPurchase(): bool
    {
        return $this->status === ItemStatus::ACTIVE && $this->availableQuantity() > 0;
    }

    public function hasScheduledRestock(): bool
    {
        return $this->restock_at instanceof Carbon && $this->restock_at->isFuture();
    }

    public function nextReservationAvailabilityAt(): ?Carbon
    {
        $reservationItems = $this->relationLoaded('reservationItems')
            ? $this->reservationItems
            : $this->reservationItems()->with('reservation')->get();

        $availabilityMoments = $reservationItems
            ->map(fn ($reservationItem) => $reservationItem->reservation)
            ->filter()
            ->map(function ($reservation): ?Carbon {
                if (
                    $reservation->status === ReservationStatus::PENDING
                    && $reservation->expires_at instanceof Carbon
                    && $reservation->expires_at->isFuture()
                ) {
                    return $reservation->expires_at;
                }

                if (
                    $reservation->status === ReservationStatus::READY_FOR_PICKUP
                    && $reservation->pickup_date instanceof Carbon
                ) {
                    $pickupDeadline = $reservation->pickup_date->copy()->endOfDay();

                    if ($pickupDeadline->isFuture()) {
                        return $pickupDeadline;
                    }
                }

                return null;
            })
            ->filter()
            ->sortBy(fn (Carbon $moment) => $moment->getTimestamp())
            ->first();

        return $availabilityMoments;
    }

    public function isReservedOut(): bool
    {
        return $this->status === ItemStatus::ACTIVE
            && $this->availableQuantity() <= 0
            && $this->nextReservationAvailabilityAt() instanceof Carbon;
    }

    public function imageUrl(): ?string
    {
        if ($this->image_path === null || $this->image_path === '') {
            return null;
        }

        if (Str::startsWith($this->image_path, ['http://', 'https://', '/'])) {
            return $this->image_path;
        }

        return asset("storage/{$this->image_path}");
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    private function syncStatusWithAvailability(): void
    {
        if ($this->status === ItemStatus::ARCHIVED) {
            return;
        }

        $targetStatus = $this->availableQuantity() <= 0
            ? ItemStatus::OUT_OF_STOCK
            : ItemStatus::ACTIVE;

        if ($this->status !== $targetStatus) {
            $this->forceFill([
                'status' => $targetStatus,
            ])->saveQuietly();
        }
    }

    public function reserve(): bool
    {
        if ($this->status !== 'available') {
            return false;
        }

        $this->status = 'reserved';
        return $this->save();
    }

    public function release(): void
    {
        $this->status = 'available';
        $this->save();
    }
}
