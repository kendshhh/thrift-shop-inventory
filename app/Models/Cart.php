<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = ['user_id', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function totalQuantity(): int
    {
        if ($this->relationLoaded('items')) {
            return (int) $this->items->sum('quantity');
        }

        return (int) $this->items()->sum('quantity');
    }

    public static function currentForUser(?int $userId): ?self
    {
        if ($userId === null) {
            return null;
        }

        return self::query()
            ->where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->latest('expires_at')
            ->latest('id')
            ->first();
    }

    public static function currentOrCreateForUser(int $userId): self
    {
        self::query()
            ->where('user_id', $userId)
            ->where('expires_at', '<=', now())
            ->delete();

        return self::currentForUser($userId)
            ?? self::query()->create([
                'user_id' => $userId,
                'expires_at' => now()->addMinutes(15),
            ]);
    }

    public function refreshExpiry(int $minutes = 15): void
    {
        $this->forceFill([
            'expires_at' => now()->addMinutes($minutes),
        ])->save();
    }
}