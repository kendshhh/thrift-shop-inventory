<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    public const DEFAULT_EXPIRATION_HOURS = 24;
    public const EXTENSION_HOURS = 24;
    public const MAX_PENDING_RESERVATIONS_PER_USER = 2;

    protected $fillable = [
        'user_id',
        'reference',
        'status',
        'payment_status',
        'pickup_date',
        'pickup_slot',
        'expires_at',
        'paid_at',
        'completed_at',
        'notes',
        'customer_request_type',
        'customer_request_status',
        'customer_request_reason',
        'customer_requested_pickup_date',
        'customer_requested_pickup_slot',
        'customer_requested_at',
        'customer_request_handled_at',
        'customer_request_admin_note',
        'extended_at',
        'total_amount',
    ];

    protected $casts = [
        'status' => ReservationStatus::class,
        'payment_status' => PaymentStatus::class,
        'pickup_date' => 'date',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
        'customer_requested_pickup_date' => 'date',
        'customer_requested_at' => 'datetime',
        'customer_request_handled_at' => 'datetime',
        'extended_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $reservation): void {
            if (empty($reservation->reference)) {
                $reservation->reference = self::generateReference();
            }

            if (empty($reservation->expires_at)) {
                $reservation->expires_at = now()->addHours(self::DEFAULT_EXPIRATION_HOURS);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reservationItems(): HasMany
    {
        return $this->hasMany(ReservationItem::class);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'reservation_items')
            ->withPivot(['quantity', 'unit_price', 'line_total'])
            ->withTimestamps();
    }

    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at) && $this->status === ReservationStatus::PENDING;
    }

    public function isExtendable(): bool
    {
        return $this->status === ReservationStatus::PENDING
            && $this->expires_at !== null
            && now()->lt($this->expires_at)
            && $this->extended_at === null;
    }

    private static function generateReference(): string
    {
        do {
            $reference = 'RSV-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
